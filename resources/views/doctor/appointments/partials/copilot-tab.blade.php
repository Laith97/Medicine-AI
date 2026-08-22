                <!-- AI Medical Copilot Section -->
                @if(auth()->check() && auth()->user()->isDoctor())
                <div id="ai-medical-copilot-section" class="table-card" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-brain me-2"></i>AI Medical Copilot
                            </h4>
                            <p class="mb-0 text-muted small">AI-powered clinical decision support for this appointment</p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAIMedicalCopilotForm()">
                            <i class="fas fa-times me-1"></i>Close
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div class="copilot-loading" id="copilotLoadingSection">
                        <div class="copilot-loading-spinner mx-auto"></div>
                        <h5 class="text-primary text-center">AI Medical Copilot is analyzing...</h5>
                        <p class="text-muted text-center">Processing clinical data and generating decision support insights</p>
                    </div>

                    <!-- Error State -->
                    <div class="copilot-error alert alert-danger" id="copilotErrorSection" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="copilotErrorMessageSection"></span>
                    </div>

                    <!-- Content Area -->
                    <div id="copilotContentSection" style="display: none;">
                        <!-- Tab Navigation -->
                        <div class="d-flex justify-content-start mb-3 border-bottom">
                            <button class="copilot-tab active" data-tab="summary">
                                <i class="fas fa-file-medical me-1"></i>Summary
                            </button>
                            <button class="copilot-tab" data-tab="considerations">
                                <i class="fas fa-list-check me-1"></i>Considerations
                            </button>
                            <button class="copilot-tab" data-tab="questions">
                                <i class="fas fa-question-circle me-1"></i>Questions
                            </button>
                            <button class="copilot-tab" data-tab="red-flags">
                                <i class="fas fa-flag me-1"></i>Red Flags
                            </button>
                            <button class="copilot-tab" data-tab="history">
                                <i class="fas fa-history me-1"></i>Patient History
                            </button>
                        </div>

                        <!-- Tab Content -->
                        <div id="copilotTabsSection">
                            <!-- Summary Tab -->
                            <div class="copilot-tab-content active" data-tab-content="summary">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-file-medical me-2"></i>Medical Case Summary
                                        </h6>
                                        <span class="badge copilot-badge bg-primary">
                                            <i class="fas fa-check-circle me-1"></i>AI-Generated
                                        </span>
                                    </div>
                                    <div class="copilot-content" id="copilotSummarySection">
                                        <p class="text-muted">Loading medical case summary...</p>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeSummaryInNoteSection">
                                        <label class="form-check-label" for="includeSummaryInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Considerations Tab -->
                            <div class="copilot-tab-content" data-tab-content="considerations">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-list-check me-2"></i>Differential Considerations
                                        </h6>
                                        <span class="badge copilot-badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Not Diagnoses
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-warning" id="copilotConsiderationsSection">
                                        <p class="text-muted">Loading differential considerations...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>⚠️ For clinical consideration only. Physician judgment required.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeConsiderationsInNoteSection">
                                        <label class="form-check-label" for="includeConsiderationsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Questions Tab -->
                            <div class="copilot-tab-content" data-tab-content="questions">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-question-circle me-2"></i>Suggested Follow-up Questions
                                        </h6>
                                        <span class="badge copilot-badge bg-info">
                                            <i class="fas fa-lightbulb me-1"></i>Clinical Insights
                                        </span>
                                    </div>
                                    <div class="copilot-content copilot-info" id="copilotQuestionsSection">
                                        <p class="text-muted">Loading follow-up questions...</p>
                                    </div>
                                    <div class="copilot-disclaimer">
                                        <strong>💡 These questions help raise diagnostic quality and reduce oversight.</strong>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input copilot-checkbox" type="checkbox" id="includeQuestionsInNoteSection">
                                        <label class="form-check-label" for="includeQuestionsInNoteSection">
                                            Include in clinical note
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Red Flags Tab -->
                            <div class="copilot-tab-content" data-tab-content="red-flags">
                                <div class="copilot-section">
                                    <div class="copilot-section-header">
                                        <h6 class="copilot-section-title">
                                            <i class="fas fa-flag me-2"></i>Red Flags Detection
                                        </h6>
                                        <span class="badge copilot-badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Urgent Attention
                                        </span>
