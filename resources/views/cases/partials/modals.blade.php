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

<!-- Patient Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);">
                <h5 class="modal-title fw-bold text-white" id="summaryModalLabel">
                    <i class="fas fa-user-md me-2"></i>Patient Medical Summary
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Patient Info -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light border rounded-3">
                    <div class="d-flex align-items-center justify-content-center text-white rounded-circle flex-shrink-0" style="width: 56px; height: 56px; background: linear-gradient(135deg, #2c5aa0 0%, #1e3a8a 100%);">
                        <i class="fas fa-user" style="font-size: 1.4rem;"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 text-dark" id="patientName"></h4>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge text-bg-primary">Age: <span id="patientAge"></span></span>
                            <span class="badge text-bg-info">Gender: <span id="patientGender"></span></span>
                            <span class="badge text-bg-success">Visits: <span id="patientVisits"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom fw-semibold">
                                <i class="fas fa-thermometer-half me-2 text-info"></i>Current Symptoms
                            </div>
                            <div class="card-body small" id="symptomsContent"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom fw-semibold">
                                <i class="fas fa-history me-2 text-primary"></i>Medical History
                            </div>
                            <div class="card-body small" id="historyContent"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom fw-semibold">
                                <i class="fas fa-pills me-2 text-warning"></i>Medications
                            </div>
                            <div class="card-body small" id="medicationsContent"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom fw-semibold">
                                <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Allergies
                            </div>
                            <div class="card-body small" id="allergiesContent"></div>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div class="mb-4">
                    <h6 class="mb-2 fw-semibold"><i class="fas fa-clipboard-list me-2 text-success"></i>Recent Visits</h6>
                    <div id="visitHistory"></div>
                </div>

                <!-- AI Summary -->
                <div>
                    <h6 class="mb-2 fw-semibold"><i class="fas fa-brain me-2 text-primary"></i>AI Medical Summary</h6>
                    <div class="p-3 bg-light rounded-3" id="aiSummaryContent"></div>
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