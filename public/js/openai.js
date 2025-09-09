// Main DOMContentLoaded event - consolidate all initialization here
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing OpenAI form functionality');
    
    // Initialize all components
    initializeFormSubmission();
    initializeProgressIndicator();
    initializePatientSelection();
    initializeSymptomsDropdown();
    initializeFileUpload();
    initializeFollowUpChat();
    initializeHeadToToeAssessment();
    
    console.log('All OpenAI form components initialized');
});

// Form submission and loading functionality
function initializeFormSubmission() {
    const form = document.getElementById('openaiForm');
    if (!form) return;

    form.addEventListener('submit', function() {
        // Always show the inline progress bar overlay
        const pageLoader = document.getElementById('page-loader');
        if (pageLoader) {
            pageLoader.style.display = 'flex';
        }

        // Start fake progress animation
        const bar = document.getElementById('progressBar');
        if (bar) {
            let width = 0;
            const step = () => {
                // Ease to 90% while waiting for server; final completion handled after response
                if (width < 90) {
                    width += Math.random() * 4 + 1; // 1-5%
                    if (width > 90) width = 90;
                    bar.style.width = width + '%';
                    requestAnimationFrame(step);
                }
            };
            requestAnimationFrame(step);
        }
    });
}

// Form progress indicator functionality
function initializeProgressIndicator() {
    const progressSteps = document.querySelectorAll('.progress-step');
    const progressBar = document.querySelector('.progress-bar');

    if (!progressSteps.length || !progressBar) return;

    // Find sections by heading text
    function findSectionByHeadingText(text) {
        const headings = document.querySelectorAll('.medical-form-section h6');
        for (let heading of headings) {
            if (heading.textContent.includes(text)) {
                return heading.closest('.medical-form-section');
            }
        }
        return null;
    }

    const sections = {
        'patient': findSectionByHeadingText('Patient'),
        'vitals': findSectionByHeadingText('Vitals'),
        'symptoms': findSectionByHeadingText('Symptoms'),
        'diagnosis': findSectionByHeadingText('Diagnosis')
    };

    // Function to update progress
    function updateProgress(step) {
        let progress = 0;
        let activeFound = false;

        progressSteps.forEach((stepEl, index) => {
            const stepName = stepEl.getAttribute('data-step');
            const stepIcon = stepEl.querySelector('.step-icon');

            if (stepName === step) {
                stepEl.classList.add('active');
                // Apply active styles directly
                if (stepIcon) {
                    stepIcon.style.backgroundColor = '#DE6262';
                    stepIcon.style.color = 'white';
                    stepIcon.style.borderColor = '#DE6262';
                    stepIcon.style.boxShadow = '0 0 0 5px rgba(222, 98, 98, 0.2)';
                }
                activeFound = true;
                progress = (index + 1) * 20; // 20% per step
            } else if (!activeFound) {
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
                // Apply completed styles directly
                if (stepIcon) {
                    stepIcon.style.backgroundColor = '#DE6262';
                    stepIcon.style.color = 'white';
                    stepIcon.style.borderColor = '#DE6262';
                    stepIcon.style.boxShadow = 'none';
                }
            } else {
                stepEl.classList.remove('active', 'completed');
                // Apply inactive styles directly
                if (stepIcon) {
                    stepIcon.style.backgroundColor = '#f8f9fa';
                    stepIcon.style.color = '#6c757d';
                    stepIcon.style.borderColor = '#e9ecef';
                    stepIcon.style.boxShadow = 'none';
                }
            }
        });

        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
    }

    // Add click event to step icons for navigation
    progressSteps.forEach(step => {
        step.addEventListener('click', function() {
            const stepName = this.getAttribute('data-step');
            updateProgress(stepName);

            // Scroll to the corresponding section
            if (sections[stepName]) {
                sections[stepName].scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Initialize with first step active
    updateProgress('patient');

    // Add scroll spy functionality
    window.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY + 200; // Offset for better detection

        // Determine which section is currently in view
        let currentSection = 'patient';

        Object.entries(sections).forEach(([name, section]) => {
            if (section && section.offsetTop <= scrollPosition) {
                currentSection = name;
            }
        });

        updateProgress(currentSection);
    });

    // Quick test buttons functionality
    const quickTestButtons = document.querySelectorAll('.quick-test');
    const testResultsTextarea = document.querySelector('textarea[name="test_results"]');

    if (quickTestButtons.length > 0 && testResultsTextarea) {
        quickTestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const testType = this.getAttribute('data-test');
                let template = '';

                // Add different templates based on test type
                switch(testType) {
                    case 'CBC':
                        template = 'CBC: WBC 7,500/μL, RBC 4.8 M/μL, Hgb 14.2 g/dL, Hct 42%, Plt 250,000/μL';
                        break;
                    case 'CRP':
                        template = 'CRP: 0.8 mg/L (Normal range: 0-1.0 mg/L)';
                        break;
                    case 'Urinalysis':
                        template = 'Urinalysis: Color - Yellow, Clarity - Clear, pH 6.0, Specific gravity 1.018, Negative for protein, glucose, ketones, blood, and nitrites';
                        break;
                    case 'X-ray':
                        template = 'Chest X-ray: No acute cardiopulmonary process. Heart size normal. Lungs clear.';
                        break;
                    case 'CT Scan':
                        template = 'CT Scan: No evidence of acute intracranial abnormality. No mass effect or midline shift.';
                        break;
                    default:
                        template = testType + ': ';
                }

                // Add the template to the textarea
                const currentText = testResultsTextarea.value;
                if (currentText && !currentText.endsWith('\n')) {
                    testResultsTextarea.value += '\n';
                }

                testResultsTextarea.value += (currentText ? '' : '') + template;
                testResultsTextarea.focus();
            });
        });
    }
}

// Patient selection functionality
function initializePatientSelection() {
    const existingPatientSelect = document.getElementById('existing_patient');
    const newPatientForm = document.getElementById('new_patient_form');
    const patientNameInput = document.getElementById('patient_name');
    const patientEmailInput = document.getElementById('patient_email');
    const patientPhoneInput = document.getElementById('patient_phone');
    const patientAgeInput = document.getElementById('patient_age');
    const patientGenderSelect = document.getElementById('patient_gender');

    if (!existingPatientSelect || !newPatientForm) {
        console.log('Patient selection elements not found');
        return;
    }

    console.log('Initializing patient selection functionality');

    // Function to toggle patient form visibility
    function togglePatientForm() {
        console.log('Toggling patient form, selected value:', existingPatientSelect.value);
        
        if (existingPatientSelect.value === '') {
            // Show new patient form
            newPatientForm.style.display = 'block';
            console.log('Showing new patient form');

            // Make new patient fields required
            if (patientNameInput) patientNameInput.required = true;
            if (patientEmailInput) patientEmailInput.required = true;
            if (patientGenderSelect) patientGenderSelect.required = true;

            // Clear any pre-filled data
            if (patientNameInput) patientNameInput.value = '';
            if (patientEmailInput) patientEmailInput.value = '';
            if (patientPhoneInput) patientPhoneInput.value = '';
            if (patientAgeInput) patientAgeInput.value = '';
            if (patientGenderSelect) patientGenderSelect.value = '';
        } else {
            // Hide new patient form and populate with selected patient data
            newPatientForm.style.display = 'none';
            console.log('Hiding new patient form');

            // Remove required attributes
            if (patientNameInput) patientNameInput.required = false;
            if (patientEmailInput) patientEmailInput.required = false;
            if (patientGenderSelect) patientGenderSelect.required = false;

            // Get selected patient data
            const selectedOption = existingPatientSelect.options[existingPatientSelect.selectedIndex];
            if (selectedOption) {
                // Populate form with selected patient data (for display purposes)
                if (patientNameInput) patientNameInput.value = selectedOption.dataset.name || '';
                if (patientEmailInput) patientEmailInput.value = selectedOption.dataset.email || '';
                if (patientPhoneInput) patientPhoneInput.value = selectedOption.dataset.phone || '';
                if (patientAgeInput) patientAgeInput.value = selectedOption.dataset.age || '';
                if (patientGenderSelect) patientGenderSelect.value = selectedOption.dataset.gender || '';
            }
        }
    }

    // Initial toggle on page load
    togglePatientForm();

    // Add event listener for patient selection changes
    existingPatientSelect.addEventListener('change', togglePatientForm);

    // Form validation before submission
    const form = document.getElementById('openaiForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // If no existing patient selected, validate new patient form
            if (existingPatientSelect.value === '') {
                if (patientNameInput && !patientNameInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter patient name');
                    patientNameInput.focus();
                    return false;
                }
                if (patientEmailInput && !patientEmailInput.value.trim()) {
                    e.preventDefault();
                    alert('Please enter patient email');
                    patientEmailInput.focus();
                    return false;
                }
                if (patientGenderSelect && !patientGenderSelect.value) {
                    e.preventDefault();
                    alert('Please select patient gender');
                    patientGenderSelect.focus();
                    return false;
                }
            }
        });
    }
}

// Symptoms dropdown initialization
function initializeSymptomsDropdown() {
    console.log('Initializing symptoms dropdown');
    const element = document.getElementById('current_symptoms');

    if (!element) {
        console.log('Current symptoms element not found');
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
            searchPlaceholderValue: 'Search symptoms...',
            noResultsText: 'No symptoms found',
            itemSelectText: 'Press to select'
        });

        console.log('Choices.js initialized successfully');

        // Custom Symptoms Handling
        const customSymptomInput = document.getElementById('custom_symptom_input');
        const addCustomSymptomBtn = document.getElementById('add_custom_symptom');
        const customSymptomsContainer = document.getElementById('custom_symptoms_container');
        const customSymptomsData = document.getElementById('custom_symptoms_data');

        if (addCustomSymptomBtn && customSymptomInput) {
            addCustomSymptomBtn.addEventListener('click', function() {
                const symptomText = customSymptomInput.value.trim();
                if (symptomText) {
                    // Add to Choices.js dropdown
                    choices.setChoices([{
                        value: symptomText,
                        label: symptomText,
                        selected: true
                    }], 'value', 'label', false);

                    // Add to custom symptoms container for display
                    const symptomTag = document.createElement('span');
                    symptomTag.className = 'badge bg-secondary me-2 mb-2';
                    symptomTag.innerHTML = `${symptomText} <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7em;"></button>`;
                    
                    symptomTag.querySelector('.btn-close').addEventListener('click', function() {
                        // Remove from Choices.js
                        choices.removeActiveItemsByValue(symptomText);
                        // Remove from display
                        symptomTag.remove();
                        updateCustomSymptomsData();
                    });

                    customSymptomsContainer.appendChild(symptomTag);
                    customSymptomInput.value = '';
                    updateCustomSymptomsData();
                }
            });

            // Allow Enter key to add symptom
            customSymptomInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addCustomSymptomBtn.click();
                }
            });
        }

        function updateCustomSymptomsData() {
            const customSymptoms = Array.from(customSymptomsContainer.querySelectorAll('.badge')).map(badge => 
                badge.textContent.trim().replace('×', '').trim()
            );
            if (customSymptomsData) {
                customSymptomsData.value = JSON.stringify(customSymptoms);
            }
        }

    } catch (error) {
        console.error('Error initializing Choices.js:', error);
    }
}

// File upload functionality
function initializeFileUpload() {
    console.log('Initializing file upload functionality');
    
    const fileInput = document.getElementById('reports');
    const addMoreFilesBtn = document.getElementById('add-more-files-btn');
    const selectedFilesContainer = document.getElementById('selected-files');
    const fileStorageContainer = document.getElementById('file-storage-container');

    if (!fileInput || !selectedFilesContainer) {
        console.log('File upload elements not found');
        return;
    }

    let fileCounter = 0;
    let selectedFiles = [];

    function updateFileDisplay() {
        if (selectedFiles.length === 0) {
            selectedFilesContainer.innerHTML = `
                <div class="text-center text-muted py-2">
                    <i class="fas fa-file-upload me-2"></i>No files selected yet
                </div>
            `;
        } else {
            selectedFilesContainer.innerHTML = selectedFiles.map((file, index) => `
                <div class="selected-file-item d-flex align-items-center justify-content-between p-2 mb-2 border rounded">
                    <div class="file-info">
                        <i class="fas fa-file me-2"></i>
                        <span class="file-name">${file.name}</span>
                        <small class="text-muted ms-2">(${(file.size / 1024).toFixed(1)} KB)</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');

            // Add event listeners to remove buttons
            selectedFilesContainer.querySelectorAll('.remove-file').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    selectedFiles.splice(index, 1);
                    updateFileInput();
                    updateFileDisplay();
                });
            });
        }
    }

    function updateFileInput() {
        // Create a new DataTransfer object to update the file input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    if (addMoreFilesBtn) {
        addMoreFilesBtn.addEventListener('click', function() {
            fileInput.click();
        });
    }

    fileInput.addEventListener('change', function() {
        const newFiles = Array.from(this.files);
        selectedFiles = [...selectedFiles, ...newFiles];
        updateFileDisplay();
    });

    // Initial display
    updateFileDisplay();
}

// Follow-up chat functionality
function initializeFollowUpChat() {
    console.log('Initializing follow-up chat functionality');
    setupFollowUpChat();
}

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
            fetch('/openai/follow-up', {
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
            .then(response => {
                // Check if response is ok before parsing JSON
                if (!response.ok) {
                    // If it's an API key error (401 Unauthorized)
                    if (response.status === 401) {
                        throw new Error('API_KEY_ERROR');
                    }
                    throw new Error('SERVER_ERROR');
                }
                return response.json();
            })
            .then(data => {
                // Remove typing indicator
                if (typingIndicator) {
                    removeTypingIndicator(typingIndicator);
                }

                if (data.success) {
                    // Add AI response with typing animation
                    addChatMessage(data.message, 'ai');

                    // Update conversation ID if needed
                    if (data.conversation_id) {
                        document.getElementById('conversation-id').value = data.conversation_id;
                    }
                } else if (data.api_key_error) {
                    // Show API key error with special styling
                    addErrorMessage(data.message || 'OpenAI API key is invalid or expired. Please contact the administrator.', true);

                    // Also show a modal with more information
                    showApiKeyErrorModal();
                } else {
                    // Show regular error
                    addErrorMessage(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                // Remove typing indicator
                removeTypingIndicator(typingIndicator);

                if (error.message === 'API_KEY_ERROR') {
                    // Show API key error with special styling
                    addErrorMessage('OpenAI API key is invalid or expired. Please contact the administrator.', true);

                    // Also show a modal with more information
                    showApiKeyErrorModal();
                } else {
                    // Show regular error
                    addErrorMessage('Failed to connect to the server. Please try again later.');
                }
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
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
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
    } else if (sender === 'user') {
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
    } else if (sender === 'error') {
        messageDiv.className = 'chat-message error-message';
        messageDiv.innerHTML = `
            <div class="error-content">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div class="error-text">${content}</div>
            </div>
        `;
        
        // Add to chat
        document.getElementById('chat-messages').appendChild(messageDiv);
    }

    // Scroll to bottom
    document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
}

function addTypingIndicator() {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return null;

    const typingDiv = document.createElement('div');
    typingDiv.className = 'chat-message typing-indicator';
    typingDiv.innerHTML = `
        <div class="d-flex mb-3">
            <div class="flex-shrink-0">
                <div class="avatar bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-robot"></i>
                </div>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="message-content p-3 rounded bg-light">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    `;

    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    return typingDiv;
}

// Format AI response text with proper HTML formatting
function formatAIResponse(text) {
    if (!text) return '';

    // Clean up text: remove excessive whitespace and normalize line breaks
    let cleanedText = text
        .replace(/\r\n/g, '\n')  // Normalize line endings
        .replace(/\n{3,}/g, '\n\n')  // Replace 3+ line breaks with 2
        .replace(/[ \t]{2,}/g, ' ')  // Replace multiple spaces/tabs with single space
        .replace(/^\s+|\s+$/gm, '')  // Trim whitespace from start/end of each line
        .trim();

    // Remove the Sources section from the text before formatting
    const sourcesMatch = cleanedText.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
    if (sourcesMatch) {
        cleanedText = cleanedText.replace(sourcesMatch[0], '').trim();
    }

    // Enhanced formatting for any medical response structure
    let formattedHTML = formatMedicalResponse(cleanedText);

    return formattedHTML;
}

function formatMedicalResponse(text) {
    if (!text) return '';

    // Professional medical formatting for structured response
    let enhancedText = text
        // Handle the initial CASE URGENCY format at the top
        .replace(/^CASE\s+URGENCY:\s*(EMERGENCY|URGENT|ROUTINE)/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">$1</span></div>')

        // Patient Case Summary Section
        .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

        // Case Urgency Section
        .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medcura-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

        // A) Differential Diagnosis Section - Handle with or without dashes
        .replace(/^(-{0,3}A\)?\s*(DIFFERENTIAL\s+)?DIAGNOSIS.*?:?|🔬\s*.*?DIAGNOSIS.*?:?)$/gmi, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-microscope"></i> A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

        // B) Investigations Section - Handle with or without dashes
        .replace(/^(-{0,3}B\)?\s*.*?(RECOMMENDED\s+)?(INVESTIGATIONS?|TESTS?|DIAGNOSTIC|WORKUP).*?:?)$/gmi, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header"><i class="fas fa-vials"></i> B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

        // C) Treatment/Management Section - Handle with or without dashes
        .replace(/^(-{0,3}C\)?\s*.*?(TREATMENT|MANAGEMENT|PLAN|THERAPY|INTERVENTION).*?:?)$/gmi, '</div></div><div class="medcura-section management-plan"><h4 class="section-header"><i class="fas fa-pills"></i> C) MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

        // D) Warning Signs Section - Handle with or without dashes
        .replace(/^(-{0,3}D\)?\s*WARNING\s+SIGNS.*?:?|⚠️\s*WARNING\s+SIGNS.*?:?)$/gmi, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"><i class="fas fa-exclamation-triangle"></i> D) WARNING SIGNS TO MONITOR</h4><div class="section-content">')

        // Handle Summary Format Headers
        .replace(/^OVERALL\s+HEALTH\s+TRAJECTORY:?$/gmi, '<div class="medcura-section patient-summary"><h4 class="section-header"><i class="fas fa-chart-line"></i> OVERALL HEALTH TRAJECTORY</h4><div class="section-content">')

        .replace(/^KEY\s+MEDICAL\s+ISSUES\s+IDENTIFIED:?$/gmi, '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header"><i class="fas fa-stethoscope"></i> KEY MEDICAL ISSUES IDENTIFIED</h4><div class="section-content">')

        .replace(/^IMPORTANT\s+TRENDS\s+IN\s+SYMPTOMS\s+OR\s+TEST\s+RESULTS:?$/gmi, '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header"><i class="fas fa-chart-area"></i> IMPORTANT TRENDS IN SYMPTOMS OR TEST RESULTS</h4><div class="section-content">')

        .replace(/^TREATMENT\s+EFFECTIVENESS\s+BASED\s+ON\s+VISIT\s+PROGRESSION:?$/gmi, '</div></div><div class="medcura-section management-plan"><h4 class="section-header"><i class="fas fa-clipboard-check"></i> TREATMENT EFFECTIVENESS BASED ON VISIT PROGRESSION</h4><div class="section-content">')

        .replace(/^RECOMMENDATIONS\s+FOR\s+FUTURE\s+CARE:?$/gmi, '</div></div><div class="medcura-section warning-signs"><h4 class="section-header"><i class="fas fa-user-md"></i> RECOMMENDATIONS FOR FUTURE CARE</h4><div class="section-content">')

        // Handle Sub-sections within the main sections
        .replace(/^(Status:|Reason:|Symptoms:|Vital Signs:|Laboratory Findings:|Immediate Diagnostic Steps:|Critical Interventions:|Long-term Care Considerations:|Lifestyle and Risk Factor Modification:)/gmi, '<div class="subsection-header">$1</div>')

        // General fallback for any remaining letter-based headers
        .replace(/^([A-D]\)\s*[A-Z\s]{5,}:?)$/gmi, function(match, p1) {
            let sectionClass = 'medcura-section';
            let headerText = match.replace(/^[A-D]\)\s*/, '').replace(/:$/, '');
            let letterPrefix = match.charAt(0);
            let icon = '';

            switch(letterPrefix) {
                case 'A': icon = '<i class="fas fa-microscope"></i>'; sectionClass += ' differential-diagnoses'; break;
                case 'B': icon = '<i class="fas fa-vials"></i>'; sectionClass += ' recommended-tests'; break;
                case 'C': icon = '<i class="fas fa-pills"></i>'; sectionClass += ' management-plan'; break;
                case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; sectionClass += ' warning-signs'; break;
            }

            return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letterPrefix}) ${headerText}</h4><div class="section-content">`;
        })

        // Doctor's Note Section
        .replace(/^🧠\s*DOCTOR'S\s+NOTE:?$/gm, '</div></div><div class="medcura-section doctor-note-section"><h4 class="section-header">🧠 DOCTOR\'S NOTE</h4><div class="section-content">');

    // Split the text into lines for processing
    let lines = enhancedText.split('\n');
    let formatted = '';
    let inList = false;
    let listType = '';
    let inTable = false;
    let tableRows = [];
    let sectionOpened = false;

    // Process each line
    for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();

        // Skip empty lines
        if (!line) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (inTable) {
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }
            formatted += '<br>';
            continue;
        }

        // Skip processing if line is already HTML (from our replacement above)
        if (line.startsWith('<div') || line.startsWith('</div>') || line.startsWith('<h') || line.startsWith('<hr')) {
            if (inList) {
                formatted += listType === 'ul' ? '</ul>' : '</ol>';
                inList = false;
            }
            if (inTable) {
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }
            formatted += line;
            if (line.includes('section-content')) {
                sectionOpened = true;
            }
            continue;
        }

        // Handle table data (pipe-separated)
        if (line.includes('|') && line.split('|').length >= 3) {
            if (!inTable) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                inTable = true;
            }
            tableRows.push(line);
            continue;
        } else if (inTable) {
            formatted += formatTable(tableRows);
            inTable = false;
            tableRows = [];
        }

        // Handle numbered lists
        if (/^\d+[\.\)]\s+/.test(line)) {
            if (!inList || listType !== 'ol') {
                if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                formatted += '<ol class="medical-list">';
                inList = true;
                listType = 'ol';
            }
            formatted += '<li class="bullet-item">' + line.replace(/^\d+[\.\)]\s+/, '') + '</li>';
            continue;
        }

        // Handle bullet points
        if (/^[•\-\*]\s+/.test(line) || /^\s*[\-\*]\s+/.test(line)) {
            if (!inList || listType !== 'ul') {
                if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                formatted += '<ul class="medical-list">';
                inList = true;
                listType = 'ul';
            }
            formatted += '<li class="bullet-item">' + line.replace(/^[•\-\*\s]+/, '') + '</li>';
            continue;
        } else if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
            inList = false;
        }

        // Handle urgency levels with special styling
        if (line.match(/^\s*(EMERGENCY|URGENT|ROUTINE)\s*$/i)) {
            const urgency = line.toLowerCase();
            formatted += `<div class="urgency-badge ${urgency}">${line.toUpperCase()}</div>`;
            continue;
        }

        // Regular paragraph
        if (!sectionOpened) {
            // If no section is opened yet, start with a default section
            formatted += '<div class="medcura-section"><div class="section-content">';
            sectionOpened = true;
        }
        formatted += '<p>' + line + '</p>';
    }

    // Close any remaining lists or tables
    if (inList) {
        formatted += listType === 'ul' ? '</ul>' : '</ol>';
    }
    if (inTable) {
        formatted += formatTable(tableRows);
    }

    // Close any open sections
    if (sectionOpened) {
        formatted += '</div></div>';
    }

    return formatted;
}

function formatTable(rows) {
    if (!rows || rows.length === 0) return '';

    let tableHtml = '<div class="medcura-table"><table class="table table-striped table-hover">';

    for (let i = 0; i < rows.length; i++) {
        let cells = rows[i].split('|').map(cell => cell.trim()).filter(cell => cell);

        if (cells.length < 2) continue;

        tableHtml += '<tr>';

        if (i === 0) {
            // Header row
            for (let cell of cells) {
                tableHtml += `<th class="table-header-cell">${cell}</th>`;
            }
        } else {
            // Data rows
            for (let cell of cells) {
                tableHtml += `<td>${cell}</td>`;
            }
        }

        tableHtml += '</tr>';
    }

    tableHtml += '</table></div>';
    return tableHtml;
}

// Format sources to just show the logos of the sites
function formatSources(sourcesText) {
    if (!sourcesText || sourcesText.trim() === '') {
        return '';
    }

    // Create a simple logo grid
    let html = '<div class="d-flex flex-wrap justify-content-center mt-3">';

    // Add PubMed logo
    if (sourcesText.match(/pubmed|ncbi|nlm|nih\.gov/i)) {
        html += `
            <div class="m-2">
                <img src="https://cdn.ncbi.nlm.nih.gov/pubmed/images/pubmed-logo.png"
                     alt="PubMed"
                     title="PubMed"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add NEJM logo
    if (sourcesText.match(/nejm|new england journal/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.nejm.org/pb-assets/images/global/social-share/NEJM-Logo-Social-Share.jpg"
                     alt="NEJM"
                     title="New England Journal of Medicine"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add JAMA logo
    if (sourcesText.match(/jama|american medical association/i)) {
        html += `
            <div class="m-2">
                <img src="https://jamanetwork.com/images/logos/jama-logo.svg"
                     alt="JAMA"
                     title="Journal of the American Medical Association"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add The Lancet logo
    if (sourcesText.match(/lancet/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.thelancet.com/cms/asset/f4e2c7e5-9c1e-4d7c-b0c3-a4b8519eb0c3/lancet-logo.jpg"
                     alt="The Lancet"
                     title="The Lancet"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add BMJ logo
    if (sourcesText.match(/bmj|british medical journal/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.bmj.com/sites/default/files/attachments/bmj-logo.jpg"
                     alt="BMJ"
                     title="British Medical Journal"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add CDC logo
    if (sourcesText.match(/cdc|centers for disease control/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.cdc.gov/homepage/images/cdc-logo.png"
                     alt="CDC"
                     title="Centers for Disease Control and Prevention"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add WHO logo
    if (sourcesText.match(/who|world health/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.who.int/images/default-source/default-album/who-emblem.jpg"
                     alt="WHO"
                     title="World Health Organization"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add Mayo Clinic logo
    if (sourcesText.match(/mayo|clinic/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.mayoclinic.org/-/media/web/gbs/shared/images/socialmedia/mayo-clinic-logo-socialmedia.jpg"
                     alt="Mayo Clinic"
                     title="Mayo Clinic"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Add UpToDate logo
    if (sourcesText.match(/uptodate|wolters kluwer/i)) {
        html += `
            <div class="m-2">
                <img src="https://www.uptodate.com/sites/default/files/styles/large/public/2022-10/UpToDate_Logo_RGB.png"
                     alt="UpToDate"
                     title="UpToDate"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
            </div>
        `;
    }

    // Always add a generic medical source logo
    html += `
        <div class="m-2">
            <img src="https://cdn-icons-png.flaticon.com/512/3022/3022339.png"
                 alt="Medical Source"
                 title="Medical Source"
                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
        </div>
    `;

    html += '</div>';

    return html;
}

// Head-to-Toe Assessment functionality
function initializeHeadToToeAssessment() {
    console.log('Initializing Head-to-Toe Assessment functionality');
    
    // Get all normal checkboxes
    const normalCheckboxes = document.querySelectorAll('.section-normal-checkbox');

    // Add event listener to each checkbox
    normalCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sectionContentId = this.getAttribute('data-section');
            const sectionContent = document.getElementById(sectionContentId);

            if (this.checked) {
                // If checked, hide the section content using vanilla JavaScript
                sectionContent.style.display = 'none';

                // Clear all inputs in this section
                const inputs = sectionContent.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });

                // Add a hidden input to indicate this section is normal
                const sectionId = this.id.replace('-normal', '');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = sectionId + '_status';
                hiddenInput.value = 'normal';
                hiddenInput.id = sectionId + '_status';
                sectionContent.parentNode.appendChild(hiddenInput);
            } else {
                // If unchecked, show the section content using vanilla JavaScript
                sectionContent.style.display = 'block';

                // Remove the hidden input if it exists
                const sectionId = this.id.replace('-normal', '');
                const hiddenInput = document.getElementById(sectionId + '_status');
                if (hiddenInput) {
                    hiddenInput.parentNode.removeChild(hiddenInput);
                }
            }
        });
    });
}

// Error handling functions
function addErrorMessage(message, isApiKeyError = false) {
    const errorDiv = document.createElement('div');
    errorDiv.className = isApiKeyError ? 'alert alert-danger' : 'alert alert-warning';

    if (isApiKeyError) {
        // Create icon element
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-triangle me-2';
        errorDiv.appendChild(icon);

        // Create strong element for the title
        const strong = document.createElement('strong');
        strong.textContent = 'API Key Error: ';
        errorDiv.appendChild(strong);

        // Add the message text
        const textNode = document.createTextNode(message);
        errorDiv.appendChild(textNode);
    } else {
        errorDiv.textContent = message;
    }

    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.appendChild(errorDiv);
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Only auto-remove regular errors, not API key errors
    if (!isApiKeyError) {
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

function showApiKeyErrorModal() {
    // Create modal if it doesn't exist
    if (!document.getElementById('apiKeyErrorModal')) {
        const modalHtml = `
            <div class="modal fade" id="apiKeyErrorModal" tabindex="-1" aria-labelledby="apiKeyErrorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="apiKeyErrorModalLabel" style="word-break: break-word; line-height: 1.3; font-size: 1.1rem;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                OpenAI API Key Error
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="word-break: break-word; line-height: 1.5; font-size: 0.95rem;">
                            <p><strong>The OpenAI API key is invalid or has expired.</strong></p>
                            <p>This could be due to:</p>
                            <ul>
                                <li>Invalid API key configuration</li>
                                <li>Expired API key</li>
                                <li>Insufficient API credits</li>
                                <li>API key permissions issues</li>
                            </ul>
                            <p class="mb-0"><strong>Please contact the system administrator to resolve this issue.</strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('apiKeyErrorModal'));
    modal.show();
}

function removeTypingIndicator(typingIndicator) {
    if (typingIndicator && typingIndicator.parentNode) {
        typingIndicator.remove();
    }
}