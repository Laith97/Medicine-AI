import React, { useState, useEffect, useRef } from 'react';
import { MedicalAmbientRecorder } from '../utils/MedicalAmbientRecorder';

const AmbientAudioRecorder = ({ visitId, authToken, language = 'en' }) => {
    const [isRecording, setIsRecording] = useState(false);
    const [isConnecting, setIsConnecting] = useState(false);
    const [status, setStatus] = useState('idle');
    const [error, setError] = useState(null);
    const [sessionId, setSessionId] = useState(null);

    const recorderRef = useRef(null);
    const isFallbackRef = useRef(false);

    // Initialize the recorder ONCE
    useEffect(() => {
        // Only create if we don't have one
        if (recorderRef.current) {
            console.log('⚠️ Recorder already exists, skipping creation');
            return;
        }

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

        // Sync initial language if available
        if (typeof window.setCurrentLanguage === 'function') {
            window.setCurrentLanguage(language);
        }

        // Cleanup function to stop recording if component unmounts while recording
        return () => {
            if (recorderRef.current && isRecording) {
                console.log('🧹 Component unmounting, stopping recorder');
                recorderRef.current.stopRecording();
            }
        };
    }, []); // Empty dependency array - only run once on mount

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

            const response = await window.axios.post('/ai/ambient-listening/start-session', {
                selectedPatient: selectedPatient,
                language: language
            });

            if (!response.data.success) {
                throw new Error(response.data.message || 'Failed to start session');
            }

            const { sessionId, assemblyConfig } = response.data;

            // Store session ID for later use
            setSessionId(sessionId);

            // Synchronize sessionId with the legacy script
            if (typeof window.setSessionId === 'function') {
                window.setSessionId(sessionId);
            }
            window.sessionId = sessionId;

            // 2. Start the recorder with the received config
            if (recorderRef.current) {
                await recorderRef.current.startRecording(sessionId, authToken, language, assemblyConfig);
            }
        } catch (err) {

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
                    setError('Recording failed: ' + (fallbackErr.message || err.message));
                    setIsConnecting(false);
                    setIsRecording(false);
                }
            } else {
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

    const stopRecording = async () => {
        if (isFallbackRef.current) {
            if (window.voiceAssistant && typeof window.voiceAssistant.stopSession === 'function') {
                window.voiceAssistant.stopSession();
            } else {
                const stopBtn = document.getElementById('stopRecordingBtn');
                if (stopBtn) {
                    stopBtn.click();
                }
            }
            isFallbackRef.current = false;
            setIsRecording(false);
            setStatus('stopped');
        } else if (recorderRef.current) {
            console.log('⏹️ Stopping recording via MedicalAmbientRecorder...');
            const audioBlob = await recorderRef.current.stopRecordingAsync();
            console.log('✅ Recording stopped, audio blob received:', audioBlob ? audioBlob.size + ' bytes' : 'null');

            // Clean up after getting the blob
            if (recorderRef.current) {
                recorderRef.current.cleanup();
            }

            // Trigger server-side processing
            try {
                if (audioBlob && audioBlob.size > 0) {
                    console.log('🚀 Triggering server-side processing for audio blob...');

                    // Show loading spinner
                    window.dispatchEvent(new CustomEvent('showTranscriptLoading'));

                    const currentSessionId = sessionId || window.sessionId || (typeof window.getSessionId === 'function' ? window.getSessionId() : null);
                    console.log('   Using session ID:', currentSessionId);

                    if (currentSessionId) {
                        const formData = new FormData();
                        formData.append('audio_file', audioBlob, 'recording.webm');
                        formData.append('session_id', currentSessionId);
                        formData.append('language', language);
                        formData.append('has_audio_recording', 'true');

                        const response = await window.axios.post('/ai/ambient-listening/process-audio-server', formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        });

                        if (response.data.success) {
                            console.log('✅ Server-side processing completed successfully');
                            console.log('📥 Server response data:', response.data);
                            console.log('📥 Server transcript received:', response.data.improved_transcription);
                            console.log('📥 Transcript length:', response.data.improved_transcription ? response.data.improved_transcription.length : 0);

                            // Hide loading spinner
                            window.dispatchEvent(new CustomEvent('hideTranscriptLoading'));

                            if (response.data.improved_transcription) {
                                console.log('🎯 Dispatching serverTranscriptReady event');
                                window.dispatchEvent(new CustomEvent('serverTranscriptReady', {
                                    detail: {
                                        transcription: response.data.improved_transcription,
                                        extractedData: response.data.server_extracted_data,
                                        speakers: response.data.speakers,
                                        corrected_segments: response.data.corrected_segments || null
                                    }
                                }));
                            } else {
                                console.warn('⚠️ No improved_transcription in response');
                            }
                        } else {
                            console.error('❌ Server-side processing failed:', response.data.message);
                            // Hide loading on error
                            window.dispatchEvent(new CustomEvent('hideTranscriptLoading'));
                        }
                    } else {
                        console.warn('⚠️ No session ID available for server-side processing');
                    }
                } else {
                    console.warn('⚠️ No audio blob available for server-side processing');
                }
            } catch (error) {
                console.error('❌ Error during server-side processing:', error);
                // Hide loading on error
                window.dispatchEvent(new CustomEvent('hideTranscriptLoading'));
            }
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
        <div className="ambient-recorder-container d-inline-block">
            <div
                className={`d-inline-flex align-items-center bg-white rounded-pill shadow-sm border transaction-all ${isRecording ? 'border-danger' : 'border-light'}`}
                style={{
                    padding: '6px 16px',
                    transition: 'all 0.3s ease',
                    minWidth: '200px'
                }}
            >
                {/* Status Indicator Section */}
                <div className="d-flex align-items-center pe-3 border-end">
                    <span
                        className="status-dot me-2"
                        style={{
                            width: '8px',
                            height: '8px',
                            borderRadius: '50%',
                            backgroundColor: status === 'recording' ? '#dc3545' :
                                status === 'connecting' ? '#ffc107' : '#adb5bd',
                            boxShadow: status === 'recording' ? '0 0 0 2px rgba(220, 53, 69, 0.2)' : 'none',
                            animation: status === 'recording' ? 'pulse 1.5s infinite' : 'none'
                        }}
                    ></span>
                    <span
                        className={`fw-bold ${status === 'recording' ? 'text-danger' : 'text-secondary'}`}
                        style={{ fontSize: '0.85rem' }}
                    >
                        {status === 'idle' ? 'Ready' :
                            status === 'recording' ? 'Listening' :
                                status === 'connecting' ? 'Connecting...' : status}
                    </span>
                </div>

                {/* Controls Section */}
                <div className="ps-3">
                    {!isRecording ? (
                        <button
                            onClick={startRecording}
                            className="btn btn-link text-decoration-none p-0 text-dark fw-bold d-flex align-items-center"
                            disabled={isConnecting}
                            style={{ fontSize: '0.9rem' }}
                        >
                            {isConnecting ? (
                                <i className="fas fa-spinner fa-spin text-secondary"></i>
                            ) : (
                                <>
                                    <div
                                        className="d-flex align-items-center justify-content-center bg-light rounded-circle me-2"
                                        style={{ width: '28px', height: '28px' }}
                                    >
                                        <i className="fas fa-microphone text-primary" style={{ fontSize: '0.8rem' }}></i>
                                    </div>
                                    <span>Start</span>
                                </>
                            )}
                        </button>
                    ) : (
                        <button
                            onClick={stopRecording}
                            className="btn btn-link text-decoration-none p-0 text-danger fw-bold d-flex align-items-center"
                            style={{ fontSize: '0.9rem' }}
                        >
                            <div
                                className="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle me-2"
                                style={{ width: '28px', height: '28px' }}
                            >
                                <i className="fas fa-stop text-danger" style={{ fontSize: '0.8rem' }}></i>
                            </div>
                            <span>Stop</span>
                        </button>
                    )}
                </div>
            </div>

            {/* Error Message Toast-like display */}
            {error && (
                <div
                    className="alert alert-danger py-1 px-3 mt-2 mb-0 rounded-3 shadow-sm"
                    style={{ fontSize: '0.8rem', maxWidth: '300px' }}
                >
                    <i className="fas fa-exclamation-circle me-1"></i>{error}
                </div>
            )}

            <style>{`
                @keyframes pulse {
                    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
                    70% { box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
                }
                .ambient-recorder-container .btn-link:hover {
                    opacity: 0.8;
                }
            `}</style>
        </div>
    );
};

export default AmbientAudioRecorder;