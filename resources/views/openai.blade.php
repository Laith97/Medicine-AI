<!-- resources/views/openai-form.blade.php -->
@extends('master')

@section('title', 'Patients Page')

@section('content')

<link rel="stylesheet" href="{{ asset('css/custom-openai.css') }}">
<!-- Include Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" />

<!-- Include Choices.js JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>

        <div class="container medical-form-container ">
            <form id="openaiForm" action="{{ url('/openai/respond') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if(isset($patientToEdit))
                    <input type="hidden" name="edit_patient_id" value="{{ $patientToEdit->id }}">
                @endif

                <div class="medical-form-card">
        
                    <div id="errorMessages"></div>
                    
                    <!-- Patient Selection -->
                    <div class="medical-form-section">
                        <h4>Patient Selection</h4>
                        <div class="row">
                            <div class="col-md-8">
                                <label for="patient_selection" class="form-label">Select Patient:</label>
                                <select id="patient_selection" name="patient_selection" class="form-select">
                                    <option value="new">New Patient</option>
                                    <!-- Patient visit counts are now passed from the controller -->
                                    
                                    @foreach($existingPatients as $patient)
                                        @php
                                            $key = $patient->name . '-' . $patient->age . '-' . $patient->gender;
                                            $visitCount = isset($simplifiedVisits[$key]) ? $simplifiedVisits[$key]['count'] : 1;
                                        @endphp
                                        <option value="{{ $patient->id }}">
                                            {{ $patient->name }} ({{ $patient->age }}y, {{ ucfirst($patient->gender) }})
                                            @if($visitCount > 1)
                                                - {{ $visitCount }} visits
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle"></i> Select "New Patient" for first-time visits or choose an existing patient to access their medical history.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Upload Medical Reports:</label>
                                <input type="file" id="reports" name="reports[]" multiple class="form-control">
                                <div id="upload-status" class="mt-2"></div>
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-file-medical"></i> Supported formats: JPG, PNG, PDF (max 10MB each)
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patient Info (only shown for new patients) -->
                    <div class="medical-form-section" id="new_patient_info">
                        <h4>Patient Information</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="name" class="form-label required">Name:</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ $patientToEdit->name ?? '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label for="age" class="form-label required">Age:</label>
                                <input type="number" id="age" name="age" class="form-control" value="{{ $patientToEdit->age ?? '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label for="gender" class="form-label required">Gender:</label>
                                <select name="gender" id="gender" class="form-select">
                                    <option value="male" {{ isset($patientToEdit) && $patientToEdit->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ isset($patientToEdit) && $patientToEdit->gender == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patient History (only shown for existing patients) -->
                    <div class="medical-form-section" id="patient_history_info" style="display: none;">
                        <div class="d-flex align-items-center mb-3">
                            <h4 class="mb-0 me-2">Patient History</h4>
                            <span id="visit_count_badge" class="badge bg-info ms-2">Visit #1</span>
                        </div>
                        <div class="alert alert-info" id="patient_history_alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="patient_history_text">Select an existing patient to see their history.</span>
                        </div>
                    </div>
        
                    <!-- Vitals --><br>
                    <div class="medical-form-section">
                        <h4>Physical Attributes / Vitals</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Weight:</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="weight" class="form-control" value="{{ $patientToEdit->weight ?? '' }}" placeholder="e.g., 70.5">
                                    <span class="input-group-text">kg</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Height:</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="height" class="form-control" value="{{ $patientToEdit->height ?? '' }}" placeholder="e.g., 175">
                                    <span class="input-group-text">cm</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Temperature:</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="temperature" class="form-control" placeholder="e.g., 37.2">
                                    <span class="input-group-text">°C</span>
                                </div>
                                <small class="form-text text-muted">Numeric value only</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Blood Pressure:</label>
                                <div class="input-group">
                                    <input type="text" name="blood_pressure" class="form-control" placeholder="e.g., 120/80">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Blood Sugar:</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="blood_sugar" class="form-control" placeholder="e.g., 85">
                                    <span class="input-group-text">mg/dL</span>
                                </div>
                                <small class="form-text text-muted">Enter numeric value only (without units)</small>
                            </div>
                        </div>
                    </div>
        
                    <!-- Symptoms --><br>
                    <div class="medical-form-section">
                        <h4>Symptoms</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Current Symptoms:</label>
                                <select id="current_symptoms" name="current_symptoms[]" multiple>
                                    @foreach($symptoms as $symptom)
                                        <option value="{{ $symptom->id }}" 
                                            {{ isset($patientToEdit) && $patientToEdit->symptoms && in_array($symptom->id, json_decode($patientToEdit->symptoms, true) ?: []) ? 'selected' : '' }}>
                                            {{ $symptom->name }}
                                        </option>
                                    @endforeach
                                </select>
                                
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Select Common Symptoms:</label>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="fever" class="form-check-input" id="fever">
                                    <label class="form-check-label" for="fever">Fever</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="cough" class="form-check-input" id="cough">
                                    <label class="form-check-label" for="cough">Cough</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="headache" class="form-check-input" id="headache">
                                    <label class="form-check-label" for="headache">Headache</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="symptoms_checkboxes[]" value="fatigue" class="form-check-input" id="fatigue">
                                    <label class="form-check-label" for="fatigue">Fatigue</label>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Tests and Diagnosis --><br>
                    <div class="medical-form-section">
                        <h4>Test Results & Preliminary Diagnosis</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Test Results:</label>
                                <textarea name="test_results" class="form-control" rows="4" placeholder="e.g., CRP: Elevated at 15 mg/L.
CBC: WBC 12,000/μL, Hgb 13.5 g/dL, Plt 250,000/μL
Urinalysis: Negative for protein, glucose, and blood
X-ray: No abnormalities detected">{{ $patientToEdit->test_results ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preliminary Diagnosis:</label>
                                <textarea name="preliminary_diagnosis" class="form-control" rows="4" placeholder="Enter your initial assessment or suspected diagnosis based on the patient's symptoms and test results."></textarea>
                            </div>
                        </div>
                    </div>
        
                <!-- Submit -->
                <div class="row mt-4">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-deep-red btn-lg px-4 "><i class="fa-solid fa-robot me-2"></i>Get Results</button>
                    </div>
                </div>


        
                </div>
            </form>
        </div>

        <div id="page-loader" style="display:none;">
            <div id='css3-spinner-svg-pulse-wrapper'>
                <svg id='css3-spinner-svg-pulse' version='1.2' height='210' width='550'
                     xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>
                    <path id='css3-spinner-pulse' stroke='#DE6262' fill='none' stroke-width='2'
                          stroke-linejoin='round'
                          d='M0,90L250,90Q257,60 262,87T267,95 270,88 273,92t6,35 7,-60T290,127 297,107s2,-11 10,-10 1,1 8,-10T319,95c6,4 8,-6 10,-17s2,10 9,11h210'></path>
                </svg>
            </div>
        </div>
        
        
<!-- Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content response-modal-content">
            <div class="modal-header response-modal-header">
                <h5 class="modal-title" id="responseModalLabel" style="color: #fff">
                    <i class="fas fa-stethoscope me-2"></i>Medical Recommendations
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body response-modal-body">
                <!-- Initial AI response -->
                <div class="response-block mb-4">
                    <pre id="openaiReply" class="response-text"></pre>
                </div>
                
                <!-- Chat continuation section -->
                <hr class="my-4">
                
                <div id="chat-continuation">
                    <h6 class="mb-3"><i class="fas fa-comments me-2"></i>Follow-up Questions</h6>
                    
                    <div id="chat-messages" class="mb-3">
                        <!-- Additional messages will appear here -->
                    </div>
                    
                    <div class="chat-input-container">
                        <form id="follow-up-form" class="d-flex">
                            @csrf
                            <input type="hidden" id="conversation-id" name="conversation_id" value="{{ session('conversation_id') ?? '' }}">
                            <input type="text" id="follow-up-message" name="message" class="form-control" placeholder="Ask a follow-up question..." required>
                            <button type="submit" class="btn btn-primary ms-2">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS for chat interface -->
<style>
    #chat-messages {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        background-color: #f9f9f9;
    }
    
    .chat-message {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 12px;
        max-width: 85%;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .user-message {
        background-color: #007bff;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 2px;
    }
    
    .ai-message {
        background-color: #f0f0f0;
        color: #333;
        border-bottom-left-radius: 2px;
    }
    
    /* Style for the initial response */
    .response-block {
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e0e0e0;
    }
    
    /* Style for the response text */
    .response-text {
        white-space: pre-wrap;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.5;
        margin: 0;
        padding: 0;
        font-size: 15px;
        color: #333;
    }
    
    /* Add a subtle animation for new messages */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chat-message {
        animation: fadeIn 0.3s ease-out;
    }
    
    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 5px;
        text-align: right;
    }
    
    .typing-indicator {
        display: flex;
        padding: 10px 15px;
    }
    
    .typing-indicator span {
        height: 8px;
        width: 8px;
        background-color: #888;
        border-radius: 50%;
        display: inline-block;
        margin: 0 2px;
        animation: typing 1.4s infinite both;
    }
    
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes typing {
        0% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
        100% { transform: translateY(0); }
    }
</style>

  
  
  

        

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


<script>
    document.getElementById('openaiForm').addEventListener('submit', function () {
        document.getElementById('page-loader').style.display = 'block';
    });

</script>


@if (session('openai_result'))
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            // Show the modal with the full response immediately
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();
            
            // Hide the page loader once the modal is shown
            document.getElementById('page-loader').style.display = 'none';
            
            // Get the AI response and display it immediately (no typing animation)
            const aiResponse = @json(session('openai_result'));
            
            // Format the response to remove markdown symbols and unwanted sections
            let formattedResponse = aiResponse
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets
                
                // Remove introduction and conclusion sections
                .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                .replace(/^.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS)/s, '')          // Remove everything before section A
                .replace(/^.*?(?=A\)\s*DIAGNOS[IE]S)/s, '')                  // Alternative section A format
                .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
                .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                .replace(/\n\nSummary.*$/s, '')
                
                // Clean up any remaining formatting issues
                .replace(/\n{3,}/g, '\n\n')                                  // Replace multiple newlines with double newlines
                .trim();                                                     // Remove leading/trailing whitespace
                
            document.getElementById('openaiReply').textContent = formattedResponse;
            
            // Set the conversation ID for follow-up messages
            if (document.getElementById('conversation-id')) {
                document.getElementById('conversation-id').value = @json(session('conversation_id') ?? '');
            }
            
            // Set up the follow-up form handler
            setupFollowUpChat();
        });
    </script>
@endif

<!-- Chat functionality script -->
<script>
    function setupFollowUpChat() {
        const followUpForm = document.getElementById('follow-up-form');
        const chatMessages = document.getElementById('chat-messages');
        
        if (followUpForm) {
            followUpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const messageInput = document.getElementById('follow-up-message');
                const message = messageInput.value.trim();
                const conversationId = document.getElementById('conversation-id').value;
                
                if (!message) return;
                
                // Add user message to chat
                addChatMessage(message, 'user');
                
                // Clear input
                messageInput.value = '';
                
                // Show typing indicator
                const typingIndicator = addTypingIndicator();
                
                // Send to server
                fetch('{{ route("openai.follow-up") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: conversationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Remove typing indicator
                    removeTypingIndicator(typingIndicator);
                    
                    if (data.success) {
                        // Add AI response with typing animation
                        addChatMessage(data.message, 'ai');
                        
                        // Update conversation ID if needed
                        if (data.conversation_id) {
                            document.getElementById('conversation-id').value = data.conversation_id;
                        }
                    } else {
                        // Show error
                        addErrorMessage(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    // Remove typing indicator
                    removeTypingIndicator(typingIndicator);
                    
                    // Show error
                    addErrorMessage('Failed to connect to the server');
                    console.error('Error:', error);
                });
            });
        }
    }
    
    // Function to simulate typing effect
    function typeText(element, text, speed = 10) {
        let i = 0;
        element.textContent = '';
        
        function typing() {
            if (i < text.length) {
                // Add character by character
                element.textContent += text.charAt(i);
                i++;
                
                // Scroll to bottom as text is being typed
                const container = element.closest('.modal-body');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
                
                // Adjust typing speed based on punctuation
                let delay = speed;
                const char = text.charAt(i-1);
                if (char === '.' || char === '!' || char === '?') {
                    delay = speed * 8; // Pause longer at end of sentences
                } else if (char === ',' || char === ';' || char === ':') {
                    delay = speed * 5; // Pause at commas and other punctuation
                } else if (char === '\n') {
                    delay = speed * 3; // Pause at new lines
                }
                
                setTimeout(typing, delay);
            }
        }
        
        typing();
    }
    
    function addChatMessage(content, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${sender}-message`;
        
        // Create message content
        if (sender === 'ai') {
            const pre = document.createElement('pre');
            pre.className = 'response-text';
            pre.style.margin = '0';
            pre.style.whiteSpace = 'pre-wrap';
            
            // Add empty pre element first
            messageDiv.appendChild(pre);
            
            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);
            
            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);
            
            // Format the response to remove markdown symbols and unwanted sections
            let formattedResponse = content
                // Remove markdown formatting
                .replace(/#{1,6}\s/g, '')  // Remove heading markers
                .replace(/\*\*/g, '')      // Remove bold markers
                .replace(/\*/g, '')        // Remove italic markers
                .replace(/- /g, '• ')      // Replace dashes with bullets
                
                // Remove introduction and conclusion sections
                .replace(/^Based on the provided.*?guidelines,.*?\n\n/s, '')  // Remove intro
                .replace(/^As a.*?specialist:.*?\n\n/s, '')                  // Remove specialty intro
                .replace(/^.*?(?=A\)\s*POSSIBLE\s*DIAGNOSIS)/s, '')          // Remove everything before section A
                .replace(/^.*?(?=A\)\s*DIAGNOS[IE]S)/s, '')                  // Alternative section A format
                .replace(/\n\nConclusion:.*$/s, '')                          // Remove conclusion
                .replace(/\n\nNote:.*$/s, '')                                // Remove notes at the end
                .replace(/^Note:.*\n\n/s, '')                                // Remove notes at the beginning
                .replace(/\n\nIn summary.*$/s, '')                           // Remove summary
                .replace(/\n\nSummary.*$/s, '')                                // Remove notes at the beginning
                
                // Clean up any remaining formatting issues
                .replace(/\n{3,}/g, '\n\n')                                  // Replace multiple newlines with double newlines
                .trim();                                                     // Remove leading/trailing whitespace
                
            // Start typing animation
            typeText(pre, formattedResponse);
        } else {
            // For user messages, show immediately
            messageDiv.textContent = content;
            
            // Add timestamp
            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            const now = new Date();
            timeDiv.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            messageDiv.appendChild(timeDiv);
            
            // Add to chat
            document.getElementById('chat-messages').appendChild(messageDiv);
        }
        
        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
    }
    
    function addTypingIndicator() {
        const id = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator';
        typingDiv.id = id;
        
        for (let i = 0; i < 3; i++) {
            const dot = document.createElement('span');
            typingDiv.appendChild(dot);
        }
        
        document.getElementById('chat-messages').appendChild(typingDiv);
        
        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
        
        return id;
    }
    
    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) {
            indicator.remove();
        }
    }
    
    function addErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.textContent = message;
        
        document.getElementById('chat-messages').appendChild(errorDiv);
        
        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
</script>





    <!-- Patient Selection Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle patient selection
            const patientSelection = document.getElementById('patient_selection');
            const newPatientInfo = document.getElementById('new_patient_info');
            const nameInput = document.getElementById('name');
            const ageInput = document.getElementById('age');
            const genderSelect = document.getElementById('gender');
            
            const patientHistoryInfo = document.getElementById('patient_history_info');
            const visitCountBadge = document.getElementById('visit_count_badge');
            const patientHistoryText = document.getElementById('patient_history_text');
            
            // Store patient data for quick access
            const patientData = @json($existingPatients);
            
            // Store visit counts - simplifiedVisits contains both patient_key and name-age-gender keys
            const patientVisits = @json($simplifiedVisits ?? []);
            
            // Debug: Log available keys for troubleshooting
            console.log('Available patient visit keys:', Object.keys(patientVisits));
            
            // Function to toggle patient info visibility
            function togglePatientInfo() {
                if (patientSelection.value === 'new') {
                    // Show new patient form
                    newPatientInfo.style.display = 'block';
                    patientHistoryInfo.style.display = 'none';
                    
                    // Make fields required
                    nameInput.required = true;
                    ageInput.required = true;
                } else {
                    // Hide new patient form
                    newPatientInfo.style.display = 'none';
                    
                    // Remove required attribute
                    nameInput.required = false;
                    ageInput.required = false;
                    
                    // Show patient history
                    updatePatientHistory(patientSelection.value);
                }
            }
            
            // Function to update patient history display
            function updatePatientHistory(patientId) {
                console.log('Updating patient history for ID:', patientId);
                const selectedPatient = patientData.find(p => p.id == patientId);
                
                if (selectedPatient) {
                    console.log('Selected patient:', selectedPatient);
                    
                    // Try multiple key formats to find a match
                    const nameAgeGenderKey = selectedPatient.name + '-' + selectedPatient.age + '-' + selectedPatient.gender;
                    const patientKey = selectedPatient.patient_key;
                    
                    console.log('Trying keys:', { nameAgeGenderKey, patientKey });
                    
                    // Try patient_key first, then name-age-gender
                    let key = null;
                    let visitData = null;
                    
                    if (patientKey && patientVisits[patientKey]) {
                        key = patientKey;
                        visitData = patientVisits[patientKey];
                        console.log('Found visit data using patient_key');
                    } else if (patientVisits[nameAgeGenderKey]) {
                        key = nameAgeGenderKey;
                        visitData = patientVisits[nameAgeGenderKey];
                        console.log('Found visit data using name-age-gender key');
                    } else {
                        key = nameAgeGenderKey;
                        visitData = { count: 1 };
                        console.log('No visit data found, using default');
                    }
                    
                    const visitCount = visitData.count || 1;
                    
                    // Show patient history section
                    patientHistoryInfo.style.display = 'block';
                    
                    // Update visit count badge
                    visitCountBadge.textContent = 'Visit #' + visitCount;
                    console.log('Setting visit count to:', visitCount);
                    
                    // Update history text
                    if (visitCount > 1) {
                        patientHistoryText.innerHTML = `<strong>${selectedPatient.name}</strong> has been seen ${visitCount} time(s) before. This will be visit #${visitCount+1}. Previous medical history will be considered in the analysis.`;
                    } else {
                        patientHistoryText.innerHTML = `This is the second visit for <strong>${selectedPatient.name}</strong>.`;
                    }
                    
                    console.log('Patient history updated successfully');
                } else {
                    patientHistoryInfo.style.display = 'none';
                }
            }
            
            // Initial toggle
            togglePatientInfo();
            
            // Add event listener
            patientSelection.addEventListener('change', togglePatientInfo);
        });
    </script>
    
    <!-- Initialize Choices.js for symptoms dropdown -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded - Initializing Choices.js');
            const element = document.getElementById('current_symptoms');
            
            if (!element) {
                console.error('Could not find element with ID "current_symptoms"');
                return;
            }
            
            console.log('Found current_symptoms element:', element);
            
            try {
                if (typeof Choices === 'undefined') {
                    console.error('Choices.js is not loaded');
                    return;
                }
                
                console.log('Choices.js is loaded, initializing...');
                
                const choices = new Choices(element, {
                    removeItemButton: true,
                    placeholderValue: 'Select symptoms...',
                    searchPlaceholderValue: 'Search...',
                    classNames: {
                        containerInner: 'form-control',
                    }
                });
                
                console.log('Choices.js initialized successfully');
            } catch (error) {
                console.error('Error initializing Choices.js:', error);
            }
        });
    </script>
    @endsection