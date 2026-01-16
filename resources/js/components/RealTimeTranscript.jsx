import React, { useState, useEffect, useRef, useCallback } from 'react';
import { v4 as uuidv4 } from 'uuid';
import DOMPurify from 'dompurify';

const RealTimeTranscript = ({ language }) => {
    const [conversation, setConversation] = useState([]);
    const currentLang = language || (window.currentLanguage || 'en');
    const [interimTranscript, setInterimTranscript] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [isLoadingServerTranscript, setIsLoadingServerTranscript] = useState(false);

    const transcriptBodyRef = useRef(null);
    const scrollingContainerRef = useRef(null);

    const MAX_CONVERSATION_LENGTH = 100;

    const speakerMap = {
        1: 'Speaker 1',
        2: 'Speaker 2'
    };

    const handleWebSocketMessage = (data) => {
        if (data.type === 'transcript_update') {
            processTranscriptUpdate(data.payload);
        } else if (data.type === 'speaker_change') {
            // Handle explicit speaker change events if needed
        }
    };

    // Find the scrolling container (the parent with overflow-y: auto)
    useEffect(() => {
        const findScrollingContainer = () => {
            let element = transcriptBodyRef.current;
            while (element && element.parentElement) {
                if (getComputedStyle(element).overflowY === 'auto' || element.style.overflowY === 'auto') {
                    scrollingContainerRef.current = element;
                    break;
                }
                element = element.parentElement;
            }
        };

        if (transcriptBodyRef.current) {
            findScrollingContainer();
        }
    }, []);

    // Listen for transcript updates from the AmbientAudioRecorder component
    useEffect(() => {
        const handleTranscriptUpdate = (event) => {
            const data = event.detail;
            handleWebSocketMessage(data);
        };

        const handleServerTranscript = (event) => {
            const { transcription, extractedData, speakers } = event.detail;
            console.log('📥 Server transcript received:', transcription);
            
            setIsLoadingServerTranscript(false);
            
            if (transcription) {
                const lines = transcription.split('\n').filter(line => line.trim());
                const newSegments = [];
                
                lines.forEach((line, index) => {
                    const match = line.match(/\[Speaker (\d+)\]:\s*(.+)/);
                    if (match) {
                        newSegments.push({
                            id: `server-${Date.now()}-${index}`,
                            speaker: parseInt(match[1]),
                            text: match[2].trim(),
                            timestamp: Date.now() + index,
                            medical_entities: [],
                            is_formatted: true
                        });
                    } else if (line.trim()) {
                        newSegments.push({
                            id: `server-${Date.now()}-${index}`,
                            speaker: 1,
                            text: line.trim(),
                            timestamp: Date.now() + index,
                            medical_entities: [],
                            is_formatted: true
                        });
                    }
                });
                
                if (newSegments.length > 0) {
                    setConversation(prev => [...prev, ...newSegments]);
                }
            }
        };

        const showLoading = () => setIsLoadingServerTranscript(true);
        const hideLoading = () => setIsLoadingServerTranscript(false);

        window.addEventListener('transcriptUpdate', handleTranscriptUpdate);
        window.addEventListener('serverTranscriptReady', handleServerTranscript);
        window.addEventListener('showTranscriptLoading', showLoading);
        window.addEventListener('hideTranscriptLoading', hideLoading);

        return () => {
            window.removeEventListener('transcriptUpdate', handleTranscriptUpdate);
            window.removeEventListener('serverTranscriptReady', handleServerTranscript);
            window.removeEventListener('showTranscriptLoading', showLoading);
            window.removeEventListener('hideTranscriptLoading', hideLoading);
        };
    }, []);

    const processTranscriptUpdate = (payload) => {
        if (payload.is_final) {
            setInterimTranscript('');

            setConversation(prev => {
                const segmentId = payload.id || uuidv4();

                // 1. Check if we already have this segment (by ID)
                const existingIndex = prev.findIndex(s => s.id === segmentId);

                if (existingIndex !== -1) {
                    // Update existing segment (e.g. unformatted -> formatted)
                    const newConvo = [...prev];
                    newConvo[existingIndex] = {
                        ...newConvo[existingIndex],
                        text: payload.transcript,
                        medical_entities: payload.medical_entities || [],
                        is_formatted: payload.is_formatted
                    };
                    return newConvo;
                }

                // 2. Fallback: Check if the last segment has identical text (deduplicate content)
                if (prev.length > 0) {
                    const last = prev[prev.length - 1];
                    const cleanNew = payload.transcript.trim().replace(/[.!?]+$/, '');
                    const cleanLast = last.text.trim().replace(/[.!?]+$/, '');

                    if (cleanNew === cleanLast && last.speaker === (payload.speaker_tag || 1)) {
                        const newConvo = [...prev];
                        newConvo[newConvo.length - 1] = {
                            ...last,
                            text: payload.transcript, // Prefer the newer version (might have punctuation)
                            medical_entities: payload.medical_entities || []
                        };
                        return newConvo;
                    }
                }

                // 3. New segment
                const segment = {
                    id: segmentId,
                    speaker: payload.speaker_tag || 1,
                    text: payload.transcript,
                    timestamp: payload.start_time || Date.now(),
                    medical_entities: payload.medical_entities || [],
                    is_formatted: payload.is_formatted
                };

                const newConvo = [...prev, segment];
                return newConvo.length > MAX_CONVERSATION_LENGTH
                    ? newConvo.slice(-MAX_CONVERSATION_LENGTH)
                    : newConvo;
            });

            announceTranscriptUpdate(payload.transcript, true);
            scrollToBottom();
        } else {
            setInterimTranscript(payload.transcript);
            setIsProcessing(true);
            announceTranscriptUpdate(payload.transcript, false);
            scrollToBottom();
        }
    };

    const formatSpeaker = (speakerTag) => {
        return speakerMap[speakerTag] || `Speaker ${speakerTag}`;
    };

    const scrollToBottom = () => {
        if (scrollingContainerRef.current) {
            // Smooth scroll to bottom
            scrollingContainerRef.current.scrollTo({
                top: scrollingContainerRef.current.scrollHeight,
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
            <div
                className="transcript-body p-0"
                ref={transcriptBodyRef}
                role="log"
                aria-live="polite"
                aria-label="Real-time transcript"
            >
                {conversation.length === 0 && !isLoadingServerTranscript && (
                    <div className="text-center text-muted py-5">
                        <i className="fas fa-microphone-alt fa-3x mb-3 opacity-25"></i>
                        {currentLang && currentLang.startsWith('ar') ? (
                            <>
                                <p className="mb-0 fw-bold text-info">
                                    <i className="fas fa-circle-notch fa-spin me-2"></i>
                                    Arabic Recording Active
                                </p>
                                <p className="mb-1">Real-time text is hidden for maximum quality.</p>
                                <hr className="w-25 mx-auto opacity-25" />
                                <small className="text-muted">
                                    <i className="fas fa-magic me-1"></i>
                                    High-quality diarized script will appear <b>automatically</b> after you click "Stop".
                                </small>
                            </>
                        ) : (
                            <>
                                <p className="mb-0">Start ambient listening to see transcription here...</p>
                                <small className="text-muted">Transcription will appear in real-time</small>
                            </>
                        )}
                    </div>
                )}

                {isLoadingServerTranscript && (
                    <div className="text-center py-5">
                        <div className="spinner-border text-primary mb-3" role="status" style={{ width: '3rem', height: '3rem' }}>
                            <span className="visually-hidden">Loading...</span>
                        </div>
                        <p className="fw-bold text-primary mb-2">
                            <i className="fas fa-cog fa-spin me-2"></i>
                            Processing Transcription...
                        </p>
                        <small className="text-muted">Analyzing audio with AI for accurate speaker identification</small>
                    </div>
                )}

                <div className="p-3">
                    {conversation.map((segment) => (
                        <div
                            key={segment.id}
                            className={`speaker-segment mb-3 p-3 rounded-2 ${segment.speaker === 1 ? 'speaker-doctor bg-primary-subtle' :
                                segment.speaker === 2 ? 'speaker-patient bg-success-subtle' :
                                    'speaker-unknown bg-light'
                                }`}
                        >
                            <div className="d-flex justify-content-between align-items-start">
                                <div className="d-flex align-items-center">
                                    <span className={`speaker-label me-2 ${segment.speaker === 1 ? 'bg-primary text-white' :
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
                    <div className="interim-segment p-3 bg-light border-start border-warning border-3" style={{ display: 'none' }}>
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
    );
};

export default RealTimeTranscript;