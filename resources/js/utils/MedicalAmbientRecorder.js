import { v4 as uuidv4 } from 'uuid';

export class MedicalAmbientRecorder {
    constructor(config = {}) {
        this.instanceId = Math.random().toString(36).substr(2, 9);
        console.log('🎬 Creating MedicalAmbientRecorder instance:', this.instanceId);
        
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
        this.localMediaRecorder = null;
        this.recordedChunks = [];
        this.audioBlob = null;

        this.config = {
            sampleRate: 16000,
            bufferSize: 4096,
            ...config
        };

        // Extract and set callback functions from config
        this.onTranscriptUpdate = config.onTranscriptUpdate || null;
        this.onStatusChange = config.onStatusChange || null;
        this.onError = config.onError || null;
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

    async startRecording(visitId, authToken, language = 'en', assemblyConfig = null) {
        if (this.isDestroyed) throw new Error('Recorder has been destroyed');

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
            // 1. Get microphone
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

            console.log('✅ Microphone access granted, mediaStream:', this.mediaStream);

            // 2. Setup direct AssemblyAI connection ONLY if config is provided (English only)
            if (assemblyConfig) {
                console.log('🔗 Connecting to AssemblyAI...', assemblyConfig);
                try {
                    await this.setupAssemblyAIConnection(assemblyConfig);
                    console.log('✅ AssemblyAI streaming initialized');
                } catch (assemblyError) {
                    console.error('❌ AssemblyAI connection failed:', assemblyError);
                }
            } else {
                console.log('ℹ️ No AssemblyAI config provided - using local recording with post-session processing');
            }

            // 2b. Setup local recording for high-quality fallback
            console.log('🎥 Setting up local recording...');
            this.setupLocalRecording();
            console.log('✅ Local recording setup complete, localMediaRecorder:', this.localMediaRecorder);

            // 3. Skip WebSocket for all languages - use post-processing for speaker diarization
            if (false) { // Disabled: Always use post-processing for better speaker diarization
                try {
                    console.log('🔗 Connecting to local WebSocket...');
                    await this.connectWebSocket();
                    console.log('✅ Local WebSocket connected');
                } catch (wsError) {
                    console.warn('⚠️ Local WebSocket connection failed:', wsError.message);
                    if (!this.sendToAssemblyAI) {
                        console.error('❌ No active transcription service available');
                        throw wsError;
                    }
                    console.log('ℹ️ Proceeding with AssemblyAI streaming only');
                }
            } else {
                console.log('ℹ️ Arabic session: Recording locally, will use GPT-4o for high-quality transcription after recording stops');
            }

            // 4. Create audio processing pipeline
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
        return new Promise((resolve) => {
            if (this.localMediaRecorder && this.localMediaRecorder.state !== 'inactive') {
                const originalOnStop = this.localMediaRecorder.onstop;
                this.localMediaRecorder.onstop = () => {
                    if (originalOnStop) originalOnStop();
                    console.log('⏹️ Local MediaRecorder stopped, audio blob ready');
                    resolve();
                };
                this.localMediaRecorder.stop();
            } else {
                resolve();
            }
            this.isRecording = false;
            // DON'T call cleanup here - it will destroy the MediaRecorder
            this.handleStatusChange('stopped');
        });
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

                    // Throttled logging to verify audio flow (every ~100 chunks)
                    if (this.chunkSequence % 100 === 0) {
                        console.log(`🎙️ Audio flow check: Chunks sent: ${this.chunkSequence}, Size: ${pcmData.length}, AssemblyAI State: ${this.assemblySocket ? this.assemblySocket.readyState : 'null'}`);
                    }

                    // Send to AssemblyAI if configured
                    if (this.sendToAssemblyAI && this.assemblySocket && this.assemblySocket.readyState === WebSocket.OPEN) {
                        try {
                            const audioBuffer = new ArrayBuffer(pcmData.length * 2);
                            const view = new DataView(audioBuffer);
                            for (let i = 0; i < pcmData.length; i++) {
                                view.setInt16(i * 2, pcmData[i], true);
                            }
                            this.assemblySocket.send(audioBuffer);
                        } catch (sendError) {
                            console.error('❌ Error sending to AssemblyAI:', sendError);
                        }
                    }

                    // Also send to main WebSocket for processing
                    if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
                        this.websocket.send(JSON.stringify({
                            type: 'audio_chunk',
                            data: Array.from(pcmData),
                            timestamp: Date.now(),
                            sequence: this.chunkSequence
                        }));
                    }

                    this.chunkSequence++;
                } catch (error) {
                    console.error('❌ Audio processing logic error:', error);
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
            try {
                this.websocket = new WebSocket(wsUrl);
                this.configureWebSocketHandlers();

                const timeout = setTimeout(() => {
                    reject(new Error('WebSocket connection timeout'));
                }, 5000); // Reduced timeout to fail faster

                this.websocket.onopen = () => {
                    clearTimeout(timeout);
                    this.handleStatusChange('connected');
                    resolve();
                };

                this.websocket.onerror = (error) => {
                    clearTimeout(timeout);
                    // Don't immediately reject, allow fallback to browser recording
                    reject(new Error('WebSocket connection failed - falling back to browser recording'));
                };

                this.websocket.onclose = (event) => {
                    if (!event.wasClean) {
                        clearTimeout(timeout);
                        reject(new Error('WebSocket connection closed unexpectedly'));
                    }
                };
            } catch (error) {
                clearTimeout(timeout);
                reject(new Error('Failed to create WebSocket connection: ' + error.message));
            }
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
                    // Ensure confidence is included in the data
                    if (data.type === 'transcript_update' && data.confidence === undefined) {
                        // Default confidence based on speaker identification or other factors
                        data.confidence = 0.8; // Default to 80% confidence
                    }
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
        const enhancedMessage = this.getEnhancedErrorMessage(message, error);
        if (this.onError) {
            this.onError(enhancedMessage, error);
        }
    }

    getEnhancedErrorMessage(message, error) {
        let enhancedMessage = message;

        // Add specific guidance based on error type
        if (error && error.name) {
            switch (error.name) {
                case 'NotAllowedError':
                    enhancedMessage += ' - Microphone access was denied. Please grant microphone permissions in your browser settings.';
                    break;
                case 'NotFoundError':
                    enhancedMessage += ' - No microphone was found. Please connect a microphone and try again.';
                    break;
                case 'NotReadableError':
                    enhancedMessage += ' - Could not access the microphone. Another application may be using it.';
                    break;
                case 'SecurityError':
                    enhancedMessage += ' - Microphone access is blocked by security settings. Ensure you are using HTTPS.';
                    break;
                case 'AbortError':
                    enhancedMessage += ' - The recording was interrupted unexpectedly.';
                    break;
                case 'OverconstrainedError':
                    enhancedMessage += ' - The requested media constraints cannot be satisfied.';
                    break;
                default:
                    break;
            }
        }

        // Add general guidance
        if (!enhancedMessage.includes('microphone') && !enhancedMessage.includes('Microphone')) {
            enhancedMessage += ' - Check your internet connection and microphone permissions.';
        }

        return enhancedMessage;
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
        return new Promise((resolve, reject) => {
            try {
                this.assemblySocket = new WebSocket(config.websocket_url);

                const timeout = setTimeout(() => {
                    reject(new Error('AssemblyAI WebSocket connection timeout'));
                }, 10000); // 10 second timeout

                this.assemblySocket.onopen = () => {
                    clearTimeout(timeout);
                    this.handleStatusChange('assemblyai_connected');
                    this.sendToAssemblyAI = true;
                    resolve();
                };

                this.assemblySocket.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        const messageType = data.type || data.message_type;

                        // Debug log all message types from v3 
                        console.log(`🔍 AssemblyAI Message Received (${messageType}):`, data);

                        // 1. Handle v3 "Begin" or v2 "SessionBegins"
                        if (messageType === 'Begin' || messageType === 'SessionBegins') {
                            console.log('🎯 AssemblyAI Session Began:', data.id || data.session_id);
                            return;
                        }

                        // 2. Handle v3 "Turn" or v2 "PartialTranscript"/"FinalTranscript"
                        const isV3Turn = messageType === 'Turn';
                        const isV2Transcript = messageType === 'PartialTranscript' || messageType === 'FinalTranscript';

                        if (isV3Turn || isV2Transcript) {
                            // Extract transcript text (v3 uses 'transcript', v2 uses 'text')
                            const transcriptText = isV3Turn ? data.transcript : data.text;

                            // Determine if final (v3 uses 'end_of_turn', v2 uses 'FinalTranscript' type)
                            const isFinal = isV3Turn ? data.end_of_turn : (messageType === 'FinalTranscript');

                            // Skip real-time transcript for Arabic as v3 doesn't support it (shows phonetic Latin garbage)
                            if (this.language && this.language.startsWith('ar')) {
                                return;
                            }

                            console.log(`📝 ${isFinal ? '✅ FINAL' : '⏳ Partial'}: ${transcriptText}`);



                            if (this.onTranscriptUpdate && transcriptText) {
                                const updateData = {
                                    type: 'transcript_update',
                                    payload: {
                                        id: isV3Turn ? `turn-${data.turn_order}` : (data.transcript_id || uuidv4()),
                                        transcript: transcriptText,
                                        is_final: isFinal,
                                        speaker_tag: data.speaker || 1, // Default to speaker 1 (Doctor)
                                        confidence: data.confidence || data.end_of_turn_confidence || 0.8,
                                        start_time: Date.now(),
                                        medical_entities: data.medical_entities || [],
                                        language_code: data.language_code || 'en', // v3 provides detected language
                                        turn_order: isV3Turn ? data.turn_order : null,
                                        is_formatted: isV3Turn ? data.turn_is_formatted : false
                                    }
                                };
                                this.onTranscriptUpdate(updateData);
                            }
                        }
                    } catch (error) {
                        console.error('❌ Error parsing AssemblyAI message:', error, event.data);
                    }
                };

                this.assemblySocket.onclose = (event) => {
                    console.warn(`🔌 AssemblyAI WebSocket Closed: Code ${event.code}, Reason: ${event.reason || 'None provided'}`);
                    this.sendToAssemblyAI = false;
                    if (this.isRecording && !this.isDestroyed) {
                        this.handleStatusChange('assemblyai_disconnected');
                    }
                };

                this.assemblySocket.onerror = (error) => {
                    clearTimeout(timeout);
                    this.sendToAssemblyAI = false;
                    this.handleError('AssemblyAI WebSocket error', error);
                    reject(error);
                };
            } catch (error) {
                reject(error);
            }
        });
    }

    setupLocalRecording() {
        try {
            // Prevent re-initialization if already recording
            if (this.localMediaRecorder && this.localMediaRecorder.state === 'recording') {
                console.log('⚠️ Local recording already active, skipping setup');
                return;
            }

            this.recordedChunks = [];

            let mimeType = 'audio/webm';
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'audio/mp4';
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = '';
            }

            const options = mimeType ? { mimeType } : {};
            this.localMediaRecorder = new MediaRecorder(this.mediaStream, options);

            this.localMediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    this.recordedChunks.push(event.data);
                    console.log(`💾 Chunk recorded: ${event.data.size} bytes, Total chunks: ${this.recordedChunks.length}`);
                }
            };

            this.localMediaRecorder.onstop = () => {
                this.audioBlob = new Blob(this.recordedChunks, {
                    type: this.localMediaRecorder.mimeType || 'audio/webm'
                });
                console.log('💾 Local audio recording captured:', this.audioBlob.size, 'bytes');

                if (window && !window.audioBlob) {
                    window.audioBlob = this.audioBlob;
                } else if (window) {
                    window.audioBlob = this.audioBlob;
                }
            };

            this.localMediaRecorder.start(1000);
            console.log('🎙️ Local recording started with mimeType:', this.localMediaRecorder.mimeType);
        } catch (error) {
            console.warn('⚠️ Failed to setup local recording:', error);
        }
    }

    getAudioBlob() {
        return this.audioBlob;
    }

    async stopRecordingAsync() {
        return new Promise((resolve) => {
            console.log('🛑 stopRecordingAsync called on instance:', this.instanceId);
            console.log('   this.localMediaRecorder:', this.localMediaRecorder);
            console.log('   MediaRecorder state:', this.localMediaRecorder?.state);
            console.log('   recordedChunks length:', this.recordedChunks?.length);
            console.log('   recordedChunks:', this.recordedChunks);
            
            if (this.localMediaRecorder && this.localMediaRecorder.state !== 'inactive') {
                this.localMediaRecorder.onstop = () => {
                    console.log('🎬 MediaRecorder onstop fired, creating blob from', this.recordedChunks.length, 'chunks');
                    
                    this.audioBlob = new Blob(this.recordedChunks, {
                        type: this.localMediaRecorder.mimeType || 'audio/webm'
                    });
                    
                    console.log('💾 Audio blob created:', this.audioBlob.size, 'bytes');

                    if (window) {
                        window.audioBlob = this.audioBlob;
                    }

                    this.isRecording = false;
                    this.handleStatusChange('stopped');
                    resolve(this.audioBlob);
                };
                
                console.log('⏹️ Calling MediaRecorder.stop()');
                this.localMediaRecorder.stop();
            } else {
                console.log('⚠️ MediaRecorder already inactive or not initialized');
                console.log('   Trying to use window.audioBlob or create from chunks...');
                
                // Try to create blob from existing chunks
                if (this.recordedChunks && this.recordedChunks.length > 0) {
                    console.log('💾 Creating blob from', this.recordedChunks.length, 'existing chunks');
                    this.audioBlob = new Blob(this.recordedChunks, { type: 'audio/webm' });
                    console.log('💾 Blob created:', this.audioBlob.size, 'bytes');
                    if (window) {
                        window.audioBlob = this.audioBlob;
                    }
                    this.isRecording = false;
                    this.handleStatusChange('stopped');
                    resolve(this.audioBlob);
                } else {
                    this.isRecording = false;
                    this.handleStatusChange('stopped');
                    resolve(null);
                }
            }
        });
    }

    cleanup() {
        console.log('🧹 Cleanup called, localMediaRecorder state:', this.localMediaRecorder?.state);
        
        // Stop media stream tracks
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
            this.mediaStream = null;
        }

        // Disconnect audio processing
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

        // Close websockets
        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }

        if (this.assemblySocket) {
            this.assemblySocket.close();
            this.assemblySocket = null;
        }

        // CRITICAL: Don't nullify localMediaRecorder - just stop it if needed
        if (this.localMediaRecorder && this.localMediaRecorder.state === 'recording') {
            console.log('⏹️ Stopping MediaRecorder in cleanup');
            this.localMediaRecorder.stop();
        }
        // Don't set to null: this.localMediaRecorder = null;

        this.sendToAssemblyAI = false;
    }
}
