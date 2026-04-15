<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: #fff">
                    <i class="fas fa-stethoscope me-2"></i>Diagnosis
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printResponseBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- Doctor's Diagnosis Section -->
                <div class="diagnosis-section mb-4">
                    <div class="medcura-level1">
                        <div class="level1-header level-header">
                            <i class="fas fa-user-md me-2"></i>
                            <span>Doctor's Diagnosis</span>
                        </div>
                        <div id="diagnosisContent" class="response-text">
                            <!-- Doctor's diagnosis will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Patient Information Section -->
                <div class="patient-info-section mb-4" id="patientInfoSection" style="display: none;">
                    <div class="medcura-section">
                        <h4 class="section-header">
                            <i class="fas fa-id-card me-2"></i>Patient Information
                        </h4>
                        <div class="section-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <span id="modalPatientName"></span></p>
                                    <p><strong>Age:</strong> <span id="modalPatientAge"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Gender:</strong> <span id="modalPatientGender"></span></p>
                                    <p><strong>Date:</strong> <span id="modalDiagnosisDate"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Professional Patient Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title fw-bold" id="summaryModalLabel">
                    <i class="fas fa-user-md me-2"></i>Patient Medical Summary
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Patient Header -->
                <div class="patient-header p-4 bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-dark" id="patientName"></h4>
                                    <div class="patient-details">
                                        <span class="badge bg-primary me-2">Age: <span id="patientAge"></span></span>
                                        <span class="badge bg-info me-2">Gender: <span id="patientGender"></span></span>
                                        <span class="badge bg-success">Visits: <span id="patientVisits"></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                            <button class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Medical Info Cards -->
                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header bg-info">
                                    <i class="fas fa-thermometer-half"></i>
                                    <span>Current Symptoms</span>
                                </div>
                                <div class="info-body" id="symptomsContent">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header bg-primary">
                                    <i class="fas fa-history"></i>
                                    <span>Medical History</span>
                                </div>
                                <div class="info-body" id="historyContent">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header bg-warning">
                                    <i class="fas fa-pills"></i>
                                    <span>Medications</span>
                                </div>
                                <div class="info-body" id="medicationsContent">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-header bg-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Allergies</span>
                                </div>
                                <div class="info-body" id="allergiesContent">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div class="px-4 pb-4">
                    <h6 class="mb-3"><i class="fas fa-clipboard-list me-2 text-success"></i>Recent Visits</h6>
                    <div class="visit-history" id="visitHistory">
                    </div>
                </div>

                <!-- AI Summary -->
                <div class="px-4 pb-4">
                    <h6 class="mb-3"><i class="fas fa-brain me-2 text-primary"></i>AI Medical Summary</h6>
                    <div class="visit-history" id="aiSummaryContent">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="viewDetailsBtn">
                    <i class="fas fa-external-link-alt me-1"></i>View Full Details
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.info-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.info-header {
    padding: 12px 16px;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-body {
    padding: 16px;
    min-height: 80px;
}

.visit-history {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
}

.visit-item {
    background: white;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    border-left: 4px solid #007bff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.visit-item:last-child {
    margin-bottom: 0;
}
</style>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="appointmentModalLabel" style="color: #fff">
                    <i class="fas fa-calendar-check me-2"></i>Appointment Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body response-modal-body">
                <div class="appointment-details">
                    <!-- Appointment details will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-custom-primary" id="rescheduleBtn">
                    <i class="fas fa-calendar-alt me-1"></i>Reschedule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Patient Diagnoses Modal -->
<div class="modal fade" id="diagnosesModal" tabindex="-1" aria-labelledby="diagnosesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%); color: white;">
                <h5 class="modal-title" id="diagnosesModalLabel">
                    <i class="fas fa-user-injured me-2"></i>Patient Medical Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <!-- Patient Info -->
                <div class="patient-info-card mb-4 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-1"><strong><i class="fas fa-user me-2"></i>Name:</strong> <span id="diagPatientName">-</span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong><i class="fas fa-birthday-cake me-2"></i>Age:</strong> <span id="diagPatientAge">-</span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><strong><i class="fas fa-venus-mars me-2"></i>Gender:</strong> <span id="diagPatientGender">-</span></p>
                        </div>
                        <div class="col-md-2">
                            <p class="mb-1"><strong>Total:</strong> <span id="diagTotalCount" class="badge bg-success">0</span></p>
                        </div>
                    </div>
                </div>

                <!-- Patient Medical Info -->
                <div class="patient-medical-info mt-3">
                    <div class="row g-3">
                        <!-- Symptoms -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-info bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-thermometer-half me-2"></i>Symptoms</h6>
                                </div>
                                <div class="card-body">
                                    <div id="modalSymptoms" class="text-muted">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <!-- Medical History -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Medical History</h6>
                                </div>
                                <div class="card-body">
                                    <div id="modalMedicalHistory" class="text-muted">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <!-- Current Medications -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-warning bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-pills me-2"></i>Current Medications</h6>
                                </div>
                                <div class="card-body">
                                    <div id="modalMedications" class="text-muted">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <!-- Allergies -->
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-danger bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Allergies</h6>
                                </div>
                                <div class="card-body">
                                    <div id="modalAllergies" class="text-muted">Loading...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnoses List -->
                <div class="diagnoses-section mt-4">
                    <h6 class="mb-3"><i class="fas fa-clipboard-check me-2"></i>Diagnoses History</h6>
                    <div id="diagnosesList" class="diagnoses-container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <a href="#" id="viewFullDiagnosisBtn" class="btn btn-light text-primary">
                    <i class="fas fa-external-link-alt me-1"></i>View Full Details
                </a>
            </div>
        </div>
    </div>
</div>