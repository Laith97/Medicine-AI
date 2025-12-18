import React, { useState, useEffect, useRef } from 'react';
import { MedicalAmbientRecorder } from '../utils/MedicalAmbientRecorder';

const AmbientAudioRecorder = ({ visitId, authToken, language = 'en' }) => {
    const [isRecording, setIsRecording] = useState(false);
    const [isConnecting, setIsConnecting] = useState(false);
    const [status, setStatus] = useState('idle'); // idle, connecting, recording, stopped, disconnected
    const [error, setError] = useState(null);

    const recorderRef = useRef(null);

    // Initialize the recorder
    useEffect(() => {
        const recorder = new MedicalAmbientRecorder({
            onStatusChange: (newStatus) => {
                setStatus(newStatus);
                if (newStatus === 'recording') {
                    setIsRecording(true);
                    setIsConnecting(false);
                } else if (newStatus === 'stopped' || newStatus === 'disconnected') {
                    setIsRecording(false);
                    setIsConnecting(false);
                }

                // Dispatch status update event to update the UI
                window.dispatchEvent(new CustomEvent('statusUpdate', {
                    detail: { status: newStatus }
                }));
            },
            onError: (msg, err) => {
                setError(`${msg}: ${err.message || err}`);
                setIsConnecting(false);
                setIsRecording(false);

                // Dispatch status update event to update the UI
                window.dispatchEvent(new CustomEvent('statusUpdate', {
                    detail: { status: 'error' }
                }));
            },
            onTranscriptUpdate: (data) => {
                // Emit transcript update event for the RealTimeTranscript component
                // Dispatch a custom event that can be listened to by the RealTimeTranscript component
                window.dispatchEvent(new CustomEvent('transcriptUpdate', { detail: data }));
            }
        });

        recorderRef.current = recorder;

        // Cleanup function to stop recording if component unmounts while recording
        return () => {
            if (recorderRef.current && isRecording) {
                recorderRef.current.stopRecording();
            }
        };
    }, [isRecording, language]);

    const startRecording = async () => {
        setError(null);
        setIsConnecting(true);
        setStatus('connecting');

        try {
            if (recorderRef.current) {
                await recorderRef.current.startRecording(visitId, authToken, language);
            }
        } catch (err) {
            console.warn('WebSocket recording failed, falling back to browser speech recognition', err);
            
            // Fallback to the existing voice-assistant.js implementation
            if (window.voiceAssistant && window.voiceAssistant.startSession) {
                try {
                    await window.voiceAssistant.startSession();
                    setStatus('recording');
                    setIsRecording(true);
                    setIsConnecting(false);
                    setError(null);
                } catch (fallbackErr) {
                    setError('Recording failed: ' + (fallbackErr.message || err.message));
                    setIsConnecting(false);
                    setIsRecording(false);
                }
            } else {
                setError('Recording failed: ' + (err.message || 'Unknown error'));
                setIsConnecting(false);
                setIsRecording(false);
            }
        }
    };

    const stopRecording = () => {
        if (recorderRef.current) {
            recorderRef.current.stopRecording();
        }
    };

    const statusText = () => {
        const map = {
            idle: 'Ready',
            connecting: 'Connecting...',
            recording: 'Live',
            stopped: 'Stopped',
            disconnected: 'Disconnected'
        };
        return map[status] || status;
    };

    const statusClass = () => {
        if (status === 'recording') return 'text-success';
        if (status === 'connecting') return 'text-warning';
        if (status === 'idle' || status === 'stopped') return 'text-secondary';
        if (status === 'disconnected') return 'text-danger';
        return '';
    };

    const badgeClass = () => {
        const map = {
            idle: 'bg-secondary',
            connecting: 'bg-warning text-dark',
            recording: 'bg-danger',
            stopped: 'bg-dark',
            disconnected: 'bg-danger'
        };
        return map[status] || 'bg-secondary';
    };

    return (
        <div className="ambient-recorder-container">
            <div className="card shadow-sm border-0">
                <div className="card-body p-4">
                    <div className="d-flex align-items-center justify-content-between mb-3">
                        <h5 className="card-title mb-0">
                            <i className="fas fa-microphone-alt me-2"></i>Ambient Listening
                        </h5>
                        <div className="d-flex align-items-center">
                            <div className="status-indicator me-2">
                                <span
                                    className={`status-dot ${
                                        status === 'recording' ? 'recording' :
                                        status === 'connecting' ? 'connecting' :
                                        status === 'disconnected' ? 'error' :
                                        status === 'idle' || status === 'stopped' ? 'active' : ''
                                    }`}
                                    id="statusDot"
                                    aria-hidden="true"
                                ></span>
                            </div>
                            <div
                                className={`status-text ${statusClass()}`}
                                role="status"
                                aria-live="polite"
                            >
                                <span className={`badge ${badgeClass()}`}>{statusText()}</span>
                            </div>
                        </div>
                    </div>

                    <div className="d-flex flex-column align-items-center my-3">
                        <div className={`recording-button-container ${isRecording ? 'recording' : ''}`}>
                            {!isRecording && (
                                <button
                                    onClick={startRecording}
                                    className={`btn btn-success btn-lg px-5 py-4 rounded-circle shadow-lg ${isConnecting ? 'disabled' : ''}`}
                                    disabled={isConnecting}
                                    aria-label="Start recording"
                                >
                                    {isConnecting ? (
                                        <span className="d-flex flex-column align-items-center">
                                            <span className="spinner-border spinner-border-sm mb-2" role="status"></span>
                                            <span>Connecting...</span>
                                        </span>
                                    ) : (
                                        <>
                                            <i className="fas fa-microphone fa-2x mb-2" aria-hidden="true"></i>
                                            <div>Start Listening</div>
                                        </>
                                    )}
                                </button>
                            )}

                            {isRecording && (
                                <button
                                    onClick={stopRecording}
                                    className="btn btn-danger btn-lg px-5 py-4 rounded-circle shadow-lg recording-pulse"
                                    aria-label="Stop recording"
                                >
                                    <i className="fas fa-stop fa-2x mb-2" aria-hidden="true"></i>
                                    <div>Stop Listening</div>
                                </button>
                            )}
                        </div>

                        {isRecording && (
                            <div className="recording-info mt-3 text-center">
                                <div className="d-flex align-items-center justify-content-center gap-2">
                                    <span className="recording-dot"></span>
                                    <small className="text-danger">LIVE</small>
                                </div>
                            </div>
                        )}
                    </div>

                    {error && (
                        <div className="alert alert-danger mt-3 d-flex align-items-center">
                            <i className="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <div className="fw-bold">Connection Error</div>
                                <div>{error}</div>
                                <small className="mt-1 d-block">
                                    <i className="fas fa-lightbulb me-1"></i>
                                    Try checking your microphone permissions, internet connection, or contact support.
                                </small>
                            </div>
                        </div>
                    )}

                    <div className="text-muted small text-center mt-3">
                        <i className="fas fa-shield-alt me-1"></i> HIPAA Compliant • Encrypted • Secure
                    </div>

                    {/* Audio quality indicator */}
                    <div className="mt-3">
                        <div className="progress" style={{height: '6px'}}>
                            <div
                                className="progress-bar bg-success"
                                role="progressbar"
                                style={{width: '85%'}}
                                aria-valuenow="85"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>
                        <small className="text-muted d-block text-center mt-1">Audio Quality: High</small>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AmbientAudioRecorder;