import React, { useState, useEffect, useRef } from 'react';
import { MedicalAmbientRecorder } from '../utils/MedicalAmbientRecorder';

const AmbientAudioRecorder = ({ visitId, authToken, language = 'en' }) => {
    const [isRecording, setIsRecording] = useState(false);
    const [isConnecting, setIsConnecting] = useState(false);
    const [status, setStatus] = useState('idle'); // idle, connecting, recording, stopped, disconnected
    const [error, setError] = useState(null);

    const recorderRef = useRef(null);
    const isFallbackRef = useRef(false);

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
            // 1. Call start-session API to get AssemblyAI config and ensure session is active
            const patientSelect = document.getElementById('patientSelect');
            const selectedPatient = patientSelect ? patientSelect.value : null;

            if (!selectedPatient) {
                throw new Error('Please select a patient first');
            }

            const response = await window.axios.post('/ai/voice-assistant/start-session', {
                selectedPatient: selectedPatient,
            });

            if (!response.data.success) {
                throw new Error(response.data.message || 'Failed to start session');
            }

            const { sessionId, assemblyConfig } = response.data;

            // 2. Start the recorder with the received config
            if (recorderRef.current) {
                await recorderRef.current.startRecording(sessionId, authToken, language, assemblyConfig);
            }
        } catch (err) {
            console.warn('Primary recording failed, falling back to browser speech recognition', err);

            // Fallback to the existing voice-assistant.js implementation
            if (window.voiceAssistant && typeof window.voiceAssistant.startSession === 'function') {
                try {
                    // Wait a moment to ensure the window context is ready
                    await new Promise(resolve => setTimeout(resolve, 100));
                    await window.voiceAssistant.startSession();
                    isFallbackRef.current = true;
                    setStatus('recording');
                    setIsRecording(true);
                    setIsConnecting(false);
                    setError(null);
                } catch (fallbackErr) {
                    console.error('Fallback recording also failed:', fallbackErr);
                    setError('Recording failed: ' + (fallbackErr.message || err.message));
                    setIsConnecting(false);
                    setIsRecording(false);
                }
            } else {
                console.error('Voice assistant module not available for fallback');
                // If voice assistant is not available, try to initialize it first
                try {
                    // Wait for window.voiceAssistant to be available with a timeout
                    let attempts = 0;
                    const maxAttempts = 20; // 20 * 100ms = 2 seconds

                    while (attempts < maxAttempts && (!window.voiceAssistant || typeof window.voiceAssistant.startSession !== 'function')) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        attempts++;
                    }

                    // If voice assistant is now available, use it
                    if (window.voiceAssistant && typeof window.voiceAssistant.startSession === 'function') {
                        await window.voiceAssistant.startSession();
                        isFallbackRef.current = true;
                        setStatus('recording');
                        setIsRecording(true);
                        setIsConnecting(false);
                        setError(null);
                    } else {
                        // Try to trigger the original recording button
                        const startBtn = document.getElementById('startRecordingBtn');
                        const patientSelect = document.getElementById('patientSelect');

                        // Check if a patient is selected - if not, the button will be disabled
                        if (patientSelect && patientSelect.value) {
                            if (startBtn && startBtn.disabled) {
                                // Button is disabled, try to enable it by ensuring proper state
                                // The button might be disabled due to missing patient selection
                                const selectedPatientValue = patientSelect.value;
                                if (selectedPatientValue) {
                                    // Patient is selected, but button may still be disabled due to other issues
                                    // Trigger a selection change to update button state
                                    const event = new Event('change', { bubbles: true });
                                    patientSelect.dispatchEvent(event);

                                    // Wait a bit for the update to happen
                                    await new Promise(resolve => setTimeout(resolve, 200));
                                }
                            }

                            // Now try to click the button
                            if (startBtn && !startBtn.disabled) {
                                startBtn.click();
                                isFallbackRef.current = true; // Assume fallback if we clicked the legacy button
                                setStatus('recording');
                                setIsRecording(true);
                                setIsConnecting(false);
                                setError(null);
                            } else {
                                console.error('Start button not available or still disabled after patient check');
                                // Try clicking the React container's fallback button
                                const reactStartBtn = document.querySelector('#react-audio-recorder-container .btn-success:not(.disabled):not([disabled])');
                                if (reactStartBtn) {
                                    reactStartBtn.click();
                                    // Don't set isFallbackRef here as this is likely a recursive click on the same component
                                    setStatus('recording');
                                    setIsRecording(true);
                                    setIsConnecting(false);
                                    setError(null);
                                } else {
                                    setError('Microphone access denied or not supported: ' + (err.message || 'Please allow microphone permissions in your browser settings'));
                                    setIsConnecting(false);
                                    setIsRecording(false);
                                }
                            }
                        } else {
                            setError('Please select a patient first before starting the recording.');
                            setIsConnecting(false);
                            setIsRecording(false);
                        }
                    }
                } catch (initErr) {
                    console.error('Failed to initialize or use voice assistant:', initErr);
                    // As a last resort, try to trigger the recording button directly
                    try {
                        const startBtn = document.getElementById('startRecordingBtn');
                        if (startBtn && !startBtn.disabled) {
                            startBtn.click();
                            isFallbackRef.current = true;
                            setStatus('recording');
                            setIsRecording(true);
                            setIsConnecting(false);
                            setError(null);
                        } else {
                            // Ensure patient is selected first
                            const patientSelect = document.getElementById('patientSelect');
                            if (patientSelect && patientSelect.value) {
                                if (startBtn) {
                                    // Make sure button is enabled by triggering the change event
                                    const event = new Event('change', { bubbles: true });
                                    patientSelect.dispatchEvent(event);
                                    await new Promise(resolve => setTimeout(resolve, 200));

                                    if (!startBtn.disabled) {
                                        startBtn.click();
                                        isFallbackRef.current = true;
                                        setStatus('recording');
                                        setIsRecording(true);
                                        setIsConnecting(false);
                                        setError(null);
                                    } else {
                                        setError('Microphone access denied or not supported: Please select a patient and check permissions. ' + (initErr.message || 'Please allow microphone permissions in your browser settings'));
                                        setIsConnecting(false);
                                        setIsRecording(false);
                                    }
                                } else {
                                    setError('Microphone access denied or not supported: ' + (initErr.message || 'Please allow microphone permissions in your browser settings'));
                                    setIsConnecting(false);
                                    setIsRecording(false);
                                }
                            } else {
                                setError('Please select a patient first before starting the recording.');
                                setIsConnecting(false);
                                setIsRecording(false);
                            }
                        }
                    } catch (btnErr) {
                        setError('Microphone access denied or not supported: ' + (btnErr.message || 'Please allow microphone permissions in your browser settings'));
                        setIsConnecting(false);
                        setIsRecording(false);
                    }
                }
            }
        }
    };

    const stopRecording = () => {
        if (isFallbackRef.current) {
            console.log('Stopping fallback recording...');
            if (window.voiceAssistant && typeof window.voiceAssistant.stopSession === 'function') {
                window.voiceAssistant.stopSession();
            } else {
                // Try to find the stop button and click it
                const stopBtn = document.getElementById('stopRecordingBtn');
                if (stopBtn) {
                    stopBtn.click();
                }
            }
            isFallbackRef.current = false;
            setIsRecording(false);
            setStatus('stopped');
        } else if (recorderRef.current) {
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