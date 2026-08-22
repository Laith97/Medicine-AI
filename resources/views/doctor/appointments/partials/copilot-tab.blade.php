<!-- AI Medical Copilot Section - Professional placeholder -->
<div id="ai-medical-copilot-section" class="doctor-card mt-3" style="display:none;">
    <div class="doctor-card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-brain me-2 text-primary"></i>AI Medical Copilot</h5>
        <button type="button" class="doctor-btn doctor-btn-outline doctor-btn-sm" onclick="toggleAIMedicalCopilotForm()"><i class="fas fa-times me-1"></i>Close</button>
    </div>
    <div class="doctor-card-body">
        <div id="copilotLoadingSection" class="text-center py-4" style="display:none;">
            <div class="spinner-border text-primary mb-3"></div>
            <h6>AI analyzing...</h6>
            <p class="text-muted small">Processing clinical data</p>
        </div>
        <div id="copilotErrorSection" class="alert alert-danger" style="display:none;">
            <span id="copilotErrorMessageSection"></span>
        </div>
        <div id="copilotContentSection" style="display:none;">
            <div id="copilotSummarySection" class="mb-3"></div>
            <div id="copilotConsiderationsSection" class="mb-3"></div>
            <div id="copilotQuestionsSection" class="mb-3"></div>
            <div id="copilotRedFlagsSection" class="mb-3"></div>
            <div id="copilotHistorySection" class="mb-3"></div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" class="doctor-btn doctor-btn-primary" id="saveCopilotAnalysisSection"><i class="fas fa-save me-1"></i>Save Analysis</button>
                <button type="button" class="doctor-btn doctor-btn-outline" onclick="toggleAIMedicalCopilotForm()">Close</button>
            </div>
        </div>
        <div id="copilotInitial" class="text-center py-4">
            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;"><i class="fas fa-brain text-primary"></i></div>
            <h6>AI Medical Copilot</h6>
            <p class="text-muted small">Click to analyze this appointment with AI</p>
            <button class="doctor-btn doctor-btn-primary btn-sm" onclick="initializeAIMedicalCopilot({{ $appointment->id }})">Analyze Now</button>
        </div>
    </div>
</div>
