<!-- Patient Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-md me-2"></i>Patient Medical Summary</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                <!-- Patient Info -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div>
                            <h4 class="mb-1" id="patientName">Patient Name</h4>
                            <div>
                                <span class="badge bg-secondary me-2">Age: <span id="patientAge">-</span></span>
                                <span class="badge bg-secondary me-2">Gender: <span id="patientGender">-</span></span>
                                <span class="badge bg-secondary">Visits: <span id="patientVisits">0</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-thermometer-half me-2"></i>Symptoms</h6>
                        <div class="p-3 bg-light rounded" id="symptomsContent">
                            <span class="text-muted">No symptoms recorded</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-history me-2"></i>Medical History</h6>
                        <div class="p-3 bg-light rounded" id="historyContent">
                            <span class="text-muted">No medical history</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-pills me-2"></i>Medications</h6>
                        <div class="p-3 bg-light rounded" id="medicationsContent">
                            <span class="text-muted">No medications</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-exclamation-triangle me-2"></i>Allergies</h6>
                        <div class="p-3 bg-light rounded" id="allergiesContent">
                            <span class="text-muted">No allergies</span>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div>
                    <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list me-2"></i>Visit History</h6>
                    <div class="p-3 bg-light rounded" id="visitHistory">
                        <div class="text-center py-3 text-muted">
                            <p class="mb-0">No visit history available</p>
                        </div>
                    </div>
                </div>

                <!-- AI Summary -->
                <div class="mt-4">
                    <h6 class="text-primary mb-3"><i class="fas fa-brain me-2"></i>AI Medical Summary</h6>
                    <div class="p-3 bg-light rounded" id="aiSummaryContent">
                        <div class="text-center py-3 text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            <span>Generating AI summary...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('show.bs.modal', '#summaryModal', function(event) {
    var button = $(event.relatedTarget);
    if (button.length > 0) {
        var name = button.data('patient-name') || 'Unknown Patient';
        var age = button.data('patient-age') || 'N/A';
        var gender = button.data('patient-gender') || 'N/A';
        var patientKey = button.data('patient-key') || '';
        var patientId = button.data('patient-id') || '';

        // Update patient info immediately
        $('#patientName').text(name);
        $('#patientAge').text(age);
        $('#patientGender').text(gender);

        // Load actual patient data
        if (patientKey) {
            loadRealPatientData(patientKey, name, age, gender, patientId);
        }
    }
});

function loadRealPatientData(patientKey, name, age, gender, patientId) {
    // Load from server records
    try {
        const allRecords = @json($records ?? []);
        let patientRecords = allRecords.filter(record => 
            record.patient_key === patientKey || 
            (record.name === name && record.age == age && record.gender === gender)
        );

        // Update visit count
        $('#patientVisits').text(patientRecords.length);

        if (patientRecords.length > 0) {
            const latest = patientRecords[patientRecords.length - 1];
            
            // Update symptoms
            if (latest.symptoms && latest.symptoms !== 'N/A') {
                const symptoms = latest.symptoms.split(',').map(s => s.trim()).filter(s => s);
                if (symptoms.length > 0) {
                    $('#symptomsContent').html(symptoms.map(s => `<span class="badge bg-info me-1 mb-1">${s}</span>`).join(''));
                }
            }

            // Update visit history
            let visitHtml = '';
            patientRecords.forEach((record, index) => {
                const date = new Date(record.created_at).toLocaleDateString();
                const diagnosis = record.ai_response || record.diagnosis_text || 'No diagnosis';
                const shortDiagnosis = diagnosis.length > 150 ? diagnosis.substring(0, 150) + '...' : diagnosis;
                
                visitHtml += `
                    <div class="mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Visit ${index + 1}</strong>
                            <div>
                                <span class="badge bg-primary me-2">${record.source_model || 'Record'}</span>
                                <small class="text-muted">${date}</small>
                            </div>
                        </div>
                        <p class="mb-0 small">${shortDiagnosis}</p>
                    </div>
                `;
            });
            
            if (visitHtml) {
                $('#visitHistory').html(visitHtml);
            }

            // Generate AI Summary
            generatePatientAISummary(patientRecords, name, age, gender);
        }
    } catch (error) {
        console.log('Error loading patient data:', error);
    }
}

function generatePatientAISummary(patientRecords, name, age, gender) {
    const summaryData = {
        patient_id: patientRecords[0]?.patient_id || 0,
        patient_name: name,
        patient_age: age,
        patient_gender: gender,
        visit_count: patientRecords.length,
        visits: patientRecords.map((record, index) => ({
            visit_number: index + 1,
            date: new Date(record.created_at).toLocaleDateString(),
            diagnosis: record.ai_response || record.diagnosis_text || 'No diagnosis'
        }))
    };

    $.ajax({
        url: '/patient/summary',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json'
        },
        data: JSON.stringify(summaryData),
        success: function(response) {
            if (response.success) {
                $('#aiSummaryContent').html(response.summary);
            } else {
                $('#aiSummaryContent').html('<span class="text-muted">Could not generate AI summary</span>');
            }
        },
        error: function() {
            $('#aiSummaryContent').html('<span class="text-muted">Error generating AI summary</span>');
        }
    });
}
</script>