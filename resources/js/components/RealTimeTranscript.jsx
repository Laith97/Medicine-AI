import React, { useState, useEffect } from 'react';

const RealTimeTranscript = ({ language }) => {
    const [transcript, setTranscript] = useState('');
    const [corrected, setCorrected] = useState(null);
    const [correcting, setCorrecting] = useState(false);
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
                // Use server-provided precise correction if available (single loading), else fallback to AI endpoint
                if (event.detail?.corrected_segments && Array.isArray(event.detail.corrected_segments) && event.detail.corrected_segments.length) {
                    setCorrected(event.detail.corrected_segments);
                    setCorrecting(false);
                } else {
                    setCorrected(null);
                    const patientFirst = (document.getElementById('patientSelect')?.selectedOptions[0]?.text?.split(' (')[0]?.trim().split(' ')[0] || '');
                    const appointmentContext = document.getElementById('patientSelect')?.selectedOptions[0]?.text || '';
                    setCorrecting(true);
                    fetch('/ai/ambient-listening/correct-diarization', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            transcript: transcriptText,
                            patient_first_name: patientFirst,
                            appointment_context: appointmentContext
                        })
                    }).then(r=>r.json()).then(data=>{
                        if(data.success && data.segments){ setCorrected(data.segments); }
                    }).catch(()=>{}).finally(()=>setCorrecting(false));
                }
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
        if (correcting) {
            return (
                <div style={{ padding: '20px', margin: '10px' }}>
                    <div style={{ padding: '30px', textAlign: 'center', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', borderRadius: '8px' }}>
                        <i className="fas fa-brain fa-spin" style={{ fontSize: '36px', marginBottom: '12px' }}></i>
                        <h5 style={{ fontWeight: 600 }}>Correcting diarization with AI...</h5>
                        <p style={{ fontSize: '13px', opacity: 0.9, margin: 0 }}>GPT-4o re-assigning Clinician vs Patient · ~1.2s</p>
                    </div>
                </div>
            );
        }
        // Use AI-corrected segments if available, else fallback to raw display
        const segments = corrected || transcript.split('\n').filter(l=>l.trim()).map(line=>{
            const m=line.match(/\[Speaker (\d+)\]:\s*(.*)/);
            if(m){ let t=m[2]; try{ const p=JSON.parse(t); if(p.text) t=p.text;}catch(e){} return {speaker: m[1], text: t.trim(), label: `Speaker ${m[1]}`}; }
            return {speaker: '?', text: line.trim(), label: 'Unknown'};
        }).filter(Boolean);

        const isCorrected = !!corrected;
        return (
            <div style={{ padding: '20px', margin: '10px' }}>
                <div style={{ marginBottom: '20px', padding: '15px', background: isCorrected ? 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' : 'linear-gradient(135deg, #6c757d 0%, #495057 100%)', color: 'white', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                    <div>
                        <i className={isCorrected ? "fas fa-check-circle" : "fas fa-exclamation-triangle"} style={{ marginRight: '10px', fontSize: '20px' }}></i>
                        <strong style={{ fontSize: '16px' }}>{isCorrected ? 'Transcript Ready · AI Corrected' : 'Transcript Ready'}</strong>
                        <p style={{ margin: '5px 0 0 30px', fontSize: '13px', opacity: 0.9 }}>{isCorrected ? `Precise · ${segments.length} turns · GPT-4o` : `Raw · ${segments.length} turns · heuristic fallback`}</p>
                    </div>
                    <div style={{ fontSize: '24px' }}>{isCorrected ? '✨' : '⚠️'}</div>
                </div>
                
                <div style={{ background: '#f8f9fa', borderRadius: '8px', padding: '20px', border: '1px solid #e9ecef' }}>
                    {segments.map((seg, index) => {
                        const isClinician = seg.speaker === 'Clinician' || seg.label === 'Clinician';
                        const isPatient = seg.speaker === 'Patient' || seg.label === 'Patient';
                        const label = seg.speaker === 'Clinician' || seg.speaker === 'Patient' ? seg.speaker : (isClinician ? 'Clinician' : isPatient ? 'Patient' : seg.label);
                        const color = isClinician ? '#667eea' : isPatient ? '#38ef7d' : '#6c757d';
                        const border = isClinician ? '#667eea' : isPatient ? '#38ef7d' : '#adb5bd';
                        return (
                            <div key={index} style={{ 
                                marginBottom: '15px', 
                                padding: '15px', 
                                background: 'white',
                                borderLeft: `4px solid ${border}`,
                                borderRadius: '6px',
                                boxShadow: '0 2px 4px rgba(0,0,0,0.05)'
                            }}>
                                <div style={{ 
                                    display: 'inline-block',
                                    padding: '4px 12px',
                                    background: color,
                                    color: 'white',
                                    borderRadius: '12px',
                                    fontSize: '12px',
                                    fontWeight: '600',
                                    marginBottom: '8px'
                                }}>
                                    {label}
                                </div>
                                <p style={{ margin: '0', lineHeight: '1.6', color: '#2c3e50', fontSize: '15px' }}>{seg.text}</p>
                            </div>
                        );
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
