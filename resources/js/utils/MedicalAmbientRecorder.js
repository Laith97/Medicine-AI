export class MedicalAmbientRecorder {
    constructor(config = {}) {
        this.audioContext = null;
        this.mediaStream = null;
        this.websocket = null;
        this.processor = null;
        this.source = null;
        this.isRecording = false;
        this.chunkSequence = 0;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.isDestroyed = false;
        
        this.config = {
            sampleRate: 16000,
            bufferSize: 4096,
            ...config
        };

        this.onTranscriptUpdate = null;
        this.onStatusChange = null;
        this.onError = null;
        this.sendToAssemblyAI = false;
        this.assemblySocket = null;
        this.visitId = null;
        this.authToken = null;
    }

    /**
     * Validates the visitId format to prevent injection attacks
     * @param {*} visitId - The visit identifier to validate
     * @returns {boolean} - True if valid, false otherwise
     */
    isValidVisitId(visitId) {
        if (!visitId || typeof visitId !== 'string') {
            return false;
        }

        // UUID format validation or numeric ID validation
        const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
        const numericIdRegex = /^\d+$/;

        return uuidRegex.test(visitId) || numericIdRegex.test(visitId);
    }

    /**
     * Validates the authToken format to prevent injection attacks
     * @param {*} authToken - The authentication token to validate
     * @returns {boolean} - True if valid, false otherwise
     */
    isValidAuthToken(authToken) {
        if (!authToken || typeof authToken !== 'string') {
            return false;
        }

        // Basic JWT format validation or other token format
        const jwtRegex = /^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+$/;
        const uuidTokenRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
        const alphanumericRegex = /^[a-zA-Z0-9+/=]+$/;

        return jwtRegex.test(authToken) ||
               uuidTokenRegex.test(authToken) ||
               (alphanumericRegex.test(authToken) && authToken.length >= 16);
    }

    async startRecording(visitId, authToken, language = 'en') {
        if (this.isDestroyed) throw new Error('Recorder has been destroyed');

        // Validate inputs to prevent injection attacks
        if (!this.isValidVisitId(visitId)) {
            throw new Error('Invalid visitId provided');
        }

        if (!this.isValidAuthToken(authToken)) {
            throw new Error('Invalid authToken provided');
        }

        this.visitId = visitId;
        this.authToken = authToken;
        this.language = language;

        try {
            // 1. Get microphone with MEDICAL OPTIMIZED settings
            this.mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                    sampleRate: this.config.sampleRate,
                    sampleSize: 16
                }
            });

            // 2. Create WebSocket connection with retry logic
            await this.connectWebSocket();

            // 3. Create audio processing pipeline
            await this.createAudioProcessingPipeline();

            this.isRecording = true;
            this.reconnectAttempts = 0;
            this.handleStatusChange('recording');

        } catch (error) {
            this.cleanup();
            this.handleError('Failed to start recording', error);
            throw error;
        }
    }

    stopRecording() {
        this.isRecording = false;
        this.cleanup();
        this.handleStatusChange('stopped');
    }
    
    destroy() {
        this.isDestroyed = true;
        this.stopRecording();
    }

    async createAudioProcessingPipeline() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)({ 
                sampleRate: this.config.sampleRate 
            });
            
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }
            
            this.source = this.audioContext.createMediaStreamSource(this.mediaStream);
            this.processor = this.audioContext.createScriptProcessor(this.config.bufferSize, 1, 1);
            
            this.processor.onaudioprocess = (e) => {
                if (!this.isRecording || this.isDestroyed) return;
                
                try {
                    const audioData = e.inputBuffer.getChannelData(0);
                    const pcmData = this.convertFloat32ToInt16(audioData);
                    
                    // Send to AssemblyAI if configured
                    if (this.sendToAssemblyAI && this.assemblySocket && this.assemblySocket.readyState === WebSocket.OPEN) {
                        const audioBuffer = new ArrayBuffer(pcmData.length * 2);
                        const view = new DataView(audioBuffer);
                        for (let i = 0; i < pcmData.length; i++) {
                            view.setInt16(i * 2, pcmData[i], true);
                        }
                        this.assemblySocket.send(audioBuffer);
                    }
                    
                    // Also send to main WebSocket for processing
                    if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                        this.websocket.send(JSON.stringify({
                            type: 'audio_chunk',
                            data: Array.from(pcmData),
                            timestamp: Date.now(),
                            sequence: this.chunkSequence++
                        }));
                    }
                } catch (error) {
                    console.error('Audio processing error:', error);
                }
            };

            this.source.connect(this.processor);
            this.processor.connect(this.audioContext.destination);
        } catch (error) {
            throw new Error('Failed to create audio pipeline: ' + error.message);
        }
    }

    convertFloat32ToInt16(float32Array) {
        let l = float32Array.length;
        const buffer = new Int16Array(l);
        while (l--) {
            buffer[l] = Math.min(1, Math.max(-1, float32Array[l])) * 0x7FFF;
        }
        return buffer;
    }

    async connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsPort = window.location.protocol === 'https:' ? '6002' : '6001';

        // URL encode the parameters to prevent injection
        const encodedToken = encodeURIComponent(this.authToken);
        const encodedVisitId = encodeURIComponent(this.visitId);
        const encodedLanguage = encodeURIComponent(this.language || 'en');
        const wsUrl = `${protocol}//${window.location.hostname}:${wsPort}/ws/medical-audio?token=${encodedToken}&visit_id=${encodedVisitId}&language=${encodedLanguage}`;

        return new Promise((resolve, reject) => {
            this.websocket = new WebSocket(wsUrl);
            this.configureWebSocketHandlers();

            const timeout = setTimeout(() => {
                reject(new Error('WebSocket connection timeout'));
            }, 10000);

            this.websocket.onopen = () => {
                clearTimeout(timeout);
                this.handleStatusChange('connected');
                resolve();
            };

            this.websocket.onerror = (error) => {
                clearTimeout(timeout);
                reject(error);
            };
        });
    }

    configureWebSocketHandlers() {
        this.websocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);

                if (data.type === 'config' && data.provider === 'assemblyai') {
                    this.setupAssemblyAIConnection(data);
                    return;
                }

                if (this.onTranscriptUpdate) {
                    this.onTranscriptUpdate(data);
                }
            } catch (e) {
                console.error('Error parsing WebSocket message:', e);
            }
        };

        this.websocket.onclose = (event) => {
            if (this.isRecording && !this.isDestroyed) {
                this.handleStatusChange('disconnected');
                this.attemptReconnect();
            }
        };

        this.websocket.onerror = (error) => {
            this.handleError('WebSocket error', error);
        };
    }

    handleStatusChange(status) {
        if (this.onStatusChange) {
            this.onStatusChange(status);
        }
    }

    handleError(message, error) {
        console.error(message, error);
        if (this.onError) {
            this.onError(message, error);
        }
    }

    async attemptReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts || this.isDestroyed) {
            this.handleError('Max reconnection attempts reached', new Error('Connection failed'));
            return;
        }
        
        this.reconnectAttempts++;
        this.handleStatusChange('reconnecting');
        
        setTimeout(async () => {
            try {
                await this.connectWebSocket();
                this.reconnectAttempts = 0;
                this.handleStatusChange('reconnected');
            } catch (error) {
                this.attemptReconnect();
            }
        }, this.reconnectDelay * this.reconnectAttempts);
    }

    setupAssemblyAIConnection(config) {
        this.assemblySocket = new WebSocket(config.websocket_url);
        
        this.assemblySocket.onopen = () => {
            this.handleStatusChange('assemblyai_connected');
            this.sendToAssemblyAI = true;
        };
        
        this.assemblySocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.message_type === 'PartialTranscript' || data.message_type === 'FinalTranscript') {
                    if (this.onTranscriptUpdate) {
                        this.onTranscriptUpdate({
                            type: 'transcript_update',
                            text: data.text,
                            is_final: data.message_type === 'FinalTranscript',
                            speaker: data.speaker || 'unknown',
                            confidence: data.confidence
                        });
                    }
                }
            } catch (error) {
                console.error('AssemblyAI message parsing error:', error);
            }
        };
        
        this.assemblySocket.onclose = () => {
            this.sendToAssemblyAI = false;
            if (this.isRecording && !this.isDestroyed) {
                this.handleStatusChange('assemblyai_disconnected');
            }
        };
        
        this.assemblySocket.onerror = (error) => {
            this.sendToAssemblyAI = false;
            this.handleError('AssemblyAI WebSocket error', error);
        };
    }
    
    cleanup() {
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
            this.mediaStream = null;
        }

        if (this.processor) {
            this.processor.disconnect();
            this.processor = null;
        }

        if (this.source) {
            this.source.disconnect();
            this.source = null;
        }

        if (this.audioContext && this.audioContext.state !== 'closed') {
            this.audioContext.close();
            this.audioContext = null;
        }

        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }
        
        if (this.assemblySocket) {
            this.assemblySocket.close();
            this.assemblySocket = null;
        }
        
        this.sendToAssemblyAI = false;
    }
}
