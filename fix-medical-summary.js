// JavaScript patch for Patient Medical Summary display issue
// This fixes the "No symptoms recorded", "No medical history", etc. display

// Add this to the cases.blade.php file or create a separate JS file

function populatePatientMedicalInfo(info) {
    // Symptoms
    const symptomsEl = document.getElementById('modalSymptoms');
    if (symptomsEl) {
        if (info.symptoms && info.symptoms.length > 0) {
            const symptoms = Array.isArray(info.symptoms) ? info.symptoms : [info.symptoms];
            const validSymptoms = symptoms.filter(s => s && s.trim() !== '' && s !== 'N/A');
            if (validSymptoms.length > 0) {
                symptomsEl.innerHTML = '<div class="d-flex flex-wrap gap-1">' + 
                    validSymptoms.map(s => `<span class="badge bg-info text-dark">${s}</span>`).join('') + '</div>';
            } else {
                symptomsEl.innerHTML = '<span class="text-muted">No symptoms recorded</span>';
            }
        } else {
            symptomsEl.innerHTML = '<span class="text-muted">No symptoms recorded</span>';
        }
    }

    // Medical History
    const historyEl = document.getElementById('modalMedicalHistory');
    if (historyEl) {
        if (info.past_medical_history && info.past_medical_history.length > 0) {
            const history = Array.isArray(info.past_medical_history) ? info.past_medical_history : [info.past_medical_history];
            const validHistory = history.filter(h => h && h.trim() !== '' && h !== 'N/A');
            if (validHistory.length > 0) {
                historyEl.innerHTML = '<ul class="mb-0 ps-3">' + validHistory.map(h => `<li>${h}</li>`).join('') + '</ul>';
            } else {
                historyEl.innerHTML = '<span class="text-muted">No medical history recorded</span>';
            }
        } else {
            historyEl.innerHTML = '<span class="text-muted">No medical history recorded</span>';
        }
    }

    // Medications
    const medsEl = document.getElementById('modalMedications');
    if (medsEl) {
        if (info.past_medications && info.past_medications.length > 0) {
            const meds = Array.isArray(info.past_medications) ? info.past_medications : [info.past_medications];
            const validMeds = meds.filter(m => m && m.trim() !== '' && m !== 'N/A');
            if (validMeds.length > 0) {
                medsEl.innerHTML = '<ul class="mb-0 ps-3">' + validMeds.map(m => `<li>${m}</li>`).join('') + '</ul>';
            } else {
                medsEl.innerHTML = '<span class="text-muted">No medications recorded</span>';
            }
        } else {
            medsEl.innerHTML = '<span class="text-muted">No medications recorded</span>';
        }
    }

    // Allergies
    const allergiesEl = document.getElementById('modalAllergies');
    if (allergiesEl) {
        if (info.allergies && info.allergies.length > 0) {
            const allergies = Array.isArray(info.allergies) ? info.allergies : [info.allergies];
            const validAllergies = allergies.filter(a => a && a.trim() !== '' && a !== 'N/A');
            if (validAllergies.length > 0) {
                allergiesEl.innerHTML = '<div class="d-flex flex-wrap gap-1">' + 
                    validAllergies.map(a => `<span class="badge bg-danger">${a}</span>`).join('') + '</div>';
            } else {
                allergiesEl.innerHTML = '<span class="text-muted">No allergies recorded</span>';
            }
        } else {
            allergiesEl.innerHTML = '<span class="text-muted">No allergies recorded</span>';
        }
    }
}

// Enhanced function to load patient data with better error handling
function loadPatientDataViaAPI(patientData) {
    console.log('Loading patient data for:', patientData);
    
    $.ajax({
        url: '/api/doctor/patient-management/patient-visits/' + patientData.key,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            console.log('API Response:', response);
            
            if (response.success && response.patient_medical_info) {
                // Update visit count
                document.getElementById('totalVisitsCount').textContent = response.visits ? response.visits.length : 0;
                
                // Populate medical info using the enhanced function
                populatePatientMedicalInfo(response.patient_medical_info);
                
                // Handle visit history
                if (response.visits && response.visits.length > 0) {
                    const visitContainer = document.getElementById('visitSummaryContainer');
                    let tableHtml = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th class="border-0">Date</th><th class="border-0">Type</th><th class="border-0">Diagnosis</th></tr></thead><tbody>';
                    
                    response.visits.forEach(visit => {
                        const diagnosis = visit.diagnosis ? (visit.diagnosis.length > 60 ? visit.diagnosis.substring(0, 60) + '...' : visit.diagnosis) : 'No diagnosis';
                        tableHtml += `<tr><td class="border-0">${visit.date}</td><td class="border-0"><span class="badge bg-primary">${visit.source_model || 'Visit'}</span></td><td class="border-0 small">${diagnosis}</td></tr>`;
                    });
                    
                    tableHtml += '</tbody></table></div>';
                    visitContainer.innerHTML = tableHtml;
                } else {
                    document.getElementById('visitSummaryContainer').innerHTML = '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-3"></i><h6 class="text-muted">No Visit History</h6></div>';
                }
                
                // Generate AI Summary if visits exist
                if (response.visits && response.visits.length > 0) {
                    generateAISummary(response.visits, patientData);
                } else {
                    document.getElementById('aiSummaryContainer').innerHTML = '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-muted mb-3"></i><h6 class="text-muted">No Data for Summary</h6></div>';
                }
            } else {
                console.warn('No patient medical info in response');
                showNoDataAvailable();
            }
        },
        error: function(xhr, status, error) {
            console.error('API Error:', error);
            console.error('Response:', xhr.responseText);
            showNoDataAvailable();
        }
    });
}

console.log('Patient Medical Summary JavaScript patch loaded');