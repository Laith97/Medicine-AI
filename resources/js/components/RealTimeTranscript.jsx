import React, { useState, useEffect } from 'react';

const RealTimeTranscript = ({ language }) => {
    const [transcript, setTranscript] = useState('');
    const [isRecording, setIsRecording] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        const handleStatusUpdate = (event) => {
            if (event.detail?.status === 'recording') {
                setIsRecording(true);
                setIsProcessing(false);
                setTranscript('');
            } else if (event.detail?.status === 'stopped') {
                setIsRecording(false);
                setIsProcessing(true);
            }
        };

        const handleServerTranscript = (event) => {
            const transcriptText = event.detail?.transcription || event.detail?.improved_transcription || event.detail?.transcript || '';
            if (transcriptText) {
                setTranscript(transcriptText);
                setIsRecording(false);
                setIsProcessing(false);
            }
        };

        window.addEventListener('statusUpdate', handleStatusUpdate);
        window.addEventListener('serverTranscriptReady', handleServerTranscript);

        return () => {
            window.removeEventListener('statusUpdate', handleStatusUpdate);
            window.removeEventListener('serverTranscriptReady', handleServerTranscript);
        };
    }, []);

    if (isRecording) {
        return (
            <div style={{ padding: '30px', textAlign: 'center', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', borderRadius: '8px', margin: '10px' }}>
                <div style={{ marginBottom: '15px' }}>
                    <i className="fas fa-microphone-alt" style={{ fontSize: '56px', animation: 'pulse 1.5s ease-in-out infinite' }}></i>
                </div>
                <h4 style={{ marginBottom: '10px', fontWeight: '600' }}>🎙️ Recording Active</h4>
                <p style={{ marginBottom: '5px', opacity: 0.9 }}>Real-time text is hidden for maximum quality</p>
                <p style={{ fontSize: '14px', opacity: 0.8 }}>High-quality diarized transcript will appear after you click "Stop"</p>
            </div>
        );
    }

    if (isProcessing) {
        return (
            <div style={{ padding: '30px', textAlign: 'center', background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)', color: 'white', borderRadius: '8px', margin: '10px' }}>
                <div style={{ marginBottom: '15px' }}>
                    <i className="fas fa-cog fa-spin" style={{ fontSize: '56px' }}></i>
                </div>
                <h4 style={{ marginBottom: '10px', fontWeight: '600' }}>⚙️ Processing Audio</h4>
                <p style={{ marginBottom: '5px', opacity: 0.9 }}>Analyzing conversation with AI speaker diarization...</p>
                <p style={{ fontSize: '14px', opacity: 0.8 }}>This may take a few seconds</p>
                <div style={{ marginTop: '20px', background: 'rgba(255,255,255,0.2)', borderRadius: '10px', height: '6px', overflow: 'hidden' }}>
                    <div style={{ width: '100%', height: '100%', background: 'white', animation: 'progress 2s ease-in-out infinite' }}></div>
                </div>
            </div>
        );
    }

    if (transcript) {
        const lines = transcript.split('\n').filter(line => line.trim());
        
        return (
            <div style={{ padding: '20px', margin: '10px' }}>
                <div style={{ marginBottom: '20px', padding: '15px', background: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)', color: 'white', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div>
                        <i className="fas fa-check-circle" style={{ marginRight: '10px', fontSize: '20px' }}></i>
                        <strong style={{ fontSize: '16px' }}>Transcript Ready</strong>
                        <p style={{ margin: '5px 0 0 30px', fontSize: '13px', opacity: 0.9 }}>High-quality diarized transcript with speaker labels</p>
                    </div>
                    <div style={{ fontSize: '24px' }}>✨</div>
                </div>
                
                <div style={{ background: '#f8f9fa', borderRadius: '8px', padding: '20px', border: '1px solid #e9ecef' }}>
                    {lines.map((line, index) => {
                        const match = line.match(/\[Speaker (\d+)\]:\s*(.*)/);
                        if (match) {
                            const speaker = match[1];
                            const text = match[2];
                            const isDoctor = speaker === '1';
                            
                            return (
                                <div key={index} style={{ 
                                    marginBottom: '15px', 
                                    padding: '15px', 
                                    background: 'white',
                                    borderLeft: `4px solid ${isDoctor ? '#667eea' : '#38ef7d'}`,
                                    borderRadius: '6px',
                                    boxShadow: '0 2px 4px rgba(0,0,0,0.05)'
                                }}>
                                    <div style={{ 
                                        display: 'inline-block',
                                        padding: '4px 12px',
                                        background: isDoctor ? '#667eea' : '#38ef7d',
                                        color: 'white',
                                        borderRadius: '12px',
                                        fontSize: '12px',
                                        fontWeight: '600',
                                        marginBottom: '8px'
                                    }}>
                                        {isDoctor ? 'Speaker 1' : 'Speaker 2'}
                                    </div>
                                    <p style={{ margin: '0', lineHeight: '1.6', color: '#2c3e50', fontSize: '15px' }}>{text}</p>
                                </div>
                            );
                        }
                        return null;
                    })}
                </div>
            </div>
        );
    }

    return (
        <div style={{ padding: '40px', textAlign: 'center', color: '#999', background: '#f8f9fa', borderRadius: '8px', margin: '10px' }}>
            <i className="fas fa-microphone-slash" style={{ fontSize: '56px', marginBottom: '15px', opacity: 0.3 }}></i>
            <h5 style={{ color: '#6c757d', marginBottom: '8px' }}>Ready to Record</h5>
            <p style={{ fontSize: '14px' }}>Select a patient and click "Start" to begin recording</p>
        </div>
    );
};

export default RealTimeTranscript;
