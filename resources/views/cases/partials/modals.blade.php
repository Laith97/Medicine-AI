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

<!-- Patient Summary Modal - modern light -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 20px 50px rgba(15,23,42,0.12)">
            <div class="modal-header" style="background:#ffffff;border-bottom:1px solid #f1f5f9;padding:1rem 1.25rem">
                <div class="d-flex align-items-center gap-3">
                    <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;border-radius:10px;background:rgba(222,98,98,0.08);color:#DE6262;border:1px solid rgba(222,98,98,0.15)"><i class="fas fa-brain"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="summaryModalLabel" style="font-size:1rem;color:#0f172a;letter-spacing:-0.01em">Patient Medical Summary</h5>
                        <small style="font-size:0.72rem;color:#94a3b8">AI generated · Review visit history</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#fcfdff">
                <!-- Patient Info -->
                <div class="d-flex align-items-center gap-3 mb-4 p-3" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
                    <span class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-weight:700" id="patientInitial">?</span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate" style="font-size:1rem;color:#0f172a" id="patientName"></div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <span class="d-inline-flex align-items-center gap-1" style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.55rem;border-radius:99px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155">Age <span id="patientAge"></span></span>
                            <span class="d-inline-flex align-items-center gap-1" style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.55rem;border-radius:99px;background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8">Gender <span id="patientGender"></span></span>
                            <span class="d-inline-flex align-items-center gap-1" style="font-size:0.72rem;font-weight:600;padding:0.25rem 0.55rem;border-radius:99px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569">Visits <span id="patientVisits"></span></span>
                        </div>
                    </div>
                </div>

                <!-- Medical Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="h-100" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border-bottom:1px solid #f1f5f9;font-weight:600;font-size:0.84rem;color:#0f172a"><i class="fas fa-thermometer-half" style="color:#0ea5e9"></i> Current Symptoms</div>
                            <div class="p-3 small" id="symptomsContent" style="font-size:0.84rem;color:#475569;min-height:60px"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="h-100" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border-bottom:1px solid #f1f5f9;font-weight:600;font-size:0.84rem;color:#0f172a"><i class="fas fa-history" style="color:#6366f1"></i> Medical History</div>
                            <div class="p-3 small" id="historyContent" style="font-size:0.84rem;color:#475569;min-height:60px"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="h-100" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border-bottom:1px solid #f1f5f9;font-weight:600;font-size:0.84rem;color:#0f172a"><i class="fas fa-pills" style="color:#f59e0b"></i> Medications</div>
                            <div class="p-3 small" id="medicationsContent" style="font-size:0.84rem;color:#475569;min-height:60px"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="h-100" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
                            <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f8fafc;border-bottom:1px solid #f1f5f9;font-weight:600;font-size:0.84rem;color:#0f172a"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i> Allergies</div>
                            <div class="p-3 small" id="allergiesContent" style="font-size:0.84rem;color:#475569;min-height:60px"></div>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div class="mb-4">
                    <h6 class="mb-2 fw-semibold" style="font-size:0.875rem;color:#0f172a"><i class="fas fa-clipboard-list me-2" style="color:#10b981"></i>Recent Visits</h6>
                    <div id="visitHistory"></div>
                </div>

                <!-- AI Summary -->
                <div>
                    <h6 class="mb-2 fw-semibold" style="font-size:0.875rem;color:#0f172a"><i class="fas fa-brain me-2" style="color:#DE6262"></i>AI Medical Summary</h6>
                    <div class="p-3" style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;min-height:80px" id="aiSummaryContent"></div>
                </div>
            </div>
            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #f1f5f9;padding:0.85rem 1.25rem">
                <button type="button" class="btn" style="background:#ffffff;border:1px solid #e2e8f0;color:#475569;border-radius:8px;padding:0.5rem 1rem;font-weight:500;font-size:0.84rem" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn" id="viewDetailsBtn" style="background:#0f172a;border:1px solid #0f172a;color:#ffffff;border-radius:8px;padding:0.5rem 1rem;font-weight:600;font-size:0.84rem"><i class="fas fa-external-link-alt me-1"></i> View Full Details</a>
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