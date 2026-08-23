<!-- Floating Help Assistant -->
<div class="floating-assistant">
    <div class="assistant-bubble" id="assistantBubble" style="display: none;">
        <div class="bubble-content">
            <p id="contextual-tip">💡 Tip: Click "Start Consultation" to begin AI-assisted diagnosis</p>
            <button class="close-bubble" onclick="hideAssistantBubble()">&times;</button>
        </div>
    </div>
    <button class="assistant-toggle" onclick="toggleAssistant()">
        <i class="fas fa-question-circle"></i>
    </button>
</div>

<style>
.floating-assistant {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
}

.assistant-toggle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.assistant-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.assistant-bubble {
    position: absolute;
    bottom: 60px;
    right: 0;
    background: white;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid #e9ecef;
    min-width: 280px;
    max-width: 320px;
    animation: slideUp 0.3s ease;
}

.bubble-content {
    padding: 1rem;
    position: relative;
}

.bubble-content p {
    margin: 0;
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.4;
}

.close-bubble {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-bubble:hover {
    color: #dc3545;
}

.assistant-bubble::after {
    content: '';
    position: absolute;
    bottom: -8px;
    right: 20px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .floating-assistant {
        bottom: 15px;
        right: 15px;
    }
    
    .assistant-toggle {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
    
    .assistant-bubble {
        min-width: 250px;
        max-width: 280px;
    }
}
</style>

<script>
let assistantVisible = false;
let currentPage = window.location.pathname;

// Contextual tips based on current page
const contextualTips = {
    '/dashboard': {
        noAppointments: "💡 No appointments today? Start a walk-in consultation →",
        hasAppointments: "📅 Review your next appointment details →",
        endOfDay: "📊 Review today's completed cases →"
    },
    '/ai/ambient-listening': {
        noPatientSelected: "👤 Select a patient first to begin recording ↑",
        recordingActive: "🎙️ Speak clearly, AI is listening and transcribing...",
        recordingComplete: "🧠 Generate AI analysis from your transcript ↓"
    },
    '/voice-assistant': {
        noPatientSelected: "👤 Select a patient first to begin recording ↑",
        recordingActive: "🎙️ Speak clearly, AI is listening and transcribing...",
        recordingComplete: "🧠 Generate AI analysis from your transcript ↓"
    },
    '/cases': {
        noCases: "🩺 No cases yet? Start your first consultation →",
        hasCases: "📝 Click any case to add notes or schedule follow-up →"
    },
    '/doctor/appointments': {
        beforeAppointment: "📋 Review patient history before the appointment →",
        duringAppointment: "🎙️ Start AI consultation for this appointment →",
        afterAppointment: "✅ Mark complete and add notes →",
        confirmed: "🎙️ Ready to start AI consultation →",
        completed: "📊 Review AI analysis and add prescriptions →",
        pending: "⏳ Confirm appointment to proceed →"
    }
};

function getContextualTip() {
    const tips = contextualTips[currentPage];
    if (!tips) return "💡 Need help? Click here for guidance";
    
    // Check page context and return appropriate tip
    if (currentPage === '/dashboard') {
        const appointmentCards = document.querySelectorAll('[data-appointments]');
        if (appointmentCards.length === 0) return tips.noAppointments;
        return tips.hasAppointments;
    }
    
    if (currentPage.includes('ambient-listening') || currentPage.includes('voice-assistant')) {
        const patientSelect = document.getElementById('patientSelect');
        const recordingBtn = document.querySelector('.recording-active');
        
        if (!patientSelect || !patientSelect.value) return tips.noPatientSelected;
        if (recordingBtn) return tips.recordingActive;
        return tips.recordingComplete;
    }
    
    if (currentPage === '/cases') {
        const caseRows = document.querySelectorAll('.patient-row');
        if (caseRows.length === 0) return tips.noCases;
        return tips.hasCases;
    }
    
    if (currentPage.includes('/doctor/appointments/')) {
        const statusBadge = document.querySelector('.status-badge');
        if (statusBadge) {
            const statusText = statusBadge.textContent.toLowerCase();
            if (statusText.includes('confirmed')) return tips.confirmed;
            if (statusText.includes('completed')) return tips.completed;
            if (statusText.includes('pending')) return tips.pending;
        }
        return tips.duringAppointment;
    }
    
    return "💡 Need help? Click here for guidance";
}

function updateContextualTip() {
    const tipElement = document.getElementById('contextual-tip');
    if (tipElement) {
        tipElement.textContent = getContextualTip();
    }
}

function toggleAssistant() {
    const bubble = document.getElementById('assistantBubble');
    if (assistantVisible) {
        hideAssistantBubble();
    } else {
        showAssistantBubble();
    }
}

function showAssistantBubble() {
    const bubble = document.getElementById('assistantBubble');
    updateContextualTip();
    bubble.style.display = 'block';
    assistantVisible = true;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (assistantVisible) {
            hideAssistantBubble();
        }
    }, 5000);
}

function hideAssistantBubble() {
    const bubble = document.getElementById('assistantBubble');
    bubble.style.display = 'none';
    assistantVisible = false;
}

// Update tips when page state changes
document.addEventListener('DOMContentLoaded', function() {
    // Update tips periodically
    setInterval(updateContextualTip, 3000);
    
    // Listen for page state changes
    window.addEventListener('statusUpdate', updateContextualTip);
    window.addEventListener('patientSelected', updateContextualTip);
    window.addEventListener('recordingStateChange', updateContextualTip);
    
    // Update wizard steps based on page state
    updateWizardSteps();
});

// Function to update wizard steps
function updateWizardSteps() {
    const steps = document.querySelectorAll('.step');
    if (steps.length === 0) return;
    
    // Check patient selection
    const patientSelect = document.getElementById('patientSelect');
    const hasPatient = patientSelect && patientSelect.value;
    
    // Check recording state
    const isRecording = document.querySelector('.recording-active') || 
                       document.querySelector('.status-dot.recording');
    
    // Check if transcript exists
    const transcriptContainer = document.getElementById('react-transcript-container');
    const hasTranscript = transcriptContainer && transcriptContainer.innerText.trim().length > 0;
    
    // Update step 1 (Patient Selection)
    const step1 = document.querySelector('.step[data-step="1"]');
    if (step1) {
        if (hasPatient) {
            step1.classList.remove('active');
            step1.classList.add('completed');
        } else {
            step1.classList.add('active');
            step1.classList.remove('completed');
        }
    }
    
    // Update step 2 (Recording)
    const step2 = document.querySelector('.step[data-step="2"]');
    if (step2) {
        if (hasPatient && !isRecording && hasTranscript) {
            step2.classList.remove('active');
            step2.classList.add('completed');
        } else if (hasPatient) {
            step2.classList.add('active');
            step2.classList.remove('completed');
        } else {
            step2.classList.remove('active', 'completed');
        }
    }
    
    // Update step 3 (Review & Diagnose)
    const step3 = document.querySelector('.step[data-step="3"]');
    if (step3) {
        if (hasTranscript) {
            step3.classList.add('active');
            step3.classList.remove('completed');
        } else {
            step3.classList.remove('active', 'completed');
        }
    }
}

// Listen for patient selection changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'patientSelect') {
        updateWizardSteps();
        window.dispatchEvent(new CustomEvent('patientSelected'));
    }
});

// Listen for recording state changes
window.addEventListener('statusUpdate', function(event) {
    updateWizardSteps();
});

// Listen for transcript updates
window.addEventListener('transcriptUpdate', function(event) {
    updateWizardSteps();
});

// Show tip on first visit to ambient listening
if (currentPage.includes('ambient-listening') || currentPage.includes('voice-assistant')) {
    setTimeout(() => {
        if (!assistantVisible) {
            showAssistantBubble();
        }
    }, 2000);
}
</script>