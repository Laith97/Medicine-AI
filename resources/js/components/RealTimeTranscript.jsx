import React, { useState, useEffect, useRef, useCallback } from 'react';
import { v4 as uuidv4 } from 'uuid';
import DOMPurify from 'dompurify';

const RealTimeTranscript = () => {
    const [conversation, setConversation] = useState([]);
    const [interimTranscript, setInterimTranscript] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);

    const transcriptBodyRef = useRef(null);

    // Limit conversation history to prevent memory issues
    const MAX_CONVERSATION_LENGTH = 100; // configurable

    const speakerMap = {
        1: 'Doctor',
        2: 'Patient'
    };

    const handleWebSocketMessage = (data) => {
        if (data.type === 'transcript_update') {
            processTranscriptUpdate(data.payload);
        } else if (data.type === 'speaker_change') {
            // Handle explicit speaker change events if needed
        }
    };

    // Listen for transcript updates from the AmbientAudioRecorder component
    useEffect(() => {
        const handleTranscriptUpdate = (event) => {
            const data = event.detail;
            handleWebSocketMessage(data);
        };

        window.addEventListener('transcriptUpdate', handleTranscriptUpdate);

        // Cleanup event listener on component unmount
        return () => {
            window.removeEventListener('transcriptUpdate', handleTranscriptUpdate);
        };
    }, []);

    const processTranscriptUpdate = (payload) => {
        if (payload.is_final) {
            setInterimTranscript('');

            const segment = {
                id: uuidv4(),
                speaker: payload.speaker_tag || 1, // Default to Doctor if unknown
                text: payload.transcript,
                timestamp: payload.start_time || Date.now(),
                medical_entities: payload.medical_entities || []
            };

            // Limit conversation history to prevent memory issues
            setConversation(prev => {
                const newConvo = [...prev, segment];
                return newConvo.length > MAX_CONVERSATION_LENGTH
                    ? newConvo.slice(-MAX_CONVERSATION_LENGTH)
                    : newConvo;
            });
            
            // Announce for accessibility
            announceTranscriptUpdate(payload.transcript, true);
            
            // Emit event equivalent
            // onSegmentAdded(segment); // Uncomment if you have a way to handle this event

            scrollToBottom();
        } else {
            setInterimTranscript(payload.transcript);
            setIsProcessing(true);
            // Announce interim transcript for accessibility
            announceTranscriptUpdate(payload.transcript, false);
            scrollToBottom();
        }
    };

    const formatSpeaker = (speakerTag) => {
        return speakerMap[speakerTag] || `Speaker ${speakerTag}`;
    };

    const scrollToBottom = () => {
        if (transcriptBodyRef.current) {
            // Smooth scroll to bottom
            transcriptBodyRef.current.scrollTo({
                top: transcriptBodyRef.current.scrollHeight,
                behavior: 'smooth'
            });
        }
    };

    // Function to announce transcript updates for screen readers
    const announceTranscriptUpdate = (text, isFinal = false) => {
        // Create a live region for screen readers
        let liveRegion = document.getElementById('transcript-live-region');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.setAttribute('id', 'transcript-live-region');
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'visually-hidden';
            document.body.appendChild(liveRegion);
        }
        
        liveRegion.textContent = isFinal ? `New transcript: ${text}` : `Transcribing: ${text}`;
    };

    const formatTime = (timestamp) => {
        return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    // Helper function to escape special regex characters
    const escapeRegExp = (string) => {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    };

    const highlightEntities = useCallback((text, entities) => {
        if (!entities || entities.length === 0) return text;

        // Create a single regex pattern for all entities
        const sortedEntities = [...entities].sort((a, b) => b.length - a.length);
        const pattern = sortedEntities.map(escapeRegExp).join('|');
        const regex = new RegExp(`\\b(${pattern})\\b`, 'gi');

        return DOMPurify.sanitize(text.replace(regex, '<span class="fw-bold text-danger" title="Medical Entity">$1</span>'));
    }, []);

    return (
        <div className="transcript-container" tabIndex="0" role="region" aria-label="Live transcript container">
            <div className="card shadow-sm h-100 border-0">
                <div className="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">
                        <i className="fas fa-comments me-2"></i>
                        Live Transcript
                    </h6>
                    {isProcessing && <div className="badge bg-warning text-dark">Processing...</div>}
                </div>
                <div
                    className="card-body transcript-body overflow-auto p-0"
                    ref={transcriptBodyRef}
                    style={{ maxHeight: '500px' }}
                    role="log"
                    aria-live="polite"
                    aria-label="Real-time transcript"
                >
                    {conversation.length === 0 && (
                        <div className="text-center text-muted py-5">
                            <i className="fas fa-microphone-alt fa-3x mb-3 opacity-25"></i>
                            <p className="mb-0">Start ambient listening to see transcription here...</p>
                            <small className="text-muted">Transcription will appear in real-time</small>
                        </div>
                    )}

                    <div className="p-3">
                        {conversation.map((segment) => (
                            <div
                                key={segment.id}
                                className={`speaker-segment mb-3 p-3 rounded-2 ${
                                    segment.speaker === 1 ? 'speaker-doctor bg-primary-subtle' :
                                    segment.speaker === 2 ? 'speaker-patient bg-success-subtle' :
                                    'speaker-unknown bg-light'
                                }`}
                            >
                                <div className="d-flex justify-content-between align-items-start">
                                    <div className="d-flex align-items-center">
                                        <span className={`speaker-label me-2 ${
                                            segment.speaker === 1 ? 'bg-primary text-white' :
                                            segment.speaker === 2 ? 'bg-success text-white' :
                                            'bg-secondary text-white'
                                        }`}>
                                            {formatSpeaker(segment.speaker)}
                                        </span>
                                    </div>
                                    <small className="text-muted">{formatTime(segment.timestamp)}</small>
                                </div>
                                <div
                                    className="speaker-text mt-2"
                                    dangerouslySetInnerHTML={{ __html: highlightEntities(segment.text, segment.medical_entities) }}
                                />
                            </div>
                        ))}
                    </div>

                    {interimTranscript && (
                        <div className="interim-segment p-3 bg-light border-start border-warning border-3">
                            <div className="d-flex align-items-baseline mb-1">
                                <span className="speaker-label badge bg-warning text-dark me-2">LIVE</span>
                                <small className="text-muted">Now speaking...</small>
                            </div>
                            <div className="message-content p-3 bg-white rounded">
                                <p className="mb-0 fst-italic text-muted">{interimTranscript}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default RealTimeTranscript;