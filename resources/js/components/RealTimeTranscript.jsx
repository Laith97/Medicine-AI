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
            // Emit event equivalent
            // onSegmentAdded(segment); // Uncomment if you have a way to handle this event

            scrollToBottom();
        } else {
            setInterimTranscript(payload.transcript);
            setIsProcessing(true);
            scrollToBottom();
        }
    };

    const formatSpeaker = (speakerTag) => {
        return speakerMap[speakerTag] || `Speaker ${speakerTag}`;
    };

    const getSpeakerClass = (speakerTag) => {
        return speakerTag === 1 ? 'text-end' : 'text-start';
    };

    const getSpeakerBadgeClass = (speakerTag) => {
        return speakerTag === 1 ? 'bg-primary' : 'bg-success';
    };

    const getMessageClass = (speakerTag) => {
        return speakerTag === 1 ? 'bg-primary-subtle text-primary-emphasis' : 'bg-success-subtle text-success-emphasis';
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

    const scrollToBottom = () => {
        if (transcriptBodyRef.current) {
            requestAnimationFrame(() => {
                transcriptBodyRef.current.scrollTop = transcriptBodyRef.current.scrollHeight;
            });
        }
    };

    return (
        <div className="transcript-container">
            <div className="card shadow-sm h-100">
                <div className="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Live Transcript</h6>
                    {isProcessing && <div className="badge bg-info text-dark">Processing...</div>}
                </div>
                <div
                    className="card-body transcript-body overflow-auto"
                    ref={transcriptBodyRef}
                    style={{ maxHeight: '500px' }}
                    role="log"
                    aria-live="polite"
                    aria-label="Real-time transcript"
                >
                    {conversation.length === 0 && (
                        <div className="text-center text-muted py-5">
                            <i className="fas fa-comments fa-3x mb-3 opacity-25"></i>
                            <p>Conversation will appear here...</p>
                        </div>
                    )}

                    {conversation.map((segment) => (
                        <div 
                            key={segment.id} 
                            className={`message-segment mb-3 ${getSpeakerClass(segment.speaker)}`}
                        >
                            <div className="d-flex align-items-baseline mb-1">
                                <span className={`speaker-label badge me-2 ${getSpeakerBadgeClass(segment.speaker)}`}>
                                    {formatSpeaker(segment.speaker)}
                                </span>
                                <small className="text-muted">{formatTime(segment.timestamp)}</small>
                            </div>
                            <div 
                                className={`message-content p-3 rounded ${getMessageClass(segment.speaker)}`}
                                dangerouslySetInnerHTML={{ __html: highlightEntities(segment.text, segment.medical_entities) }}
                            />
                        </div>
                    ))}

                    {interimTranscript && (
                        <div className="interim-segment mb-3 opacity-75">
                            <div className="d-flex align-items-baseline mb-1">
                                <span className="speaker-label badge bg-secondary me-2">...</span>
                            </div>
                            <div className="message-content p-3 rounded bg-light border border-secondary border-dashed">
                                <p className="mb-0 fst-italic">{interimTranscript}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default RealTimeTranscript;