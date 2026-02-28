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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content summary-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="summaryModalLabel" style="color: #fff">
                    <i class="fas fa-user-doctor me-2"></i><span id="patientSummaryTitle">Patient Summary</span>
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printSummaryBtn">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body response-modal-body">
                <!-- Patient Info Section -->
                <div class="patient-info-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-id-card me-2"></i>Patient Information</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Name:</strong> <span id="summaryPatientName"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Age:</strong> <span id="summaryPatientAge"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Gender:</strong> <span id="summaryPatientGender"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Visit Summary Section -->
                <div class="visit-summary-section mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-clipboard-list me-2"></i>Visit Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="visitSummaryContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading patient history...</p>
                        </div>
                    </div>
                </div>

                <!-- Patient Summary Section -->
                <div class="patient-summary-section">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-2"><i class="fas fa-file-medical me-2"></i>Patient Summary</h6>
                        <hr class="flex-grow-1 ms-2">
                    </div>
                    <div id="aiSummaryContainer" class="response-text">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Generating patient summary...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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