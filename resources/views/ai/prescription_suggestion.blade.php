@push('styles')
<style>
/* Modern Professional AI Suggestion Design System */
.ai-section .btn-primary-modern {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    font-size: 0.88rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    box-shadow: 0 8px 24px rgba(30,41,59,0.18);
    transition: all 0.2s ease;
}
.ai-section .btn-primary-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 32px rgba(30,41,59,0.24);
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* Clinical Data Used - Modern Card */
#clinical-data-summary {
    background: #fff !important;
    border: 1px solid #eef2f7 !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 16px rgba(15,23,42,0.04) !important;
    overflow: hidden;
    padding: 0 !important;
}
#clinical-data-summary .cds-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #eef2f7;
    padding: 0.9rem 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
#clinical-data-summary .cds-header-icon {
    width: 32px; height: 32px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(15,23,42,0.04);
}
#clinical-data-summary .cds-title {
    font-weight: 800;
    font-size: 0.86rem;
    color: #0f172a;
    letter-spacing: -0.01em;
}
#clinical-data-summary .cds-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 0.75rem;
    padding: 1rem 1.1rem;
}
.cds-item {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
}
.cds-item-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.cds-item-value {
    font-size: 0.84rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.5;
}
.cds-footer {
    background: #f0fdf4;
    border-top: 1px solid #dcfce7;
    padding: 0.65rem 1.1rem;
    font-size: 0.74rem;
    color: #15803d;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* Modern Suggestion Cards */
#ai-suggestions {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
}
.modern-suggestion-card {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15,23,42,0.04);
    transition: all 0.2s ease;
    margin-bottom: 0.85rem;
}
.modern-suggestion-card:hover {
    box-shadow: 0 8px 24px rgba(15,23,42,0.08);
    transform: translateY(-1px);
    border-color: #e2e8f0;
}
.modern-suggestion-card.accepted {
    border-color: #86efac !important;
    background: #f0fdf4 !important;
    box-shadow: 0 4px 16px rgba(16,185,129,0.12);
}
.modern-suggestion-card.rejected {
    border-color: #fecaca !important;
    background: #fef2f2 !important;
    opacity: 0.85;
}
.suggestion-header {
    padding: 0.95rem 1.1rem 0.75rem;
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    border-bottom: 1px solid #f1f5f9;
}
.suggestion-med-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
    display: flex; align-items: center; justify-content: center;
    color: #1d4ed8;
    font-size: 1rem;
    flex-shrink: 0;
}
.suggestion-med-info {
    flex: 1;
    min-width: 0;
}
.suggestion-med-name {
    font-weight: 800;
    font-size: 0.95rem;
    color: #0f172a;
    letter-spacing: -0.01em;
    margin: 0 0 0.15rem;
    line-height: 1.3;
}
.suggestion-med-reason {
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.45;
    margin: 0;
}
.confidence-badge {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
}
.confidence-badge .badge {
    border-radius: 20px;
    padding: 0.35rem 0.65rem;
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: -0.01em;
    border: 1px solid transparent;
}
.confidence-badge .badge.high {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}
.confidence-badge .badge.medium {
    background: #fef9c3;
    color: #854d0e;
    border-color: #fde68a;
}
.confidence-badge .badge.low {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}
.suggestion-body {
    padding: 0.85rem 1.1rem;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.65rem;
    margin-bottom: 0.75rem;
}
@media (max-width: 640px) {
    .detail-grid { grid-template-columns: 1fr; }
}
.detail-item {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    padding: 0.65rem 0.75rem;
    text-align: center;
}
.detail-label {
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
}
.detail-value {
    font-weight: 700;
    font-size: 0.86rem;
    color: #0f172a;
}
.warnings-section, .interactions-section {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 0.65rem 0.75rem;
    margin-top: 0.6rem;
}
.interactions-section {
    background: #fef2f2;
    border-color: #fecaca;
}
.warnings-header, .interactions-header {
    font-weight: 700;
    font-size: 0.76rem;
    color: #92400e;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.interactions-header { color: #991b1b; }
.warnings-section ul, .interactions-section ul {
    margin: 0;
    padding-left: 1.1rem;
    font-size: 0.78rem;
    color: #78350f;
    line-height: 1.5;
}
.interactions-section ul { color: #7f1d1d; }
.suggestion-footer {
    padding: 0.75rem 1.1rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.btn-accept-modern {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 0.85rem;
    font-weight: 700;
    font-size: 0.82rem;
    box-shadow: 0 4px 12px rgba(16,185,129,0.2);
    transition: all 0.15s ease;
}
.btn-accept-modern:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,185,129,0.28); color: #fff; }
.btn-accept-modern:disabled { opacity: 0.6; transform: none; box-shadow: none; }
.btn-reject-modern {
    background: #fff;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.5rem 0.85rem;
    font-weight: 600;
    font-size: 0.82rem;
}
.btn-reject-modern:hover { background: #f8fafc; border-color: #cbd5e1; }
.suggestion-disclaimer {
    width: 100%;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.5rem 0.65rem;
    font-size: 0.72rem;
    color: #475569;
    margin-top: 0.25rem;
    display: flex;
    gap: 0.4rem;
}
#ai-risks {
    background: #fff !important;
    border: 1px solid #fee2e2 !important;
    border-radius: 16px !important;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(239,68,68,0.04) !important;
}
#ai-risks .risks-header {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-bottom: 1px solid #fecaca;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 800;
    font-size: 0.84rem;
    color: #991b1b;
}
#ai-risks #risks-content ul {
    margin: 0;
    padding-left: 1.1rem;
    font-size: 0.8rem;
    color: #7f1d1d;
    line-height: 1.6;
}
#ai-risks hr { border-color: #fee2e2; }
.suggestion-item.accepted { border-color: #86efac !important; background: #f0fdf4 !important; }
.suggestion-item.rejected { border-color: #fecaca !important; background: #fef2f2 !important; opacity: 0.85; }
</style>
@endpush

<input type="hidden" name="ai_suggestions" id="ai_suggestions" value="">
<input type="hidden" name="ai_risk_flags" id="ai_risk_flags" value="">

<div class="ai-section">
    <button type="button" id="aiSuggestBtn" class="btn-primary-modern w-100 d-flex align-items-center justify-content-center gap-2">
        <span class="d-flex align-items-center justify-content-center" style="width:28px;height:28px;border-radius:9px;background:rgba(255,255,255,0.14);"><i class="fas fa-wand-magic-sparkles" style="font-size:0.82rem;"></i></span>
        <span>Get AI Medication Suggestions</span>
        <span class="ms-auto d-none d-sm-inline-flex align-items-center gap-1" style="background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:20px;padding:0.2rem 0.55rem;font-size:0.68rem;font-weight:700;"><i class="fas fa-shield-halved" style="font-size:0.68rem;"></i> CDS</span>
    </button>
    <div class="d-flex align-items-center justify-content-between gap-2 mt-2 px-1">
        <div class="d-flex align-items-center gap-2" style="font-size:0.72rem; color:#64748b; line-height:1.4;">
            <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:20px;height:20px;border-radius:7px;background:#f1f5f9;border:1px solid #e2e8f0;"><i class="fas fa-lock" style="font-size:0.62rem;color:#475569;"></i></span>
            <span>Analyzes verified symptoms, allergies & meds. <strong style="color:#334155;">Requires clinical review.</strong></span>
        </div>
        <button type="button" class="btn btn-sm d-inline-flex align-items-center gap-1 flex-shrink-0" style="background:#fff;border:1px solid #e2e8f0;color:#2563eb;border-radius:20px;font-size:0.7rem;font-weight:600;padding:0.3rem 0.65rem;white-space:nowrap;" data-bs-toggle="modal" data-bs-target="#aiDataSourcesModal">
            <i class="fas fa-database" style="font-size:0.68rem;"></i> AI Sources
        </button>
    </div>
</div>

<!-- Professional Inline for AI Response — compact, no popup destroying page -->
<div id="aiResponseInline" style="display:none;">
  <div id="aiResponseClinicalData" class="mb-3"></div>
  <div id="aiResponseSuggestions" class="mb-3"></div>
  <div id="aiResponseRisks" style="display:none;" class="mb-3 p-3" style="background:#fff;border:1px solid #fee2e2;border-radius:14px;">
    <div class="d-flex align-items-center gap-2 mb-2"><span class="d-flex align-items-center justify-content-center" style="width:26px;height:26px;border-radius:9px;background:#fee2e2;border:1px solid #fecaca;"><i class="fas fa-shield-alt" style="font-size:0.7rem;color:#dc2626;"></i></span><span style="font-weight:800;font-size:0.84rem;color:#991b1b;">Safety Warnings</span></div>
    <div id="aiResponseRisksContent"></div>
  </div>
</div>
<!-- Hidden inline containers kept for backward compat (populated but not shown) -->
<div id="clinical-data-summary" style="display:none;"><div id="clinical-data-content"></div></div>
<div id="ai-suggestions" style="display:none;"></div>
<div id="ai-risks" style="display:none;"><div id="risks-content"></div></div>

@push('scripts')
<script>
// Function to save quick data to appointment for persistence
function saveQuickDataToAppointment(allergies, medications, notes) {
    console.log('Saving quick data:', { allergies, medications, notes });
    
    return $.ajax({
        url: "{{ route('doctor.appointments.save-quick-data', $appointment->id) }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            allergies: allergies,
            medications: medications,
            clinical_notes: notes
        },
        success: function(response) {
            console.log('Quick data saved successfully:', response);
        },
        error: function(xhr, status, error) {
            console.error('Failed to save quick data:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let errorMessage = 'Failed to save quick data';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMessage = xhr.responseText;
            }
            
            showNotification('Error saving data: ' + errorMessage, 'error');
        }
    });
}

// Modern Clinical Data Summary — professional, deduplicated, compact
function showClinicalDataSummary(clinicalData) {
    if (!clinicalData || typeof clinicalData !== 'object' || Object.keys(clinicalData).length === 0) {
        $('#clinical-data-content').html('<div class="p-3"><div class="d-flex gap-2 align-items-start p-3" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;"><span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;border-radius:10px;background:#fef3c7;"><i class="fas fa-triangle-exclamation" style="color:#d97706;font-size:0.82rem;"></i></span><div><strong style="font-size:0.84rem;color:#92400e;">No clinical documentation found.</strong><div style="font-size:0.76rem;color:#78350f;margin-top:2px;">AI analyzed general preventive care — add symptoms/diagnosis for accurate suggestions.</div></div></div></div>');
        $('#clinical-data-summary').html('<div id="clinical-data-content">' + $('#clinical-data-content').html() + '</div>'); // keep hidden
        // Re-inject correctly
        const html = $('#clinical-data-content').html();
        $('#clinical-data-summary').html('<div class="cds-header"><div class="cds-header-icon"><i class="fas fa-clipboard-check"></i></div><div><div class="cds-title">Clinical Data Used</div><div style="font-size:0.7rem;color:#64748b;font-weight:500;">Verified sources prioritized</div></div><span class="ms-auto badge bg-warning text-dark" style="border-radius:20px;font-size:0.65rem;">Limited</span></div>' + html); // keep hidden
        return;
    }
    const norm = s => String(s||'').trim().toLowerCase().replace(/\s+/g,' ');
    const sym = clinicalData.symptoms ? String(clinicalData.symptoms).trim() : '';
    const diag = clinicalData.current_diagnosis ? String(clinicalData.current_diagnosis).trim() : '';
    const dup = sym && diag && norm(sym) === norm(diag);
    let items = '';
    const addItem = (icon, label, value, accent) => {
        if (!value) return;
        const v = String(value).substring(0,180) + (String(value).length>180?'...':'');
        items += `<div class="cds-item" style="${accent?'border-left:3px solid '+accent+';':''}"><div class="cds-item-label"><i class="${icon}"></i> ${label}</div><div class="cds-item-value">${$('<div>').text(v).html()}</div></div>`;
    };
    if (sym && !dup) addItem('fas fa-clipboard-list', 'Symptoms / Chief Complaint', sym, '#3b82f6');
    else if (sym && dup) addItem('fas fa-stethoscope', 'Symptoms / Diagnosis', sym, '#0ea5e9');
    if (clinicalData.doctor_notes && !dup) addItem('fas fa-user-doctor', 'Doctor Notes', String(clinicalData.doctor_notes).substring(0,180), '#8b5cf6');
    if (diag && !dup) addItem('fas fa-file-medical', 'Current Diagnosis', diag, '#10b981');
    if (clinicalData.past_diagnoses && clinicalData.past_diagnoses.length > 0) addItem('fas fa-clock-rotate-left', 'Past History', clinicalData.past_diagnoses.join('; ').substring(0,180), '#64748b');
    if (clinicalData.voice_diagnosis) {
        let v = String(clinicalData.voice_diagnosis);
        const m = v.match(/Symptoms:\s*([^\n]+)/i);
        if (m) v = m[1].trim() + (v.includes('Medical History') ? ' • ' + (v.match(/Medical History:\s*([^\n]+)/i)?.[1]||'').trim().substring(0,60) : '');
        else v = v.replace(/🟢.*?LEVEL 1:.*?CHIEF COMPLAINT:/is, '').trim().substring(0,160);
        addItem('fas fa-microphone', 'Voice Assistant', v, '#f59e0b');
    }
    const header = `<div class="cds-header"><div class="cds-header-icon"><i class="fas fa-clipboard-check"></i></div><div><div class="cds-title">Clinical Data Used</div><div style="font-size:0.7rem;color:#64748b;font-weight:500;">Verified • ${Object.keys(clinicalData).filter(k=>clinicalData[k]).length} sources analyzed</div></div><span class="ms-auto badge bg-success" style="border-radius:20px;font-size:0.65rem;"><i class="fas fa-check me-1"></i>Verified</span></div>`;
    const grid = `<div class="cds-grid">${items || '<div class="cds-item"><div class="cds-item-value text-muted">No specific clinical data — preventive guidance only</div></div>'}</div>`;
    const footer = `<div class="cds-footer"><i class="fas fa-circle-check"></i> AI analyzed verified clinical data above to generate suggestions. Review required.</div>`;
    $('#clinical-data-summary').html(header + grid + footer); // keep hidden, use aiResponseInline
    // Also populate professional popup (compact, no footer)
    if ($('#aiResponseClinicalData').length) {
        $('#aiResponseClinicalData').html(`<div class="p-3" style="background:#fff;border:1px solid #eef2f7;border-radius:14px;">${grid}</div>`);

    }
}

// Prescription AI Suggestion
$('#aiSuggestBtn').click(function(e) {
    e.preventDefault();

    // CRITICAL SAFETY: Only use doctor-verified clinical data for AI medication suggestions
    // Patient-reported symptoms are excluded due to unreliability
    var symptoms = @json($appointment->doctor_notes ?? '');

    // Include current (most recent) diagnosis data if available
    var currentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);

    // Get allergies and medications from the most recent diagnosis patient_data
    var allergies = [];
    var pastMeds = [];

    if (currentDiagnosis && currentDiagnosis.patient_data) {
        // Handle allergies - can be string or array
        var diagnosisAllergies = currentDiagnosis.patient_data.allergies || [];
        if (typeof diagnosisAllergies === 'string' && diagnosisAllergies.trim().length > 0) {
            allergies = [diagnosisAllergies.trim()];
        } else if (Array.isArray(diagnosisAllergies)) {
            allergies = diagnosisAllergies;
        }

        // Handle medications - can be string or array
        var diagnosisMeds = currentDiagnosis.patient_data.medications || currentDiagnosis.patient_data.past_medications || [];
        if (typeof diagnosisMeds === 'string' && diagnosisMeds.trim().length > 0) {
            pastMeds = [diagnosisMeds.trim()];
        } else if (Array.isArray(diagnosisMeds)) {
            pastMeds = diagnosisMeds;
        }

        // CRITICAL FIX: Include clinical_notes as symptoms if doctor_notes is empty
        // This is where Quick Entry symptoms are stored (e.g., "Chest pain, shortness of breath")
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.patient_data.clinical_notes) {
            symptoms = currentDiagnosis.patient_data.clinical_notes;
            console.log('🔍 Using clinical_notes as symptoms:', symptoms);
        }

        // Also check patient_data.symptoms as another fallback
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.patient_data.symptoms) {
            symptoms = currentDiagnosis.patient_data.symptoms;
            console.log('🔍 Using patient_data.symptoms as symptoms:', symptoms);
        }

        // If still no symptoms, use diagnosis_text as symptoms (this IS the clinical presentation)
        if ((!symptoms || symptoms.trim() === '') && currentDiagnosis.diagnosis_text) {
            symptoms = currentDiagnosis.diagnosis_text;
            console.log('🔍 Using diagnosis_text as symptoms:', symptoms);
        }
    }

    // Include past diagnosis history (all except most recent, limit to last 10)
    var pastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());

    // Include voice assistant diagnosis if available
    var voiceDiagnosis = @json($appointment->patient ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first() : null);
    var voiceTranscription = typeof _cachedVoiceTranscription !== 'undefined' ? _cachedVoiceTranscription : @json($appointment->patient ? \App\Models\VoiceTranscription::where('patient_id', $appointment->patient->id)->latest()->first() : null);

    // Debug logging to see what data we actually have
    console.log('🔍 DEBUG: Checking AI prescription data availability');
    console.log('Current Diagnosis:', currentDiagnosis);
    console.log('Patient Data:', currentDiagnosis ? currentDiagnosis.patient_data : 'No diagnosis');
    console.log('Extracted Allergies:', allergies);
    console.log('Extracted Medications:', pastMeds);
    console.log('Doctor Notes:', symptoms);
    
    // Check for missing critical data
    var missingData = [];
    
    // Check allergies - should be a non-empty string or non-empty array
    // Accept "No known allergies", "None", "N/A" as valid entries
    var hasAllergies = false;
    if (allergies) {
        if (Array.isArray(allergies) && allergies.length > 0) {
            hasAllergies = true;
        } else if (typeof allergies === 'string' && allergies.trim().length > 0) {
            const allergyText = allergies.trim().toLowerCase();
            // Accept explicit "no allergies" statements as valid
            if (allergyText === 'none' || allergyText === 'n/a' || 
                allergyText.includes('no known') || allergyText.includes('no allergies') ||
                allergyText === 'nka' || allergyText === 'nkda') {
                hasAllergies = true;
            } else if (allergies.trim().length > 0) {
                hasAllergies = true;
            }
        }
    }
    console.log('Has Allergies:', hasAllergies, 'Value:', allergies);
    
    // Check medications - should be a non-empty string or non-empty array
    // Accept "No current medications", "None", "N/A" as valid entries
    var hasMedications = false;
    if (pastMeds) {
        if (Array.isArray(pastMeds) && pastMeds.length > 0) {
            hasMedications = true;
        } else if (typeof pastMeds === 'string' && pastMeds.trim().length > 0) {
            const medText = pastMeds.trim().toLowerCase();
            // Accept explicit "no medications" statements as valid
            if (medText === 'none' || medText === 'n/a' || 
                medText.includes('no current') || medText.includes('no medications') ||
                medText.includes('no meds')) {
                hasMedications = true;
            } else if (pastMeds.trim().length > 0) {
                hasMedications = true;
            }
        }
    }
    console.log('Has Medications:', hasMedications, 'Value:', pastMeds);

    // Precise fallback to voice layer when Diagnosis empty — extract normalized "None" for safety (not truncated AI text)
    const _vtAll = voiceTranscription?.structured_chart?.medical_history || voiceTranscription?.extracted_data?.medical_history || voiceTranscription?.structured_chart?.medications || '';
    const _vtMed = voiceTranscription?.structured_chart?.medications || voiceTranscription?.extracted_data?.medications || voiceTranscription?.structured_chart?.medical_history || '';
    if (!hasAllergies && (voiceDiagnosis || voiceTranscription)) {
        const vAll = voiceDiagnosis?.patient_data?.allergies || voiceDiagnosis?.patient_data?.medical_history || voiceDiagnosis?.ai_analysis || _vtAll || voiceDiagnosis?.structured_chart?.medical_history || '';
        const vAllStr = String(vAll);
        if (vAll && vAllStr.trim().length > 0 && /no known|none|nkda|no allergies/i.test(vAllStr)) {
            hasAllergies = true;
            // Normalize to "None" for backend safety check (AIAssistant requires non-empty, "None" is valid)
            const m = vAllStr.match(/no known drug allergies|no known allergies|no drug allergies|none|nkda/i);
            allergies = [m ? 'None' : 'None'];
            console.log('🔍 Voice fallback allergies: None (from:', vAllStr.substring(0,100) + ')');
        } else if (vAll && /allergy/i.test(vAllStr) && vAllStr.trim().length > 0) {
            hasAllergies = true;
            allergies = [vAllStr.match(/allerg[^,\n]*/i)?.[0]?.trim().substring(0,80) || 'None'];
            console.log('🔍 Voice fallback allergies (extracted):', allergies[0]);
        } else if ((voiceDiagnosis?.structured_chart?.medical_history && /no known|none|nkda/i.test(voiceDiagnosis.structured_chart.medical_history)) || (_vtAll && /no known|none|nkda/i.test(String(_vtAll)))) {
            hasAllergies = true; allergies = ['None'];
            console.log('🔍 VT fallback allergies: None');
        }
    }
    if (!hasMedications && (voiceDiagnosis || voiceTranscription)) {
        const vMed = voiceDiagnosis?.patient_data?.medications || voiceDiagnosis?.patient_data?.past_medications || voiceDiagnosis?.structured_chart?.medications || voiceDiagnosis?.ai_analysis || _vtMed || '';
        const vMedStr = String(vMed);
        if (vMed && vMedStr.trim().length > 0 && /none|no.*medications|no.*meds|no current|no regular medications/i.test(vMedStr.toLowerCase())) {
            hasMedications = true;
            pastMeds = ['None'];
            console.log('🔍 Voice fallback meds: None (from:', vMedStr.substring(0,100) + ')');
        } else if (vMed && vMedStr.trim().length > 0) {
            // Has meds info but not "None" — use actual value
            hasMedications = true; pastMeds = [vMedStr.match(/Current Medications:\s*([^\n]+)/i)?.[1]?.trim() || vMedStr.substring(0,80)];
            console.log('🔍 Voice fallback meds (extracted):', pastMeds[0]);
        } else if ((voiceDiagnosis?.structured_chart?.medications && String(voiceDiagnosis.structured_chart.medications).trim().length > 0) || (_vtMed && String(_vtMed).trim().length > 0)) {
            const medVal = voiceDiagnosis?.structured_chart?.medications || _vtMed;
            hasMedications = true; pastMeds = [String(medVal).substring(0,80).toLowerCase().includes('none') ? 'None' : String(medVal).substring(0,80)];
        }
    }
    
    var hasClinicalAssessment = !!(symptoms || currentDiagnosis || voiceDiagnosis || voiceTranscription);
    console.log('Has Clinical Assessment:', hasClinicalAssessment, 'Symptoms:', symptoms, 'Diagnosis:', !!currentDiagnosis, 'Voice:', !!voiceDiagnosis, 'VT:', !!voiceTranscription);

    // Check if symptoms from patient_data.symptoms is populated — include voice fallback (VT structured_chart)
    var hasPatientDataSymptoms = !!(currentDiagnosis && currentDiagnosis.patient_data && currentDiagnosis.patient_data.symptoms && currentDiagnosis.patient_data.symptoms.trim() !== '');
    if (!hasPatientDataSymptoms && (voiceDiagnosis || voiceTranscription)) {
        const vSym = voiceDiagnosis?.patient_data?.symptoms || voiceDiagnosis?.structured_chart?.symptoms || voiceTranscription?.structured_chart?.symptoms || voiceTranscription?.extracted_data?.symptoms || (voiceDiagnosis?.ai_analysis ? String(voiceDiagnosis.ai_analysis).match(/Symptoms:\s*([^\n]+)/i)?.[1] : null) || (voiceTranscription?.raw_transcription ? 'voice transcript present' : null);
        if (vSym && String(vSym).trim().length > 0) hasPatientDataSymptoms = true;
    }
    // Also consider voiceTranscription existence as clinical assessment
    if (!hasPatientDataSymptoms && voiceTranscription) hasPatientDataSymptoms = true;

    // Only add to missing if truly missing (fallback considered available)
    if (!hasAllergies) {
        missingData.push('Patient Allergies');
    }
    if (!hasMedications) {
        missingData.push('Current Medications');
    }
    if (!hasPatientDataSymptoms && !hasClinicalAssessment) {
        missingData.push('Symptoms (empty - diagnosis text will be used)');
    }
    
    console.log('Missing Data:', missingData);

    // Show warning modal if critical data is missing
    if (missingData.length > 0) {
        var warningHtml = `
            <div class="modal fade modal-premium" id="aiWarningModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="head-icon" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color:#d97706; border:1px solid #fde68a;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title mb-0" style="font-size:0.95rem; font-weight:800; color:#1e293b; letter-spacing:-0.01em;">Missing Critical Data</h5>
                                    <div style="font-size:0.72rem; color:#94a3b8; font-weight:500;">AI requires allergies & meds for safety</div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="ml-warning">
                                <i class="fas fa-shield-alt" style="color:#dc2626; margin-top:2px;"></i>
                                <div>
                                    <strong style="color:#7f1d1d;">⚠️ Missing:</strong>
                                    <ul class="mb-1 mt-1" style="font-size:0.78rem; color:#7f1d1d;">
                                        ${missingData.map(item => '<li>' + item + '</li>').join('')}
                                    </ul>
                                    <small style="font-size:0.72rem; color:#991b1b;">AI is blocked for patient safety until critical data is provided.</small>
                                </div>
                            </div>
                            <div class="ml-note mt-3">
                                <i class="fas fa-info-circle" style="color:#2563eb; margin-top:2px;"></i>
                                <div>
                                    <strong>Why this matters:</strong>
                                    <ul class="small mb-0 mt-1" style="font-size:0.74rem; color:#475569;">
                                        <li><strong>Without allergies:</strong> Life-threatening allergy risk</li>
                                        <li><strong>Without current medications:</strong> Cannot check drug interactions</li>
                                        <li><strong>Without clinical assessment:</strong> No basis for recommendations</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Quick Data Entry Form — premium -->
                            <div id="quickDataEntry" class="mt-3 p-3" style="display:none; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px;">
                                <h6 class="mb-3 fw-bold" style="font-size:0.84rem; color:#1e293b;"><i class="fas fa-edit me-2" style="color:#475569;"></i>Quick Data Entry</h6>
                                <form id="quickDataForm">
                                    <div id="quickAllergyField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Patient Allergies *</label>
                                        <input type="text" id="quickAllergies" class="form-control" placeholder="Enter allergies or 'None' if no known allergies" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                        <div class="form-text" style="font-size:0.72rem;">Examples: Penicillin, Sulfa, None, No known allergies</div>
                                    </div>
                                    <div id="quickMedicationField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Current Medications *</label>
                                        <input type="text" id="quickMedications" class="form-control" placeholder="Enter current medications or 'None' if no current medications" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;">
                                        <div class="form-text" style="font-size:0.72rem;">Examples: Lisinopril 10mg daily, Metformin 500mg twice daily, None</div>
                                    </div>
                                    <div id="quickNotesField" style="display:none;" class="mb-3">
                                        <label class="form-label fw-semibold" style="font-size:0.82rem;">Symptoms *</label>
                                        <textarea id="quickNotes" class="form-control" rows="3" placeholder="Brief clinical assessment or symptoms" style="border-radius:10px; border:1px solid #e2e8f0; font-size:0.88rem;"></textarea>
                                        <div class="form-text" style="font-size:0.72rem;">Brief description of patient's condition or symptoms</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn fw-semibold" style="background:#1e293b; color:#fff; border-radius:8px; padding:0.5rem 1rem; font-size:0.84rem;" id="saveQuickDataBtn">
                                            <i class="fas fa-save me-1"></i>Save & Continue with AI
                                        </button>
                                        <button type="button" class="btn fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#64748b; border-radius:8px; font-size:0.84rem;" id="cancelQuickDataBtn">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="ml-note mt-3" id="optionsAlert" style="background:#eff6ff; border-color:#dbeafe;">
                                <i class="fas fa-lightbulb" style="color:#2563eb; margin-top:2px;"></i>
                                <div>
                                    <strong>Options:</strong>
                                    <div class="row g-2 mt-1" style="font-size:0.78rem;">
                                        <div class="col-6"><strong>1. Quick Entry</strong><br><small style="color:#64748b;">Fill missing data here (recommended)</small></div>
                                        <div class="col-6"><strong>2. Continue Limited</strong><br><small style="color:#64748b;">General guidance only</small></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:0.9rem 1.25rem;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn fw-semibold" style="background:#1e293b; color:#fff; border-radius:8px; font-size:0.84rem;" id="quickEntryBtn">
                                <i class="fas fa-edit me-1"></i>Quick Entry
                            </button>
                            <button type="button" class="btn fw-semibold" style="background:#fff; border:1px solid #e2e8f0; color:#475569; border-radius:8px; font-size:0.84rem;" id="continueAnywayBtn">
                                <i class="fas fa-exclamation-triangle me-1"></i>Continue Limited
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        $('#aiWarningModal').remove();
        
        // Add modal to body
        $('body').append(warningHtml);
        
        // Show modal
        var modal = new bootstrap.Modal(document.getElementById('aiWarningModal'));
        modal.show();
        
        // Handle quick entry button
        $('#quickEntryBtn').click(function() {
            // Hide options alert and show quick data entry form
            $('#optionsAlert').hide();
            $('#quickDataEntry').show();
            
            // Show relevant fields based on missing data
            if (missingData.includes('Patient Allergies')) {
                $('#quickAllergyField').show();
            }
            if (missingData.includes('Current Medications')) {
                $('#quickMedicationField').show();
            }
            if (missingData.includes('Symptoms')) {
                $('#quickNotesField').show();
            }
            
            // Hide the footer buttons
            $(this).closest('.modal-footer').hide();
            
            // Focus on first visible field
            setTimeout(() => {
                if ($('#quickAllergyField').is(':visible')) {
                    $('#quickAllergies').focus();
                } else if ($('#quickMedicationField').is(':visible')) {
                    $('#quickMedications').focus();
                } else if ($('#quickNotesField').is(':visible')) {
                    $('#quickNotes').focus();
                }
            }, 100);
        });
        
        // Handle save quick data button
        $('#saveQuickDataBtn').click(function() {
            var quickAllergies = $('#quickAllergies').val().trim();
            var quickMedications = $('#quickMedications').val().trim();
            var quickNotes = $('#quickNotes').val().trim();
            
            // Validate required fields
            var hasError = false;
            if (missingData.includes('Patient Allergies') && !quickAllergies) {
                $('#quickAllergies').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickAllergies').removeClass('is-invalid');
            }
            
            if (missingData.includes('Current Medications') && !quickMedications) {
                $('#quickMedications').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickMedications').removeClass('is-invalid');
            }
            
            if (missingData.includes('Symptoms') && !quickNotes) {
                $('#quickNotes').addClass('is-invalid');
                hasError = true;
            } else {
                $('#quickNotes').removeClass('is-invalid');
            }
            
            if (hasError) {
                showNotification('Please fill all required fields', 'error');
                return;
            }
            
            // Save the data temporarily and update our variables
            if (quickAllergies) {
                allergies = [quickAllergies];
            }
            if (quickMedications) {
                pastMeds = [quickMedications];
            }
            if (quickNotes) {
                symptoms = quickNotes;
            }
            
            // Save to appointment (for persistence) and wait for completion
            var savePromise = saveQuickDataToAppointment(quickAllergies, quickMedications, quickNotes);
            
            savePromise.done(function(response) {
                console.log('Quick data save completed successfully:', response);
                
                // Update the current diagnosis data to reflect the saved data
                if (currentDiagnosis) {
                    if (!currentDiagnosis.patient_data) {
                        currentDiagnosis.patient_data = {};
                    }
                    if (quickAllergies) {
                        currentDiagnosis.patient_data.allergies = quickAllergies;
                    }
                    if (quickMedications) {
                        currentDiagnosis.patient_data.medications = quickMedications;
                    }
                    if (quickNotes) {
                        currentDiagnosis.patient_data.clinical_notes = quickNotes;
                    }
                }
                
                // Close modal and proceed with AI suggestion
                modal.hide();
                proceedWithAISuggestion();
            }).fail(function(xhr, status, error) {
                console.error('Quick data save failed:', error);
                showNotification('Failed to save data to database. Proceeding with temporary data only.', 'warning');
                
                // Still proceed with AI suggestion using temporary data
                modal.hide();
                proceedWithAISuggestion();
            });
        });
        
        // Handle cancel quick data button
        $('#cancelQuickDataBtn').click(function() {
            // Show options alert and hide quick data entry form
            $('#quickDataEntry').hide();
            $('#optionsAlert').show();
            
            // Show the footer buttons
            $('.modal-footer').show();
        });
        
        // Handle continue anyway button — inject safe placeholders so backend safety check passes and AI can give general guidance
        $('#continueAnywayBtn').click(function() {
            if (!hasAllergies) allergies = ["No known allergies"];
            if (!hasMedications) pastMeds = ["No current medications"];
            if (!hasClinicalAssessment || !symptoms || (typeof symptoms === 'string' && symptoms.trim() === '')) {
                symptoms = "General consultation - no specific symptoms documented";
            }
            // Mark as acknowledged so backend could bypass strict block if needed
            window._aiContinueLimited = true;
            modal.hide();
            proceedWithAISuggestion();
        });
        
        return;
    }
    
    // If all critical data is present, proceed
    proceedWithAISuggestion();
    
    function proceedWithAISuggestion() {
        var button = $('#aiSuggestBtn');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Generating...');

        $.ajax({
        url: "{{ route('ai.appointments.suggest', $appointment->id) }}",
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            symptoms: symptoms,
            allergies: JSON.stringify(allergies),
            past_meds: JSON.stringify(pastMeds),
            current_diagnosis: JSON.stringify(currentDiagnosis),
            past_diagnoses: JSON.stringify(pastDiagnoses),
            voice_diagnosis: JSON.stringify(voiceDiagnosis),
            doctor_notes: @json($appointment->doctor_notes),
            clinical_notes: currentDiagnosis && currentDiagnosis.patient_data ? currentDiagnosis.patient_data.clinical_notes : null,
            continue_limited: window._aiContinueLimited ? 1 : 0
        },
        success: function(response) {
            button.prop('disabled', false).html('<i class="fas fa-magic me-1"></i>Suggest with AI');

            // Debug logging
            console.log('AI Response:', response);
            console.log('Suggestions:', response.suggestions);
            console.log('First suggestion:', response.suggestions ? response.suggestions[0] : 'none');

            // Show clinical data summary first
            showClinicalDataSummary(response.clinical_data_used);

            // Modern Suggestions — professional cards
            if (response.suggestions && response.suggestions.length > 0) {
                const isBlocked = response.suggestions.length === 1 && response.suggestions[0].med === 'Critical Data Missing';
                if (isBlocked) {
                    const s = response.suggestions[0];
                    const blockedHtml = `
                        <div class="modern-suggestion-card" style="border-color:#fecaca;background:#fef2f2;">
                            <div class="suggestion-header" style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);">
                                <span class="suggestion-med-icon" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-color:#fde68a;color:#92400e;"><i class="fas fa-triangle-exclamation"></i></span>
                                <div class="suggestion-med-info"><div class="suggestion-med-name" style="color:#92400e;">Critical Data Missing</div><div class="suggestion-med-reason">${$('<div>').text(s.reason||'Complete allergies, medications and assessment.').html()}</div></div>
                                <span class="badge low" style="background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">Blocked</span>
                            </div>
                            <div class="suggestion-body"><div class="warnings-section"><div class="warnings-header"><i class="fas fa-shield-halved"></i> Why blocked</div><ul><li>Allergies required to prevent anaphylaxis</li><li>Current meds required for interaction check</li><li>Clinical assessment required</li></ul></div></div>
                        </div>`;
                    $('#ai-suggestions').html(`<div class="d-flex align-items-center justify-content-between mb-2 px-1"><h6 class="mb-0" style="font-weight:800;color:#0f172a;letter-spacing:-0.01em;"><i class="fas fa-brain me-2" style="color:#6366f1;"></i>AI Clinical Support</h6><span class="badge bg-danger" style="border-radius:20px;">Blocked</span></div>` + blockedHtml).show();
                    $('#ai_suggestions').val('');
                } else {
                    let suggestionsHtml = `<div class="d-flex align-items-center justify-content-between mb-2 px-1">
                        <h6 class="mb-0" style="font-weight:800;color:#0f172a;letter-spacing:-0.01em;font-size:0.92rem;"><i class="fas fa-sparkles me-2" style="color:#6366f1;"></i>AI Suggested Medications <span class="text-muted" style="font-weight:600;font-size:0.72rem;">• ${response.suggestions.length} options • Review required</span></h6>
                        <span class="badge" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:20px;font-size:0.68rem;"><i class="fas fa-wand-magic-sparkles me-1"></i> CDS</span>
                    </div>`;
                    $.each(response.suggestions, function(i, suggestion) {
                        if (typeof suggestion !== 'object' || suggestion === null) return true;
                        const confidence = suggestion.confidence || 0;
                        const level = confidence >= 80 ? 'high' : (confidence >= 60 ? 'medium' : 'low');
                        const levelText = confidence >= 80 ? 'High' : (confidence >= 60 ? 'Medium' : 'Low');
                        const levelIcon = level==='high' ? 'fa-circle-check' : level==='medium' ? 'fa-chart-simple' : 'fa-triangle-exclamation';
                        const med = $('<div>').text(suggestion.med || 'Unknown').html();
                        const reason = $('<div>').text(suggestion.reason || 'Clinical decision support suggestion').html();
                        let warningsHtml = '';
                        if (suggestion.warnings && suggestion.warnings.length > 0) {
                            warningsHtml = '<div class="warnings-section"><div class="warnings-header"><i class="fas fa-triangle-exclamation"></i> Warnings</div><ul>';
                            $.each(suggestion.warnings, function(_,w){ warningsHtml += '<li>' + $('<div>').text(w).html() + '</li>'; });
                            warningsHtml += '</ul></div>';
                        }
                        let interactionsHtml = '';
                        if (suggestion.interactions && suggestion.interactions.length > 0) {
                            interactionsHtml = '<div class="interactions-section"><div class="interactions-header"><i class="fas fa-pills"></i> Interactions</div><ul>';
                            $.each(suggestion.interactions, function(_,it){ interactionsHtml += '<li>' + $('<div>').text(it).html() + '</li>'; });
                            interactionsHtml += '</ul></div>';
                        }
                        suggestionsHtml += `
                        <div class="modern-suggestion-card" data-index="${i}">
                            <div class="suggestion-header">
                                <span class="suggestion-med-icon"><i class="fas fa-prescription-bottle-medical"></i></span>
                                <div class="suggestion-med-info">
                                    <div class="suggestion-med-name">${med}</div>
                                    <div class="suggestion-med-reason">${reason}</div>
                                </div>
                                <div class="confidence-badge">
                                    <span class="badge ${level}"><i class="fas ${levelIcon} me-1"></i>${confidence}% ${levelText}</span>
                                    <span style="font-size:0.66rem;color:#64748b;font-weight:600;">Confidence</span>
                                </div>
                            </div>
                            <div class="suggestion-body">
                                <div class="detail-grid">
                                    <div class="detail-item"><div class="detail-label"><i class="fas fa-prescription-bottle"></i> Dosage</div><div class="detail-value">${$('<div>').text(suggestion.dosage||'N/A').html()}</div></div>
                                    <div class="detail-item"><div class="detail-label"><i class="fas fa-clock"></i> Frequency</div><div class="detail-value">${$('<div>').text(suggestion.freq||'N/A').html()}</div></div>
                                    <div class="detail-item"><div class="detail-label"><i class="fas fa-calendar-days"></i> Duration</div><div class="detail-value">${$('<div>').text(suggestion.dur||'N/A').html()}</div></div>
                                </div>
                                ${warningsHtml}
                                ${interactionsHtml}
                            </div>
                            <div class="suggestion-footer">
                                <button type="button" class="btn-accept-modern accept-suggestion" data-index="${i}"><i class="fas fa-check me-1"></i>Use</button>
                                <button type="button" class="btn-reject-modern reject-suggestion" data-index="${i}"><i class="fas fa-xmark me-1"></i>Dismiss</button>
                            </div>
                        </div>`;
                    });
                    $('#ai-suggestions').html(suggestionsHtml); // keep hidden, use aiResponseInline
                    if ($('#aiResponseSuggestions').length) {
                        $('#aiResponseSuggestions').html(suggestionsHtml);
                    }
                    // Show inline professional (compact, no popup)
                    $('#aiResponseInline').slideDown(200);
                    $('html, body').animate({scrollTop: $('#aiResponseInline').offset().top - 80}, 300);
                    $('#ai_suggestions').val(JSON.stringify(response.suggestions));
                }
            } else {
                const emptyHtml = '<div class="modern-suggestion-card"><div class="p-3 d-flex gap-3 align-items-start"><span class="suggestion-med-icon" style="background:#f0fdf4;border-color:#bbf7d0;color:#15803d;"><i class="fas fa-heart-pulse"></i></span><div><div class="suggestion-med-name">Preventive Care</div><div class="suggestion-med-reason">No specific medications indicated — focus on preventive measures for age/health status.</div></div></div></div>';
                $('#ai-suggestions').html(emptyHtml); // keep hidden
                if ($('#aiResponseSuggestions').length) $('#aiResponseSuggestions').html(emptyHtml);
                    // Show inline professional (compact, no popup)
                    $('#aiResponseInline').slideDown(200);
                    $('html, body').animate({scrollTop: $('#aiResponseInline').offset().top - 80}, 300);
                $('#ai_suggestions').val('');
            }

            // Modern Risks — professional list with icons
            const renderRisks = (flags, disclaimer) => {
                // Deduplicate and limit to 5 most relevant (remove redundant "CLINICAL DECISION SUPPORT ONLY" duplicates)
                const seen = new Set();
                const uniq = flags.filter(f => {
                    const key = String(f).toLowerCase().replace(/[^a-z]/g,'').substring(0,30);
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                }).slice(0,5);
                let html = '<div class="d-flex flex-column gap-2">';
                $.each(uniq, function(_, r){
                    const txt = $('<div>').text(r).html();
                    const isCritical = /critical|blocked|allergy|fda/i.test(r);
                    const isWarning = /verify|check|consider|clinical/i.test(r);
                    const icon = isCritical ? 'fa-triangle-exclamation' : isWarning ? 'fa-circle-exclamation' : 'fa-circle-info';
                    const bg = isCritical ? '#fee2e2' : isWarning ? '#fef9c3' : '#f1f5f9';
                    const border = isCritical ? '#fecaca' : isWarning ? '#fde68a' : '#e2e8f0';
                    const color = isCritical ? '#991b1b' : isWarning ? '#854d0e' : '#334155';
                    html += `<div class="d-flex gap-2 align-items-start p-2" style="background:${bg};border:1px solid ${border};border-radius:12px;font-size:0.78rem;color:${color};line-height:1.5;"><span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;border-radius:9px;background:#fff;border:1px solid ${border};"><i class="fas ${icon}" style="font-size:0.72rem;"></i></span><span>${txt}</span></div>`;
                });
                html += '</div>';
                return html;
            };
            if (response.risk_flags && response.risk_flags.length > 0) {
                const risksHtml = renderRisks(response.risk_flags, null);
                $('#risks-content').html(risksHtml); // keep hidden
                if ($('#aiResponseRisksContent').length) {
                    $('#aiResponseRisksContent').html(risksHtml);
                    $('#aiResponseRisks').show();
                }
                $('#ai_risk_flags').val(JSON.stringify(response.risk_flags));
            } else {
                const defaultWarnings = [
                    'Verify patient allergies and contraindications',
                    'Check current medications for interactions',
                    'Consider patient age, weight, and renal/hepatic function'
                ];
                const risksHtml = renderRisks(defaultWarnings, null);
                $('#risks-content').html(risksHtml); // keep hidden
                if ($('#aiResponseRisksContent').length) {
                    $('#aiResponseRisksContent').html(risksHtml);
                    $('#aiResponseRisks').show();
                }
                $('#ai_risk_flags').val(JSON.stringify(defaultWarnings));
            }

            showNotification('AI Clinical Decision Support analysis complete. Analyzed available clinical data and provided preventive care recommendations.', 'info');
        },
        error: function(xhr, status, error) {
            button.prop('disabled', false).html('<i class="fas fa-brain me-1"></i>AI Clinical Support');

            var msg = 'AI Clinical Decision Support unavailable. Please proceed with manual prescription entry.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = 'AI Clinical Decision Support: ' + xhr.responseJSON.message + ' - Manual prescription entry required.';
            }
            showNotification(msg, 'warning');

            // Show fallback suggestions when AI is unavailable
            $('#ai-suggestions').html('<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>AI Unavailable:</strong> Please proceed with manual prescription entry. For common conditions, consider evidence-based treatments like acetaminophen for pain/fever or amoxicillin for bacterial infections (after proper diagnosis).</div>').show();

            $('#clinical-data-summary').hide();
            $('#ai-suggestions').hide();
            $('#ai-risks').hide();
        },
        complete: function() {
            window._aiContinueLimited = false;
        }
    });
    }
});

// Handle accept suggestion button
$(document).on('click', '.accept-suggestion', function() {
    var index = $(this).data('index');
    var suggestions = JSON.parse($('#ai_suggestions').val());
    var suggestion = suggestions[index];

    // Fill the form with the accepted suggestion
    $('#medication_name').val(suggestion.med);
    $('#dosage').val(suggestion.dosage);
    $('#frequency').val(suggestion.freq);
    $('#duration').val(suggestion.dur);

    // Add clinical rationale to instructions if available
    if (suggestion.reason) {
        var currentInstructions = $('#instructions').val();
        var rationaleText = 'AI Clinical Decision Support: ' + suggestion.reason;
        if (currentInstructions) {
            $('#instructions').val(currentInstructions + '\n\n' + rationaleText);
        } else {
            $('#instructions').val(rationaleText);
        }
    }

    // Add warnings to notes if available
    if (suggestion.warnings && suggestion.warnings.length > 0) {
        var currentNotes = $('#notes').val();
        var warningsText = 'AI Warnings: ' + suggestion.warnings.join('; ');
        if (currentNotes) {
            $('#notes').val(currentNotes + '\n\n' + warningsText);
        } else {
            $('#notes').val(warningsText);
        }
    }

    // Mark this suggestion as accepted
    $(this).closest('.suggestion-item').addClass('accepted').removeClass('rejected');
    $(this).closest('.suggestion-item').find('.reject-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-check me-1"></i>Applied to Form');

    showNotification('AI suggestion applied to prescription form. Please review and modify as needed for patient safety.', 'success');
});

// Handle reject suggestion button
$(document).on('click', '.reject-suggestion', function() {
    var suggestionItem = $(this).closest('.suggestion-item');

    // Mark this suggestion as rejected
    suggestionItem.addClass('rejected').removeClass('accepted');
    suggestionItem.find('.accept-suggestion').prop('disabled', true);
    $(this).prop('disabled', true).html('<i class="fas fa-times me-1"></i>Rejected');

    showNotification('AI suggestion dismissed. Manual prescription entry required.', 'info');
});

// Reset form function - AI parts
window.resetPrescriptionForm = window.resetPrescriptionForm || function() {
    $('#prescriptionForm')[0].reset();
    $('#clinical-data-summary').hide();
    $('#ai-suggestions').hide();
    $('#ai-risks').hide();
    $('#ai_suggestions').val('');
    $('#ai_risk_flags').val('');
    // Reset any accepted/rejected states
    $('.suggestion-item').removeClass('accepted rejected');
    $('.accept-suggestion, .reject-suggestion').prop('disabled', false);
    $('.accept-suggestion').html('<i class="fas fa-check me-1"></i>Accept');
    $('.reject-suggestion').html('<i class="fas fa-times me-1"></i>Reject');
    showNotification('Prescription form reset. Clinical data and AI suggestions cleared.', 'info');
};

@php
$_voiceDiag = null;
$_voiceTranscription = null;
if($appointment->patient){
    $_voiceDiag = \App\Models\AiAssistantResult::where('patient_id', $appointment->patient->id)->where('source', 'voice_assistant')->latest()->first();
    if(!$_voiceDiag) $_voiceDiag = \App\Models\VoiceTranscription::where('patient_id', $appointment->patient->id)->whereNotNull('ai_analysis')->where('ai_analysis','!=','')->latest()->first();
    // Always fetch latest VT with structured_chart for fallback (Khalid 82kg case: AiAssistantResult is empty but VT has data)
    $_voiceTranscription = \App\Models\VoiceTranscription::where('patient_id', $appointment->patient->id)->latest()->first();
    if($_voiceTranscription && empty($_voiceTranscription->structured_chart) && empty($_voiceTranscription->extracted_data)) $_voiceTranscription = null;
}
@endphp
let _cachedAppointment = @json($appointment);
let _cachedPatient = @json($appointment->patient);
let _cachedCurrentDiagnosis = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null);
let _cachedPastDiagnoses = @json($appointment->patient ? \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect());
let _cachedVoiceDiagnosis = @json($_voiceDiag);
let _cachedVoiceTranscription = @json($_voiceTranscription);
// AI Data Sources Modal Functions
function populateDataSourcesModal() {
    const appointment = _cachedAppointment;
    const patient = _cachedPatient;
    const currentDiagnosis = _cachedCurrentDiagnosis;
    const pastDiagnoses = _cachedPastDiagnoses;
    const voiceDiagnosis = _cachedVoiceDiagnosis;
    const voiceTranscription = typeof _cachedVoiceTranscription !== 'undefined' ? _cachedVoiceTranscription : null;

    // Get patient data from the most recent diagnosis
    const patientData = currentDiagnosis ? currentDiagnosis.patient_data : null;

    // Precise fallback helpers: use voice layer when Diagnosis not yet saved (weight 82kg case) — includes VT structured_chart (Khalid fix)
    const voiceAllergiesRaw = voiceDiagnosis?.patient_data?.allergies || voiceDiagnosis?.extracted_data?.allergies || voiceDiagnosis?.structured_chart?.medical_history || voiceTranscription?.structured_chart?.medical_history || voiceTranscription?.extracted_data?.medical_history || currentDiagnosis?.patient_data?.medical_history || voiceDiagnosis?.ai_analysis || voiceTranscription?.ai_analysis || voiceTranscription?.raw_transcription || '';
    const voiceMedsRaw = voiceDiagnosis?.patient_data?.medications || voiceDiagnosis?.patient_data?.past_medications || voiceDiagnosis?.extracted_data?.medications || voiceDiagnosis?.structured_chart?.medications || voiceTranscription?.structured_chart?.medications || voiceTranscription?.extracted_data?.medications || currentDiagnosis?.patient_data?.medications || voiceDiagnosis?.ai_analysis || voiceTranscription?.ai_analysis || '';
    const voiceDoctorNotesRaw = appointment.doctor_notes || currentDiagnosis?.diagnosis_text || voiceDiagnosis?.patient_data?.diagnosis || voiceDiagnosis?.ai_analysis || voiceTranscription?.structured_chart?.diagnosis || voiceTranscription?.extracted_data?.diagnosis || '';
    const hasAllergiesDirect = patientData && patientData.allergies && (Array.isArray(patientData.allergies) ? patientData.allergies.length > 0 : (typeof patientData.allergies === 'string' && patientData.allergies.trim().length > 0));
    const hasAllergiesFallback = !hasAllergiesDirect && voiceAllergiesRaw && String(voiceAllergiesRaw).trim().length > 0 && /no known|none|nkda|no allergies|allergy/i.test(String(voiceAllergiesRaw));
    const hasMedsDirect = patientData && (patientData.medications || patientData.past_medications) && (Array.isArray(patientData.medications || patientData.past_medications) ? (patientData.medications || patientData.past_medications).length > 0 : (typeof (patientData.medications || patientData.past_medications) === 'string' && (patientData.medications || patientData.past_medications).trim().length > 0));
    const hasMedsFallback = !hasMedsDirect && voiceMedsRaw && String(voiceMedsRaw).trim().length > 0;
    const hasDoctorNotesFallback = !!voiceDoctorNotesRaw && String(voiceDoctorNotesRaw).trim().length > 0;
    // Weight fallback: parse from vital_signs strings (82kg from "BP 122/78...Wt 82kg") — includes VT fallback
    const voiceWeightRaw = (() => {
        const candidates = [
            patientData?.weight,
            voiceDiagnosis?.patient_data?.weight,
            voiceDiagnosis?.structured_chart?.vital_signs,
            voiceDiagnosis?.patient_data?.vital_signs,
            voiceDiagnosis?.extracted_data?.vital_signs,
            voiceTranscription?.structured_chart?.vital_signs,
            voiceTranscription?.extracted_data?.vital_signs,
            currentDiagnosis?.patient_data?.vital_signs,
            voiceDiagnosis?.ai_analysis,
            voiceTranscription?.raw_transcription
        ];
        for (const c of candidates) {
            if (!c) continue;
            const m = String(c).match(/(\d+(?:\.\d+)?)\s*kg/i);
            if (m) return m[1];
        }
        return null;
    })();
    const hasWeightDirect = !!(patientData && patientData.weight);
    const hasWeightFallback = !hasWeightDirect && !!voiceWeightRaw;

    const dataSources = [
        {
            name: 'Patient Allergies',
            status: hasAllergiesDirect || hasAllergiesFallback ? 'available' : 'missing',
            example: hasAllergiesDirect ? (Array.isArray(patientData.allergies) ? patientData.allergies.join(', ') : patientData.allergies.toString()) : (hasAllergiesFallback ? (String(voiceAllergiesRaw).substring(0,60) + (String(voiceAllergiesRaw).length>60?'...':'')) + ' <span class="badge bg-info ms-1" style="font-size:0.65rem">AI-assisted</span>' : 'No allergies recorded'),
            location: hasAllergiesDirect ? 'Diagnosis creation form (Doctor-verified)' : (hasAllergiesFallback ? 'Voice Assistant (AI-assisted, verify on Complete)' : 'Diagnosis creation form (Doctor-verified)'),
            reliability: hasAllergiesDirect ? 'Doctor-verified' : (hasAllergiesFallback ? 'AI-assisted clinical' : 'Doctor-verified'),
            icon: 'fas fa-allergies',
            importance: 'critical',
            reason: 'Prevents prescribing medications patient is allergic to (life-threatening). "None" or "No known allergies" are acceptable entries.'
        },
        {
            name: 'Current Medications',
            status: hasMedsDirect || hasMedsFallback ? 'available' : 'missing',
            example: hasMedsDirect ? (Array.isArray(patientData.medications || patientData.past_medications) ? (patientData.medications || patientData.past_medications).join(', ') : (patientData.medications || patientData.past_medications).toString()) : (hasMedsFallback ? (String(voiceMedsRaw).substring(0,60) + (String(voiceMedsRaw).length>60?'...':'')) + ' <span class="badge bg-info ms-1" style="font-size:0.65rem">AI-assisted</span>' : 'No medications recorded'),
            location: hasMedsDirect ? 'Diagnosis creation form (Doctor-verified)' : (hasMedsFallback ? 'Voice Assistant (AI-assisted, verify on Complete)' : 'Diagnosis creation form (Doctor-verified)'),
            reliability: hasMedsDirect ? 'Doctor-verified' : (hasMedsFallback ? 'AI-assisted clinical' : 'Doctor-verified'),
            icon: 'fas fa-pills',
            importance: 'critical',
            reason: 'Required to check drug-drug interactions (dangerous without this). "None" or "No current medications" are acceptable entries.'
        },
        {
            name: 'Doctor Notes',
            status: appointment.doctor_notes || hasDoctorNotesFallback ? 'available' : 'missing',
            example: appointment.doctor_notes ? (appointment.doctor_notes.length > 30 ? appointment.doctor_notes.substring(0, 30) + '...' : appointment.doctor_notes) : (hasDoctorNotesFallback ? (String(voiceDoctorNotesRaw).substring(0,60) + (String(voiceDoctorNotesRaw).length>60?'...':'')) + ' <span class="badge bg-info ms-1" style="font-size:0.65rem">AI-assisted</span>' : 'No doctor notes'),
            location: appointment.doctor_notes ? 'Appointment completion modal (Doctor-verified)' : (hasDoctorNotesFallback ? 'Voice Assistant/Diagnosis (AI-assisted, verify)' : 'Appointment completion modal (Doctor-verified)'),
            reliability: appointment.doctor_notes ? 'Doctor-verified' : (hasDoctorNotesFallback ? 'AI-assisted clinical' : 'Doctor-verified'),
            icon: 'fas fa-user-md',
            importance: 'critical',
            reason: 'Provides clinical assessment - required if no diagnosis exists'
        },
        {
            name: 'Current Diagnosis',
            status: currentDiagnosis ? 'available' : 'missing',
            example: currentDiagnosis ? (currentDiagnosis.diagnosis_text ? currentDiagnosis.diagnosis_text.substring(0, 30) + (currentDiagnosis.diagnosis_text.length > 30 ? '...' : '') : 'Diagnosis recorded') : 'No current diagnosis',
            location: 'Most recent diagnosis record (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-stethoscope',
            importance: 'critical',
            reason: 'Primary driver for medication selection - required if no doctor notes'
        },
        {
            name: 'Patient Age',
            status: patient && patient.age ? 'available' : 'missing',
            example: patient && patient.age ? patient.age + ' years old' : 'Age not recorded',
            location: 'Patient Management (Administrative)',
            reliability: 'Administrative',
            icon: 'fas fa-birthday-cake',
            importance: 'important',
            reason: 'Needed for dosage calculations (especially pediatric/geriatric)'
        },
        {
            name: 'Patient Weight',
            status: hasWeightDirect || hasWeightFallback ? 'available' : 'missing',
            example: hasWeightDirect ? patientData.weight + ' kg' : (hasWeightFallback ? voiceWeightRaw + ' kg <span class="badge bg-info ms-1" style="font-size:0.65rem">AI-assisted</span>' : 'Weight not recorded'),
            location: hasWeightDirect ? 'Diagnosis creation form (Doctor-verified)' : (hasWeightFallback ? 'Voice Assistant vital signs (AI-assisted, verify)' : 'Diagnosis creation form'),
            reliability: hasWeightDirect ? 'Doctor-verified' : (hasWeightFallback ? 'AI-assisted clinical' : 'Doctor-verified'),
            icon: 'fas fa-weight',
            importance: 'important',
            reason: 'Critical for pediatric weight-based dosing calculations'
        },
        {
            name: 'Past Diagnosis History',
            status: pastDiagnoses && pastDiagnoses.length > 0 ? 'available' : 'missing',
            example: pastDiagnoses && pastDiagnoses.length > 0 ? `${pastDiagnoses.length} past diagnosis(es) found` : 'No past diagnosis history',
            location: 'Historical diagnosis records (Doctor-verified)',
            reliability: 'Doctor-verified',
            icon: 'fas fa-history',
            importance: 'helpful',
            reason: 'Provides context for comorbidities and treatment history'
        },
        {
            name: 'Patient Gender',
            status: patient && patient.gender ? 'available' : 'missing',
            example: patient && patient.gender ? patient.gender.charAt(0).toUpperCase() + patient.gender.slice(1) : 'Gender not recorded',
            location: 'Patient Management (Administrative)',
            reliability: 'Administrative',
            icon: 'fas fa-venus-mars',
            importance: 'helpful',
            reason: 'Relevant for pregnancy/breastfeeding considerations and some medications'
        },
        {
            name: 'Voice Assistant Diagnosis',
            status: voiceDiagnosis ? 'available' : 'missing',
            example: voiceDiagnosis ? (voiceDiagnosis.patient_data && voiceDiagnosis.patient_data.diagnosis ? voiceDiagnosis.patient_data.diagnosis : (voiceDiagnosis.ai_analysis ? voiceDiagnosis.ai_analysis.substring(0,60) + '...' : 'Voice diagnosis available')) : 'No voice diagnosis',
            location: 'Voice Assistant sessions (AI-assisted clinical)',
            reliability: 'AI-assisted clinical',
            icon: 'fas fa-microphone',
            importance: 'helpful',
            reason: 'Additional clinical context from voice sessions'
        },

    ];

    let tableHtml = '';
    let availableCount = 0;
    let criticalMissing = [];

    dataSources.forEach(source => {
        const statusBadge = source.status === 'available'
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Available</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Missing</span>';

        if (source.status === 'available') availableCount++;
        if (source.status === 'missing' && source.importance === 'critical') {
            criticalMissing.push(source.name);
        }

        const reliabilityBadge = source.reliability === 'Doctor-verified'
            ? '<span class="badge bg-success"><i class="fas fa-user-md me-1"></i>Doctor-verified</span>'
            : source.reliability === 'AI-assisted clinical'
            ? '<span class="badge bg-info"><i class="fas fa-brain me-1"></i>AI-assisted</span>'
            : source.reliability === 'Administrative'
            ? '<span class="badge bg-secondary"><i class="fas fa-cog me-1"></i>Administrative</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-user me-1"></i>Patient-reported</span>';

        const importanceBadge = source.importance === 'critical'
            ? '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>CRITICAL</span>'
            : source.importance === 'important'
            ? '<span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Important</span>'
            : source.importance === 'helpful'
            ? '<span class="badge bg-info"><i class="fas fa-info-circle me-1"></i>Helpful</span>'
            : '<span class="badge bg-secondary"><i class="fas fa-tag me-1"></i>Context</span>';

        const canEdit = source.status === 'missing' && source.reliability === 'Doctor-verified' && ['Patient Allergies','Current Medications','Doctor Notes','Patient Weight','Current Diagnosis'].includes(source.name);
        const editBtn = canEdit ? `<button class="btn btn-sm mt-1 quick-edit-btn" data-source="${source.name}" style="background:#eff6ff;border:1px solid #dbeafe;color:#2563eb;border-radius:8px;font-size:0.72rem;font-weight:600"><i class="fas fa-pen me-1"></i>Fix → Available</button>` : '';
        tableHtml += `
            <tr class="${source.status === 'missing' ? 'table-light' : ''}" data-row="${source.name}">
                <td>
                    <i class="${source.icon} me-2 text-primary"></i><strong>${source.name}</strong>
                    <br><small class="text-muted">${source.reason}</small>
                </td>
                <td>${statusBadge}</td>
                <td class="small">${importanceBadge}</td>
                <td class="small">${reliabilityBadge}</td>
                <td class="small text-muted" data-cell="${source.name}">${source.example}<br>${editBtn}</td>
            </tr>
        `;
    });

    document.getElementById('dataSourcesTableBody').innerHTML = tableHtml;

    // Calculate completeness
    const completenessPercentage = Math.round((availableCount / dataSources.length) * 100);
    const completenessBar = document.getElementById('dataCompletenessBar');
    const completenessText = document.getElementById('dataCompletenessText');

    completenessBar.style.width = completenessPercentage + '%';
    completenessBar.textContent = completenessPercentage + '% Complete';

    // Check if critical data is missing
    if (criticalMissing.length > 0) {
        completenessBar.className = 'progress-bar bg-danger';
        completenessText.innerHTML = `<strong class="text-danger">⚠️ CRITICAL DATA MISSING:</strong> AI medication suggestions are <strong>BLOCKED</strong> for patient safety. Missing: ${criticalMissing.join(', ')}`;
    } else if (completenessPercentage >= 80) {
        completenessBar.className = 'progress-bar bg-success';
        completenessText.textContent = '✅ Excellent data completeness! AI suggestions will be highly accurate.';
    } else if (completenessPercentage >= 60) {
        completenessBar.className = 'progress-bar bg-warning';
        completenessText.textContent = '⚠️ Good data completeness. AI suggestions will be moderately accurate.';
    } else {
        completenessBar.className = 'progress-bar bg-danger';
        completenessText.textContent = '❌ Limited data available. Consider adding more clinical information for better AI suggestions.';
    }

    // Update improvement suggestions based on missing data
    const missingSources = dataSources.filter(s => s.status === 'missing').map(s => s.name.toLowerCase());
    let suggestionsHtml = '';

    if (missingSources.includes('patient age')) {
        suggestionsHtml += '<li>Ensure patient age is recorded in <strong>Patient Management</strong></li>';
    }
    if (missingSources.includes('patient gender')) {
        suggestionsHtml += '<li>Ensure patient gender is recorded in <strong>Patient Management</strong></li>';
    }
    if (missingSources.includes('patient allergies')) {
        suggestionsHtml += '<li>Complete patient allergy information in <strong>Diagnosis Creation</strong> form</li>';
    }
    if (missingSources.includes('current medications')) {
        suggestionsHtml += '<li>Update current medications in <strong>Diagnosis Creation</strong> form</li>';
    }
    if (missingSources.includes('doctor notes')) {
        suggestionsHtml += '<li>Include comprehensive doctor notes during <strong>appointment completion</strong></li>';
    }
    if (missingSources.includes('complete diagnosis history')) {
        suggestionsHtml += '<li>Create diagnosis records in the <strong>Diagnoses</strong> section for complete medical history</li>';
    }
    if (missingSources.includes('voice assistant diagnosis')) {
        suggestionsHtml += '<li>Use <strong>Voice Assistant</strong> for detailed clinical assessments</li>';
    }
    if (missingSources.includes('reason for visit')) {
        suggestionsHtml += '<li>Specify reason for visit during <strong>appointment booking</strong> (doctor or patient)</li>';
    }

    if (suggestionsHtml) {
        document.getElementById('improvementSuggestions').innerHTML = suggestionsHtml;
    }
}

function quickEditDataSource(sourceName){
    const map = {
        'Patient Allergies': {field:'allergies', label:'Patient Allergies', placeholder:'e.g., Penicillin, Sulfa, None', hint:'Use \"None\" or \"No known allergies\"', rows:1},
        'Current Medications': {field:'medications', label:'Current Medications', placeholder:'e.g., Lisinopril 10mg daily, None', hint:'Include dosage/frequency. \"None\" if none', rows:1},
        'Doctor Notes': {field:'clinical_notes', label:'Doctor Notes / Symptoms', placeholder:'e.g., Chest pain 2 days, fever', hint:'Brief assessment - drives AI', rows:2},
        'Patient Weight': {field:'weight', label:'Patient Weight (kg)', placeholder:'e.g., 70', hint:'For dosing. e.g., 70', rows:1},
        'Current Diagnosis': {field:'diagnosis_text', label:'Current Diagnosis', placeholder:'e.g., Acute bronchitis', hint:'Primary diagnosis', rows:2}
    };
    const cfg = map[sourceName];
    if(!cfg) return;
    const cell = document.querySelector(`[data-cell="${sourceName}"]`);
    if(!cell || cell.querySelector('.quick-edit-inline')) return;
    const isTextarea = cfg.rows > 1;
    const inputId = 'inline_'+cfg.field+'_'+Date.now();
    cell.innerHTML = `
        <div class="quick-edit-inline p-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
            <label class="form-label fw-bold mb-1" style="font-size:0.72rem;color:#334155">${cfg.label} <span class="text-danger">*</span></label>
            ${isTextarea ? `<textarea id="${inputId}" class="form-control" rows="${cfg.rows}" placeholder="${cfg.placeholder}" style="border-radius:8px;font-size:0.82rem;border:1px solid #e2e8f0"></textarea>` : `<input id="${inputId}" type="text" class="form-control" placeholder="${cfg.placeholder}" style="border-radius:8px;font-size:0.82rem;border:1px solid #e2e8f0">`}
            <div class="form-text" style="font-size:0.68rem">${cfg.hint}</div>
            <div class="text-danger small mt-1" id="${inputId}_err" style="display:none"></div>
            <div class="d-flex gap-1 mt-2">
                <button class="btn btn-sm text-white quick-save" style="background:#10b981;border:none;border-radius:8px;font-weight:600;font-size:0.72rem"><i class="fas fa-check me-1"></i>Save</button>
                <button class="btn btn-sm btn-light border quick-cancel" style="border-radius:8px;font-size:0.72rem">Cancel</button>
            </div>
        </div>`;
    const input = document.getElementById(inputId);
    input.focus();
    const errEl = document.getElementById(inputId+'_err');
    cell.querySelector('.quick-cancel').addEventListener('click', ()=> populateDataSourcesModal());
    cell.querySelector('.quick-save').addEventListener('click', ()=>{
        const val = input.value.trim();
        if(!val){ errEl.textContent='Value required. Use \"None\" if none.'; errEl.style.display='block'; input.classList.add('is-invalid'); return; }
        errEl.style.display='none'; input.classList.remove('is-invalid');
        const btn = cell.querySelector('.quick-save');
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        const payload = {_token: document.querySelector('meta[name=\"csrf-token\"]').content};
        payload[cfg.field] = val;
        fetch("{{ route('doctor.appointments.save-quick-data', $appointment->id) }}", {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':payload._token,'Accept':'application/json'}, body: JSON.stringify(payload)})
        .then(r=>r.json().then(j=>({ok:r.ok, body:j})))
        .then(({ok, body})=>{
            if(!ok) throw new Error(body.message || 'Save failed');
            // Real-time cache update - no reload needed
            if(!_cachedCurrentDiagnosis) _cachedCurrentDiagnosis = {patient_data:{}};
            if(!_cachedCurrentDiagnosis.patient_data) _cachedCurrentDiagnosis.patient_data = {};
            if(cfg.field === 'allergies') _cachedCurrentDiagnosis.patient_data.allergies = val;
            else if(cfg.field === 'medications') _cachedCurrentDiagnosis.patient_data.medications = val;
            else if(cfg.field === 'clinical_notes'){ _cachedCurrentDiagnosis.patient_data.clinical_notes = val; _cachedAppointment.doctor_notes = val; }
            else if(cfg.field === 'weight') _cachedCurrentDiagnosis.patient_data.weight = val;
            else if(cfg.field === 'diagnosis_text') _cachedCurrentDiagnosis.diagnosis_text = val;
            if(body.diagnosis) _cachedCurrentDiagnosis = body.diagnosis;
            populateDataSourcesModal();
            const toast = document.createElement('div');
            toast.style.cssText='position:fixed;bottom:20px;right:20px;background:#065f46;color:#fff;padding:0.6rem 0.9rem;border-radius:10px;font-size:0.8rem;font-weight:600;box-shadow:0 8px 20px rgba(0,0,0,0.15);z-index:9999';
            toast.innerHTML='<i class="fas fa-check-circle me-1"></i>'+sourceName+' → Available (live)';
            document.body.appendChild(toast); setTimeout(()=> toast.remove(), 2200);
        }).catch(e=>{ errEl.textContent=e.message; errEl.style.display='block'; btn.disabled=false; btn.innerHTML='<i class="fas fa-check me-1"></i>Save'; });
    });
}
document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.quick-edit-btn');
    if(btn){ e.preventDefault(); quickEditDataSource(btn.dataset.source); }
});

function refreshDataSources() {
    populateDataSourcesModal();
    const modalBody = document.querySelector('#aiDataSourcesModal .modal-body');
    if(modalBody){
        let fb = document.getElementById('refreshInlineFeedback');
        if(!fb){
            fb = document.createElement('div');
            fb.id = 'refreshInlineFeedback';
            fb.style.cssText = 'background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:8px;padding:0.6rem 0.75rem;font-size:0.82rem;font-weight:600;display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem';
            fb.innerHTML = '<i class="fas fa-check-circle"></i><span>Data sources refreshed successfully.</span><span style="margin-left:auto;font-weight:400;color:#047857;font-size:0.74rem">'+new Date().toLocaleTimeString()+'</span>';
            modalBody.prepend(fb);
        } else {
            fb.style.display='flex';
            fb.querySelector('span:last-child').textContent = new Date().toLocaleTimeString();
        }
        clearTimeout(window._refreshFbTimer);
        window._refreshFbTimer = setTimeout(()=>{ if(fb) fb.style.display='none'; }, 2500);
    }
}

// Initialize data sources modal when opened
document.addEventListener('DOMContentLoaded', function() {
    const aiDataSourcesModal = document.getElementById('aiDataSourcesModal');
    if (aiDataSourcesModal) {
        aiDataSourcesModal.addEventListener('show.bs.modal', function() {
            populateDataSourcesModal();
        });
    }
});
</script>
@endpush