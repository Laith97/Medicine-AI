// Simple working fix for AI Analysis and Clinical Doc buttons
(function() {
    'use strict';

    console.log('🔧 Button click handlers loaded');

    function getOrCreateSessionId() {
        // Prioritize window.sessionId (set by recording component)
        let sessionId = window.sessionId;
        
        if (!sessionId || sessionId === 'null' || sessionId === '') {
            const container = document.querySelector('[data-session-id]');
            sessionId = container ? container.getAttribute('data-session-id') : null;
        }
        
        if (!sessionId || sessionId === 'null' || sessionId === '') {
            sessionId = localStorage.getItem('voice_assistant_session_id');
        }
        
        console.log('Session ID sources:', { 
            window: window.sessionId,
            container: document.querySelector('[data-session-id]')?.getAttribute('data-session-id'), 
            localStorage: localStorage.getItem('voice_assistant_session_id'),
            final: sessionId
        });
        
        // If still no session, create one
        if (!sessionId || sessionId === 'null' || sessionId === '') {
            const patientSelect = document.getElementById('patientSelect');
            if (!patientSelect || !patientSelect.value) {
                alert('Please select a patient first.');
                return null;
            }
            
            // Create session synchronously
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/ai/voice-assistant/start-session', false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
            xhr.send(JSON.stringify({
                selectedPatient: patientSelect.value,
                language: 'en'
            }));
            
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    sessionId = response.sessionId;
                    window.sessionId = sessionId;
                    localStorage.setItem('voice_assistant_session_id', sessionId);
                    console.log('✅ Created new session:', sessionId);
                } else {
                    alert('Failed to create session: ' + (response.message || 'Unknown error'));
                    return null;
                }
            } else {
                alert('Failed to create session. Please try recording first.');
                return null;
            }
        }
        
        return sessionId;
    }

    function attachButtonHandlers() {
        const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
        const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

        if (generateAnalysisBtn && !generateAnalysisBtn.dataset.handlerAttached) {
            console.log('Attaching AI Analysis button handler');
            generateAnalysisBtn.dataset.handlerAttached = 'true';
            
            generateAnalysisBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('🧠 AI Analysis button clicked');
                
                // Get transcript
                const transcriptContainer = document.querySelector('#react-transcript-container');
                let transcription = '';
                
                if (transcriptContainer) {
                    transcription = transcriptContainer.innerText || transcriptContainer.textContent || '';
                }
                
                if (!transcription.trim()) {
                    alert('No transcript available. Please record a session first.');
                    return;
                }
                
                // Get or create session ID
                const sessionId = getOrCreateSessionId();
                if (!sessionId) return;
                
                // Get selected patient
                const patientSelect = document.getElementById('patientSelect');
                const selectedPatient = patientSelect ? patientSelect.value : null;
                
                // Disable button and show loading
                generateAnalysisBtn.disabled = true;
                const originalHTML = generateAnalysisBtn.innerHTML;
                generateAnalysisBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyzing...';
                
                // Make request
                fetch('/ai/voice-assistant/generate-ai-analysis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        transcription: transcription,
                        sessionId: sessionId,
                        selectedPatient: selectedPatient
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ AI Analysis successful', data);
                        
                        // Populate clinical chart fields
                        populateClinicalFields(data.aiAnalysis || data.analysis);
                        
                        // Show professional modal with formatted results
                        showAIAnalysisModal(data.aiAnalysis || data.analysis);
                        
                        generateAnalysisBtn.innerHTML = '<i class="fas fa-check me-1"></i>Complete!';
                        setTimeout(() => {
                            generateAnalysisBtn.innerHTML = originalHTML;
                            generateAnalysisBtn.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Analysis failed');
                    }
                })
                .catch(error => {
                    console.error('❌ AI Analysis error:', error);
                    alert('Failed to generate AI analysis: ' + error.message);
                    generateAnalysisBtn.innerHTML = originalHTML;
                    generateAnalysisBtn.disabled = false;
                });
            });
        }

        if (generateClinicalDocBtn && !generateClinicalDocBtn.dataset.handlerAttached) {
            console.log('Attaching Clinical Doc button handler');
            generateClinicalDocBtn.dataset.handlerAttached = 'true';
            
            generateClinicalDocBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('📄 Clinical Doc button clicked');
                
                // Get transcript
                const transcriptContainer = document.querySelector('#react-transcript-container');
                let transcription = '';
                
                if (transcriptContainer) {
                    transcription = transcriptContainer.innerText || transcriptContainer.textContent || '';
                }
                
                if (!transcription.trim()) {
                    alert('No transcript available. Please record a session first.');
                    return;
                }
                
                // Get or create session ID
                const sessionId = getOrCreateSessionId();
                if (!sessionId) return;
                
                // Get selected patient
                const patientSelect = document.getElementById('patientSelect');
                const selectedPatient = patientSelect ? patientSelect.value : null;
                
                // Disable button and show loading
                generateClinicalDocBtn.disabled = true;
                const originalHTML = generateClinicalDocBtn.innerHTML;
                generateClinicalDocBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
                
                // Make request
                fetch('/ai/voice-assistant/generate-ai-analysis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        transcription: transcription,
                        sessionId: sessionId,
                        selectedPatient: selectedPatient,
                        type: 'clinical_doc'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Clinical Doc successful', data);
                        
                        // Show professional modal with formatted results
                        const content = data.aiAnalysis || data.clinicalDoc || data.documentation || 'No documentation generated';
                        showClinicalDocModal(content);
                        
                        generateClinicalDocBtn.innerHTML = '<i class="fas fa-check me-1"></i>Complete!';
                        setTimeout(() => {
                            generateClinicalDocBtn.innerHTML = originalHTML;
                            generateClinicalDocBtn.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Documentation generation failed');
                    }
                })
                .catch(error => {
                    console.error('❌ Clinical Doc error:', error);
                    alert('Failed to generate clinical documentation: ' + error.message);
                    generateClinicalDocBtn.innerHTML = originalHTML;
                    generateClinicalDocBtn.disabled = false;
                });
            });
        }
    }

    // Attach handlers when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachButtonHandlers);
    } else {
        attachButtonHandlers();
    }

    // Also try after delays to ensure React components are loaded
    setTimeout(attachButtonHandlers, 1000);
    setTimeout(attachButtonHandlers, 3000);

    // Function to populate clinical chart fields from AI response
    function populateClinicalFields(aiAnalysis) {
        if (!aiAnalysis) return;
        
        // Extract sections using regex patterns
        const extractSection = (text, header) => {
            const regex = new RegExp(`${header}:?\\s*\\n?([\\s\\S]*?)(?=\\n\\n|\\*\\*|🔍|💊|🧪|⚠️|🔵|$)`, 'i');
            const match = text.match(regex);
            return match ? match[1].trim().substring(0, 500) : '';  // Limit to 500 chars
        };
        
        // Populate fields
        const symptoms = extractSection(aiAnalysis, '\\*\\*Symptoms\\*\\*');
        const history = extractSection(aiAnalysis, '\\*\\*Medical History\\*\\*|\\*\\*Relevant History\\*\\*');
        const findings = extractSection(aiAnalysis, '\\*\\*Physical Findings\\*\\*');
        const meds = extractSection(aiAnalysis, '\\*\\*Current Medications\\*\\*|\\*\\*Medications\\*\\*');
        const vitals = extractSection(aiAnalysis, '\\*\\*Vital Signs\\*\\*');
        
        // Extract primary diagnosis
        const diagnosisMatch = aiAnalysis.match(/1\.\s*\*?\*?([^*\n]+)\*?\*?\s*\(Probability/i);
        const diagnosis = diagnosisMatch ? diagnosisMatch[1].trim() : '';
        
        // Extract management plan
        const planMatch = aiAnalysis.match(/💊\\s*INITIAL\\sMANAGEMENT\\sPLAN:([\\s\\S]*?)(?=⚠️|---|🔵|$)/i);
        const carePlan = planMatch ? planMatch[1].trim().substring(0, 500) : '';
        
        // Populate fields if they exist and have content
        if (symptoms && document.getElementById('symptoms')) 
            document.getElementById('symptoms').value = symptoms;
        if (history && document.getElementById('medicalHistory')) 
            document.getElementById('medicalHistory').value = history;
        if (findings && document.getElementById('physicalFindings')) 
            document.getElementById('physicalFindings').value = findings;
        if (meds && document.getElementById('medications')) 
            document.getElementById('medications').value = meds;
        if (vitals && document.getElementById('vitalSigns')) 
            document.getElementById('vitalSigns').value = vitals;
        if (diagnosis && document.getElementById('diagnosis')) 
            document.getElementById('diagnosis').value = diagnosis;
        if (carePlan && document.getElementById('carePlan')) 
            document.getElementById('carePlan').value = carePlan;
        
        console.log('✅ Clinical chart fields populated');
    }

    // Disable buttons during transcript processing
    window.addEventListener('showTranscriptLoading', function() {
        const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
        const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');
        if (generateAnalysisBtn) {
            generateAnalysisBtn.disabled = true;
            generateAnalysisBtn.style.opacity = '0.5';
        }
        if (generateClinicalDocBtn) {
            generateClinicalDocBtn.disabled = true;
            generateClinicalDocBtn.style.opacity = '0.5';
        }
    });

    window.addEventListener('hideTranscriptLoading', function() {
        setTimeout(() => {
            const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
            const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');
            if (generateAnalysisBtn) {
                generateAnalysisBtn.disabled = false;
                generateAnalysisBtn.style.opacity = '1';
            }
            if (generateClinicalDocBtn) {
                generateClinicalDocBtn.disabled = false;
                generateClinicalDocBtn.style.opacity = '1';
            }
        }, 1000);
    });

    // Professional modal display functions
    function showAIAnalysisModal(content) {
        content = content || 'No content available';
        const safeContent = String(content).replace(/"/g, '&quot;');
        const modalHTML = `
            <div class="modal fade" id="aiAnalysisModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h5 class="modal-title"><i class="fas fa-brain me-2"></i>AI Analysis Results</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="background: #f8f9fa;">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; line-height: 1.8; background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">${content}</pre>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="copyToClipboard(this)" data-content="${safeContent}"><i class="fas fa-copy me-1"></i>Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
        modal.show();
        document.getElementById('aiAnalysisModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    function showClinicalDocModal(content) {
        content = content || 'No content available';
        const safeContent = String(content).replace(/"/g, '&quot;');
        const modalHTML = `
            <div class="modal fade" id="clinicalDocModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                            <h5 class="modal-title"><i class="fas fa-file-medical me-2"></i>Clinical Documentation</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="background: #f8f9fa;">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; line-height: 1.8; background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #11998e;">${content}</pre>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" onclick="copyToClipboard(this)" data-content="${safeContent}"><i class="fas fa-copy me-1"></i>Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = new bootstrap.Modal(document.getElementById('clinicalDocModal'));
        modal.show();
        document.getElementById('clinicalDocModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    // Copy to clipboard function
    window.copyToClipboard = function(button) {
        const content = button.getAttribute('data-content');
        navigator.clipboard.writeText(content).then(() => {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            button.classList.remove('btn-primary', 'btn-success');
            button.classList.add('btn-success');
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            }, 2000);
        });
    };
})();
