export class MedicalAmbientRecorder {
    constructor(config = {}) {
        this.audioContext = null;
        this.mediaStream = null;
        this.websocket = null;
        this.processor = null;
        this.source = null;
        this.isRecording = false;
        this.chunkSequence = 0;
        
        this.config = {
            sampleRate: 16000,
            bufferSize: 4096,
            ...config
        };

        this.onTranscriptUpdate = null;
        this.onStatusChange = null;
        this.onError = null;
    }

    async startRecording(visitId, authToken) {
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

            // 2. Create WebSocket connection
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/medical-audio?token=${authToken}&visit_id=${visitId}`;
            
            this.websocket = new WebSocket(wsUrl);
            this.configureWebSocketHandlers();

            // Wait for connection to open before starting audio
            await new Promise((resolve, reject) => {
                this.websocket.onopen = () => {
                    this.handleStatusChange('connected');
                    resolve();
                };
                this.websocket.onerror = (error) => {
                    reject(error);
                };
            });

            // 3. Create audio processing pipeline
            await this.createAudioProcessingPipeline();
            
            this.isRecording = true;
            this.handleStatusChange('recording');
            
        } catch (error) {
            this.handleError('Failed to start recording', error);
            throw error;
        }
    }

    stopRecording() {
        this.isRecording = false;
        
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

        if (this.audioContext) {
            this.audioContext.close();
            this.audioContext = null;
        }

        if (this.websocket) {
            this.websocket.close();
            this.websocket = null;
        }

        this.handleStatusChange('stopped');
    }

    createAudioProcessingPipeline() {
        this.audioContext = new (window.AudioContext || window.webkitAudioContext)({ 
            sampleRate: this.config.sampleRate 
        });
        
        this.source = this.audioContext.createMediaStreamSource(this.mediaStream);
        
        // Create processor for real-time audio chunking
        // bufferSize, inputChannels, outputChannels
        this.processor = this.audioContext.createScriptProcessor(this.config.bufferSize, 1, 1);
        
        this.processor.onaudioprocess = (e) => {
            if (!this.isRecording || this.websocket.readyState !== WebSocket.OPEN) return;
            
            const audioData = e.inputBuffer.getChannelData(0);
            const pcmData = this.convertFloat32ToInt16(audioData);
            
            // Send with metadata for speaker tracking
            this.websocket.send(JSON.stringify({
                type: 'audio_chunk',
                data: Array.from(pcmData),
                timestamp: Date.now(),
                sequence: this.chunkSequence++
            }));
        };

        this.source.connect(this.processor);
        this.processor.connect(this.audioContext.destination);
    }

    convertFloat32ToInt16(float32Array) {
        let l = float32Array.length;
        const buffer = new Int16Array(l);
        while (l--) {
            buffer[l] = Math.min(1, Math.max(-1, float32Array[l])) * 0x7FFF;
        }
        return buffer;
    }

    configureWebSocketHandlers() {
        this.websocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);

                // Always call the transcript update handler
                if (this.onTranscriptUpdate) {
                    this.onTranscriptUpdate(data);
                }

                // Handle different message types based on their type
                if (data.type === 'transcript_update') {
                    // Process transcript update as needed (could also process internally)
                    console.log('Received transcript update:', data.payload);
                }
            } catch (e) {
                console.error('Error parsing WebSocket message:', e);
                this.handleError('Error parsing WebSocket message', e);
            }
        };

        this.websocket.onclose = () => {
            if (this.isRecording) {
                this.handleStatusChange('disconnected');
                // Implement reconnection logic here if needed
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
}
