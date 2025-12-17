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
            },
            onError: (msg, err) => {
                setError(`${msg}: ${err.message || err}`);
                setIsConnecting(false);
                setIsRecording(false);
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
            <div className="card shadow-sm">
                <div className="card-body">
                    <div className="d-flex align-items-center justify-content-between mb-3">
                        <h5 className="card-title mb-0">
                            <i className="fas fa-microphone-alt me-2"></i>Ambient Listening
                        </h5>
                        <div
                            className={`status-indicator ${statusClass()}`}
                            role="status"
                            aria-live="polite"
                        >
                            <span className={`badge ${badgeClass()}`}>{statusText()}</span>
                        </div>
                    </div>

                    <div className="controls text-center my-4">
                        {!isRecording && (
                            <button
                                onClick={startRecording}
                                className="btn btn-primary btn-lg rounded-circle p-4 shadow-lg"
                                disabled={isConnecting}
                                aria-label="Start recording"
                            >
                                <i className="fas fa-play fa-2x" aria-hidden="true"></i>
                            </button>
                        )}

                        {isRecording && (
                            <button
                                onClick={stopRecording}
                                className="btn btn-danger btn-lg rounded-circle p-4 shadow-lg recording-pulse"
                                aria-label="Stop recording"
                            >
                                <i className="fas fa-stop fa-2x" aria-hidden="true"></i>
                            </button>
                        )}
                    </div>

                    {error && (
                        <div className="alert alert-danger mt-3">
                            {error}
                        </div>
                    )}

                    <div className="text-muted small text-center">
                        <i className="fas fa-shield-alt me-1"></i> HIPAA Compliant • Encrypted
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AmbientAudioRecorder;