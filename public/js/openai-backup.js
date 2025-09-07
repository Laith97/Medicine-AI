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
    
    console.log('All OpenAI form components initialized');
});

// Form submission and loading functionality
function initializeFormSubmission() {
    const form = document.getElementById('openaiForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        // Show the Canvas theme's built-in loader using the data-loader-html
        const body = document.body;

        // Create loader overlay with the custom SVG from data-loader-html
        const loaderHTML = body.getAttribute('data-loader-html');
        if (loaderHTML) {
            const loaderOverlay = document.createElement('div');
            loaderOverlay.id = 'canvas-loader-overlay';
            loaderOverlay.innerHTML = loaderHTML;
            loaderOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(44, 62, 80, 0.9);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(5px);
            `;

            // Style the SVG container
            const svgContainer = loaderOverlay.querySelector('#css3-spinner-svg-pulse-wrapper');
            if (svgContainer) {
                svgContainer.style.cssText = `
                    text-align: center;
                    padding: 20px;
                `;
            }

            document.body.appendChild(loaderOverlay);
        } else {
            // Fallback to our custom loader
            const pageLoader = document.getElementById('page-loader');
            if (pageLoader) {
                pageLoader.style.display = 'block';
            }
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
                    removeTypingIndicator(typingIndicator);

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

        document.getElementById('chat-messages').appendChild(errorDiv);

        // Scroll to bottom
        document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;

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
                                <p style="margin-bottom: 1rem;">The OpenAI API key appears to be invalid or expired. This means:</p>
                                <ul style="padding-left: 1.2rem; margin-bottom: 1rem;">
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">You won't be able to get AI-powered responses</li>
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">Medical analysis features will be unavailable</li>
                                    <li style="margin-bottom: 0.5rem; word-break: break-word;">Chat functionality will not work</li>
                                </ul>
                                <p style="margin-bottom: 0;">Please contact the system administrator to update the API key.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);
        }

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('apiKeyErrorModal'));
        modal.show();
    }

    /**
     * Format table from array of table rows
     */
    function formatTable(tableRows) {
        if (!tableRows || tableRows.length === 0) return '';

        let table = '<table class="table table-striped mt-3">';
        let isFirstRow = true;
        let headerAdded = false;

        for (const row of tableRows) {
            let cells = [];

            // Handle different table formats
            if (row.includes('|')) {
                // Pipe-separated format
                cells = row.split('|').map(cell => cell.trim()).filter(cell => cell);
            } else if (row.match(/^(Rank|1|2|3|4|5)\s+/)) {
                // Diagnosis table format without pipes
                const match = row.match(/^(\d+|Rank)\s+(.*?)\s+(\d+%)\s+(.*?)$/);
                if (match) {
                    cells = [match[1], match[2], match[3], match[4]];
                } else {
                    // Try to parse the concatenated format
                    const diagnosisMatch = row.match(/^(\d+)(.*?)(\d+%)(.*?)$/);
                    if (diagnosisMatch) {
                        cells = [diagnosisMatch[1], diagnosisMatch[2], diagnosisMatch[3], diagnosisMatch[4]];
                    }
                }
            } else if (row.includes('RankDiagnosis')) {
                // Header row for the concatenated format
                cells = ['Rank', 'Diagnosis', 'Probability (%)', 'Clinical Reasoning'];
            }

            if (cells.length === 0) continue;

            // Check if this should be a header row
            if (!headerAdded && (cells.some(cell => cell.toLowerCase().includes('rank') || cell.toLowerCase().includes('diagnosis')) || isFirstRow)) {
                table += '<thead><tr>';
                cells.forEach(cell => {
                    table += `<th>${cell}</th>`;
                });
                table += '</tr></thead><tbody>';
                headerAdded = true;
                isFirstRow = false;
            } else {
                // Data row
                table += '<tr>';
                cells.forEach((cell, index) => {
                    // Check if this is a probability cell
                    if (cell.includes('%')) {
                        cell = `<span class="probability">${cell}</span>`;
                    }
                    table += `<td>${cell}</td>`;
                });
                table += '</tr>';
            }
        }

        table += '</tbody></table>';
        return table;
    }

    /**
     * Format AI response text with proper HTML formatting
     */
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

    function formatLevel1(text) {
        if (!text) return '';

        let formatted = '<div class="medcura-level1">';

        // Handle Level 1 header
        text = text.replace(/🟢\s*LEVEL\s+1:\s*QUICK\s+CLINICAL\s+SUMMARY/i,
            '<div class="level-header level1-header">🟢 QUICK CLINICAL SUMMARY</div>');

        // Patient Summary Section
        text = text.replace(/📋\s*PATIENT\s+SUMMARY:/i,
            '<div class="medcura-section patient-summary"><h4 class="section-header">📋 PATIENT SUMMARY</h4><div class="section-content">');

        // Case Urgency Section
        text = text.replace(/🚨\s*CASE\s+URGENCY:/i,
            '</div></div><div class="medcura-section case-urgency"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">');

        // Top 3 Differential Diagnoses Section
        text = text.replace(/🔍\s*TOP\s+3\s+DIFFERENTIAL\s+DIAGNOSES:/i,
            '</div></div><div class="medcura-section differential-diagnoses"><h4 class="section-header">🔍 TOP 3 DIFFERENTIAL DIAGNOSES</h4><div class="section-content">');

        // Recommended Tests Section
        text = text.replace(/🧪\s*RECOMMENDED\s+TESTS:/i,
            '</div></div><div class="medcura-section recommended-tests"><h4 class="section-header">🧪 RECOMMENDED TESTS</h4><div class="section-content">');

        // Initial Management Plan Section
        text = text.replace(/💊\s*INITIAL\s+MANAGEMENT\s+PLAN:/i,
            '</div></div><div class="medcura-section management-plan"><h4 class="section-header">💊 INITIAL MANAGEMENT PLAN</h4><div class="section-content">');

        // Warning Signs Section
        text = text.replace(/⚠️\s*WARNING\s+SIGNS:/i,
            '</div></div><div class="medcura-section warning-signs"><h4 class="section-header">⚠️ WARNING SIGNS</h4><div class="section-content">');

        // Process the text line by line
        let lines = text.split('\n');
        let processedText = '';
        let inTable = false;
        let tableRows = [];

        for (let line of lines) {
            // Skip if already HTML
            if (line.includes('<div') || line.includes('</div>') || line.includes('<h4')) {
                if (inTable) {
                    processedText += formatMedCuraTable(tableRows);
                    inTable = false;
                    tableRows = [];
                }
                processedText += line + '\n';
                continue;
            }

            // Handle table rows (for differential diagnoses)
            if (line.includes('|') && line.split('|').length >= 4) {
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                processedText += formatMedCuraTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Handle bullet points
            if (/^[\s]*[•\-\*]\s+(.+)$/.test(line)) {
                const itemText = line.replace(/^[\s]*[•\-\*]\s+(.+)$/, '$1');
                processedText += `<div class="bullet-item">• ${itemText}</div>\n`;
            }
            // Handle bold subsections
            else if (/^\*\*(.+?)\*\*/.test(line)) {
                const boldText = line.replace(/^\*\*(.+?)\*\*/, '<strong>$1</strong>');
                processedText += `<div class="subsection-header">${boldText}</div>\n`;
            }
            // Handle urgency levels
            else if (/^\*\*(EMERGENCY|URGENT|ROUTINE)\*\*/.test(line)) {
                const urgencyLevel = line.match(/^\*\*(EMERGENCY|URGENT|ROUTINE)\*\*/)[1];
                const urgencyClass = urgencyLevel.toLowerCase();
                processedText += `<div class="urgency-badge ${urgencyClass}">${urgencyLevel}</div>\n`;
            }
            // Regular text
            else if (line.trim()) {
                processedText += `<p>${line}</p>\n`;
            }
        }

        // Close any remaining table
        if (inTable) {
            processedText += formatMedCuraTable(tableRows);
        }

        formatted += processedText;
        formatted += '</div></div></div>'; // Close last section and level1

        return formatted;
    }

    function formatLevel2(text) {
        if (!text) return '';

        // Create collapsible section
        let formatted = `
            <div class="medcura-level2">
                <div class="level2-toggle" onclick="toggleLevel2()">
                    <div class="level-header level2-header">
                        🔵 DETAILED MEDICAL REPORT
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="toggle-hint">Click to Expand</div>
                </div>
                <div class="level2-content" id="level2-content" style="display: none;">
        `;

        // Remove the header from text
        text = text.replace(/🔵\s*DETAILED\s+MEDICAL\s+REPORT.*?\n/i, '');

        // Process sections
        text = text.replace(/\*\*([^*]+?)\*\*/g, '<div class="level2-section-header">$1</div>');

        // Handle bullet points
        text = text.replace(/^[\s]*[•\-\*]\s+(.+)$/gm, '<div class="bullet-item">• $1</div>');

        // Handle paragraphs
        let lines = text.split('\n');
        let processedText = '';

        for (let line of lines) {
            if (line.includes('<div class="level2-section-header">') ||
                line.includes('<div class="bullet-item">')) {
                processedText += line + '\n';
            } else if (line.trim()) {
                processedText += `<p>${line}</p>\n`;
            }
        }

        formatted += processedText;
        formatted += '</div></div>';

        return formatted;
    }

    function formatMedCuraTable(rows) {
        if (!rows || rows.length === 0) return '';

        let html = '<div class="medcura-table"><table class="table table-striped table-hover">';

        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].split('|').map(cell => cell.trim());
            const tag = i === 0 ? 'th' : 'td';
            const rowClass = i === 0 ? 'table-header' : '';

            html += `<tr class="${rowClass}">`;
            for (let cell of cells) {
                if (i === 0) {
                    html += `<${tag} class="table-header-cell">${cell}</${tag}>`;
                } else {
                    html += `<${tag}>${cell}</${tag}>`;
                }
            }
            html += '</tr>';
        }

        html += '</table></div>';
        return html;
    }

    function toggleLevel2() {
        const content = document.getElementById('level2-content');
        const icon = document.querySelector('.toggle-icon');

        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.textContent = '▲';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
        }
    }

    // Legacy function - keeping the rest of the old function for compatibility
    function formatAIResponseOld(text) {

        // Remove the Sources section from the text before formatting
        const sourcesMatch = cleanedText.match(/(📚\s*SOURCES:|Sources:)([\s\S]*?)(?:$|(?=\n\n\w))/i);
        if (sourcesMatch) {
            cleanedText = cleanedText.replace(sourcesMatch[0], '').trim();
        }

        // Professional medical formatting for structured response
        let enhancedText = cleanedText
            // Handle the initial CASE URGENCY format at the top
            .replace(/^CASE\s+URGENCY:\s*(EMERGENCY|URGENT|ROUTINE)/gm, '<div class="urgency-header">CASE URGENCY: <span class="urgency-level">$1</span></div>')

            // Fix the concatenated diagnosis table format
            .replace(/RankDiagnosisProbability \(%\)Clinical Reasoning-+/g, 'Rank|Diagnosis|Probability (%)|Clinical Reasoning')
            .replace(/(\d+)([A-Z][^0-9]+?)(\d+%)([^0-9]+?)(?=\d|$)/g, '$1|$2|$3|$4\n')

            // Handle section separators
            .replace(/^---$/gm, '<div class="section-break"></div>')

            // Patient Case Summary Section
            .replace(/^📋\s*PATIENT\s+CASE\s+SUMMARY:?$/gm, '<div class="medical-section patient-section"><h4 class="section-header">📋 PATIENT CASE SUMMARY</h4><div class="section-content">')

            // Case Urgency Section
            .replace(/^🚨\s*CASE\s+URGENCY:?$/gm, '</div></div><div class="medical-section urgency-section"><h4 class="section-header">🚨 CASE URGENCY</h4><div class="section-content">')

            // A) Differential Diagnosis Section - Handle with or without dashes
            .replace(/^(-{0,3}A\)?\s*(DIFFERENTIAL\s+)?DIAGNOSIS.*?:?|🔬\s*.*?DIAGNOSIS.*?:?)$/gmi, '</div></div><div class="medical-section diagnosis-section"><h4 class="section-header"><i class="fas fa-microscope"></i> A) DIFFERENTIAL DIAGNOSIS</h4><div class="section-content">')

            // B) Investigations Section - Handle with or without dashes
            .replace(/^(-{0,3}B\)?\s*.*?(RECOMMENDED\s+)?(INVESTIGATIONS?|TESTS?|DIAGNOSTIC|WORKUP).*?:?)$/gmi, '</div></div><div class="medical-section investigations-section"><h4 class="section-header"><i class="fas fa-vials"></i> B) RECOMMENDED INVESTIGATIONS</h4><div class="section-content">')

            // C) Treatment/Management Section - Handle with or without dashes
            .replace(/^(-{0,3}C\)?\s*.*?(TREATMENT|MANAGEMENT|PLAN|THERAPY|INTERVENTION).*?:?)$/gmi, '</div></div><div class="medical-section treatment-section"><h4 class="section-header"><i class="fas fa-pills"></i> C) MANAGEMENT RECOMMENDATIONS</h4><div class="section-content">')

            // D) Warning Signs Section - Handle with or without dashes
            .replace(/^(-{0,3}D\)?\s*WARNING\s+SIGNS.*?:?|⚠️\s*WARNING\s+SIGNS.*?:?)$/gmi, '</div></div><div class="medical-section warnings-section"><h4 class="section-header"><i class="fas fa-exclamation-triangle"></i> D) WARNING SIGNS TO MONITOR</h4><div class="section-content">')

            // Specific pattern for the exact format: "---B) RECOMMENDED INVESTIGATIONS:"
            .replace(/^---([ABCD])\)\s*(.+?):\s*$/gmi, function(match, letter, text) {
                let icon = '';
                let sectionClass = 'medical-section';

                switch(letter) {
                    case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                    case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                    case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                    case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                }

                return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letter}) ${text.toUpperCase()}</h4><div class="section-content">`;
            })

            // General fallback for any remaining letter-based headers
            .replace(/^([A-D]\)\s*[A-Z\s]{5,}:?)$/gmi, function(match, p1) {
                let sectionClass = 'medical-section';
                let headerText = match.replace(/^[A-D]\)\s*/, '').replace(/:$/, '');
                let letterPrefix = match.charAt(0);
                let icon = '';

                switch(letterPrefix) {
                    case 'A': icon = '<i class="fas fa-microscope"></i>'; break;
                    case 'B': icon = '<i class="fas fa-vials"></i>'; break;
                    case 'C': icon = '<i class="fas fa-pills"></i>'; break;
                    case 'D': icon = '<i class="fas fa-exclamation-triangle"></i>'; break;
                }

                return `</div></div><div class="${sectionClass}"><h4 class="section-header">${icon} ${letterPrefix}) ${headerText}</h4><div class="section-content">`;
            })

            // Doctor's Note Section
            .replace(/^🧠\s*DOCTOR'S\s+NOTE:?$/gm, '</div></div><div class="medical-section doctor-note-section"><h4 class="section-header">🧠 DOCTOR\'S NOTE</h4><div class="section-content">')

            // Sources Section (if present)
            .replace(/^📚\s*SOURCES:?$/gm, '</div></div><div class="medical-section sources-section"><h4 class="section-header">📚 SOURCES</h4><div class="section-content">');

        // Split the text into lines
        let lines = enhancedText.split('\n');
        let formatted = '';
        let inList = false;
        let listType = '';
        let inTable = false;
        let tableRows = [];

        // Process each line
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];

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
                continue;
            }

            // Check for concatenated diagnosis table
            if (line.includes('RankDiagnosis') && line.includes('Clinical Reasoning')) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                // Create proper table header
                tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
                continue;
            }
            // Check for the concatenated data row (like: 1Abdominal Aortic Aneurysm (AAA)70%Given the symptom...)
            else if (line.match(/^\d+[A-Z][^0-9]*\d+%/)) {
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                    tableRows.push('Rank|Diagnosis|Probability (%)|Clinical Reasoning');
                }
                // Parse the concatenated format
                const match = line.match(/^(\d+)([^0-9]*?)(\d+%)(.*)$/);
                if (match) {
                    const formattedRow = `${match[1]}|${match[2].trim()}|${match[3]}|${match[4].trim()}`;
                    tableRows.push(formattedRow);
                }
                continue;
            }
            // Check for table rows (contains | or table-like structure)
            else if ((line.includes('|') && line.split('|').length > 2) ||
                (line.match(/^(Rank|1|2|3|4|5)\s+(.*?)\s+(\d+%)\s+(.*?)$/))) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                if (!inTable) {
                    inTable = true;
                    tableRows = [];
                }
                tableRows.push(line);
                continue;
            } else if (inTable) {
                // End of table
                formatted += formatTable(tableRows);
                inTable = false;
                tableRows = [];
            }

            // Check for headers (# Header)
            if (/^#{1,6}\s+(.+)$/.test(line)) {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }
                const headerLevel = line.match(/^(#{1,6})\s+/)[1].length;
                const headerText = line.replace(/^#{1,6}\s+(.+)$/, '$1');
                formatted += `<h${headerLevel}>${headerText}</h${headerLevel}>`;
            }
            // Check for bullet points (* Item or - Item or • Item)
            else if (/^[\s]*[\*\-•]\s+(.+)$/.test(line)) {
                if (!inList || listType !== 'ul') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ul class="mb-3">';
                    inList = true;
                    listType = 'ul';
                }
                const itemText = line.replace(/^[\s]*[\*\-•]\s+(.+)$/, '$1');
                formatted += `<li>${itemText}</li>`;
            }
            // Check for numbered lists (1. Item)
            else if (/^[\s]*\d+\.\s+(.+)$/.test(line)) {
                if (!inList || listType !== 'ol') {
                    if (inList) formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    formatted += '<ol class="mb-3">';
                    inList = true;
                    listType = 'ol';
                }
                const itemText = line.replace(/^[\s]*\d+\.\s+(.+)$/, '$1');
                formatted += `<li>${itemText}</li>`;
            }
            // Regular text
            else {
                if (inList) {
                    formatted += listType === 'ul' ? '</ul>' : '</ol>';
                    inList = false;
                }

                // Skip empty lines
                if (line.trim() === '') {
                    formatted += '<br>';
                    continue;
                }

                // Check for section headers with multiple patterns
                const diagnosisPattern = /(DIAGNOS[IE]S|POSSIBLE\s+DIAGNOS[IE]S|DIFFERENTIAL\s+DIAGNOS[IE]S)/i;
                const recommendationsPattern = /(RECOMMENDATIONS|RECOMMENDATIONS\s+FOR\s+TESTS|SUGGESTED\s+TESTS)/i;
                const treatmentPattern = /(TREATMENT|TREATMENT\s+RECOMMENDATIONS|TREATMENT\s+PLAN|MANAGEMENT)/i;
                const warningsPattern = /(WARNINGS|PRECAUTIONS|RED\s+FLAGS|FOLLOW\-UP)/i;

                if (/^[A-Z][\)\.]?\s+.*?(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) ||
                    /^(DIAGNOS[IE]S|RECOMMENDATIONS|TREATMENT|WARNINGS|PRECAUTIONS|MANAGEMENT|FOLLOW).*?$/i.test(line) ||
                    /^[A-Z]\)\s+(POSSIBLE\s+DIAGNOS[IE]S|RECOMMENDATIONS\s+FOR\s+TESTS|TREATMENT\s+RECOMMENDATIONS|WARNINGS|PRECAUTIONS)$/i.test(line)) {

                    let className = '';

                    if (diagnosisPattern.test(line)) {
                        className = 'section-diagnosis';
                    } else if (recommendationsPattern.test(line)) {
                        className = 'section-recommendations';
                    } else if (treatmentPattern.test(line)) {
                        className = 'section-treatment';
                    } else if (warningsPattern.test(line)) {
                        className = 'section-warnings';
                    }

                    formatted += `<h4 class="mt-4 ${className}">${line}</h4>`;
                }
                // Check for subsection headers (often in ALL CAPS or with trailing colon)
                else if (/^[A-Z][A-Z\s\d\-\(\)]{5,}:?$/.test(line)) {
                    formatted += `<p><strong style="font-size: 1.15rem; color: #34495e;">${line}</strong></p>`;
                }
                else {
                    // All other text is formatted as regular paragraphs
                    formatted += `<p>${line}</p>`;
                }
            }
        }

        // Close any open lists or tables
        if (inList) {
            formatted += listType === 'ul' ? '</ul>' : '</ol>';
        }
        if (inTable) {
            formatted += formatTable(tableRows);
        }

        // Close any remaining open divs
        formatted += '</div></div>';

        // Process inline formatting

        // Bold text between ** or __
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // Italic text between * or _
        formatted = formatted.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/_([^_]+)_/g, '<em>$1</em>');

        // Highlight important information
        formatted = formatted.replace(/\!\!(.+?)\!\!/g, '<span style="background-color: #ffffcc; padding: 0 3px;">$1</span>');

        // Add some spacing between sections for better readability
        formatted = formatted.replace(/<\/h[1-6]>/g, '$&<div style="height: 10px;"></div>');

        // Close any remaining open divs
        formatted += '</div></div>';

        // Process inline formatting

        // Bold text between ** or __
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // Italic text between * or _
        formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');
        formatted = formatted.replace(/_(.+?)_/g, '<em>$1</em>');

        // Code blocks
        formatted = formatted.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        formatted = formatted.replace(/`(.+?)`/g, '<code>$1</code>');

        return formatted;
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

    // Print functionality for response modal
    document.addEventListener('DOMContentLoaded', function() {
        const printResponseBtn = document.getElementById('printResponseBtn');
        if (printResponseBtn) {
            printResponseBtn.addEventListener('click', function() {
                let responseContent = document.getElementById('openaiReply').innerHTML;
                // Sources are hidden as requested
                const sourcesContent = '';

                // The content is already formatted with proper HTML, no need for additional formatting

                // Create a new window for printing
                const printWindow = window.open('', '_blank');

                // Add content to the print window
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Medical Recommendations</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            .header { text-align: center; margin-bottom: 30px; }
                            .content { margin-bottom: 30px; line-height: 1.6; }
                            .sources { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
                            h4 { color: #2c3e50; margin-top: 25px; margin-bottom: 15px; }
                            ul, ol { margin-bottom: 20px; }
                            li { margin-bottom: 8px; }
                            @media print {
                                .no-print { display: none; }
                                a { text-decoration: none; color: #000; }
                                h4 { page-break-after: avoid; }
                                ul, ol { page-break-inside: avoid; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Medical Recommendations</h2>
                            <p>${new Date().toLocaleDateString()}</p>
                        </div>

                        <div class="content">
                            ${responseContent}
                        </div>

                        ${sourcesContent ? `
                        <div class="sources">
                            <h5>Sources</h5>
                            ${sourcesContent}
                        </div>
                        ` : ''}

                        <div class="text-center mt-4 no-print">
                            <button class="btn btn-primary" onclick="window.print()">Print</button>
                            <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
                        </div>
                    </body>
                    </html>
                `);

                // Focus the new window
                printWindow.document.close();
                printWindow.focus();
            });
        }
    });







        document.addEventListener('DOMContentLoaded', function() {
            // Handle patient selection similar to manual diagnosis
            const existingPatientSelect = document.getElementById('existing_patient');
            const newPatientForm = document.getElementById('new_patient_form');
            const patientNameInput = document.getElementById('patient_name');
            const patientEmailInput = document.getElementById('patient_email');
            const patientPhoneInput = document.getElementById('patient_phone');
            const patientAgeInput = document.getElementById('patient_age');
            const patientGenderSelect = document.getElementById('patient_gender');

            // Function to toggle patient form visibility
            function togglePatientForm() {
                if (existingPatientSelect.value === '') {
                    // Show new patient form
                    newPatientForm.style.display = 'block';

                    // Make new patient fields required
                    patientNameInput.required = true;
                    patientEmailInput.required = true;
                    patientAgeInput.required = true;
                    patientGenderSelect.required = true;

                    // Clear any pre-filled data
                    patientNameInput.value = '';
                    patientEmailInput.value = '';
                    patientPhoneInput.value = '';
                    patientAgeInput.value = '';
                    patientGenderSelect.value = '';
                } else {
                    // Hide new patient form and populate with selected patient data
                    newPatientForm.style.display = 'none';

                    // Remove required attributes
                    patientNameInput.required = false;
                    patientEmailInput.required = false;
                    patientAgeInput.required = false;
                    patientGenderSelect.required = false;

                    // Get selected patient data
                    const selectedOption = existingPatientSelect.options[existingPatientSelect.selectedIndex];
                    if (selectedOption) {
                        // Populate form with selected patient data (for display purposes)
                        patientNameInput.value = selectedOption.dataset.name || '';
                        patientEmailInput.value = selectedOption.dataset.email || '';
                        patientPhoneInput.value = selectedOption.dataset.phone || '';
                        patientAgeInput.value = selectedOption.dataset.age || '';
                        patientGenderSelect.value = selectedOption.dataset.gender || '';
                    }
                }
            }

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

                    // Remove required attribute and clear values
                    nameInput.required = false;
                    nameInput.value = '';
                    ageInput.required = false;
                    ageInput.value = '';
                    genderSelect.value = '';

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

            // Initial toggle on page load
            togglePatientForm();

            // Add event listener for patient selection changes
            existingPatientSelect.addEventListener('change', togglePatientForm);

            // Form validation before submission
            document.getElementById('openaiForm').addEventListener('submit', function(e) {
                // If no existing patient selected, validate new patient form
                if (existingPatientSelect.value === '') {
                    if (!patientNameInput.value.trim()) {
                        e.preventDefault();
                        alert('Please enter patient name');
                        patientNameInput.focus();
                        return false;
                    }
                    if (!patientEmailInput.value.trim()) {
                        e.preventDefault();
                        alert('Please enter patient email');
                        patientEmailInput.focus();
                        return false;
                    }
                    if (!patientAgeInput.value) {
                        e.preventDefault();
                        alert('Please enter patient age');
                        patientAgeInput.focus();
                        return false;
                    }
                    if (!patientGenderSelect.value) {
                        e.preventDefault();
                        alert('Please select patient gender');
                        patientGenderSelect.focus();
                        return false;
                    }
                }
            });
        });



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
                    searchPlaceholderValue: 'Search symptoms...',
                    classNames: {
                        containerInner: 'form-control',
                    }
                });

                console.log('Choices.js initialized successfully');

                // Custom Symptoms Handling
                const customSymptomInput = document.getElementById('custom_symptom_input');
                const addCustomSymptomBtn = document.getElementById('add_custom_symptom');
                const customSymptomsContainer = document.getElementById('custom_symptoms_container');
                const customSymptomsData = document.getElementById('custom_symptoms_data');

                // Array to store custom symptoms
                let customSymptoms = [];

                // Function to add a custom symptom
                function addCustomSymptom() {
                    const symptomText = customSymptomInput.value.trim();

                    if (symptomText.length < 3) {
                        alert('Symptom must be at least 3 characters long');
                        return;
                    }

                    if (customSymptoms.includes(symptomText)) {
                        alert('This symptom has already been added');
                        return;
                    }

                    // Add to array
                    customSymptoms.push(symptomText);

                    // Update hidden input
                    customSymptomsData.value = JSON.stringify(customSymptoms);

                    // Create visual representation
                    const symptomBadge = document.createElement('span');
                    symptomBadge.className = 'badge me-2 mb-2 p-2';
                    symptomBadge.style.backgroundColor = '#DE6262';
                    symptomBadge.style.color = 'white';
                    symptomBadge.innerHTML = `${symptomText} <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove" style="font-size: 0.5rem;"></button>`;

                    // Add remove functionality
                    const closeBtn = symptomBadge.querySelector('.btn-close');
                    closeBtn.addEventListener('click', function() {
                        // Remove from array
                        const index = customSymptoms.indexOf(symptomText);
                        if (index > -1) {
                            customSymptoms.splice(index, 1);
                        }

                        // Update hidden input
                        customSymptomsData.value = JSON.stringify(customSymptoms);

                        // Remove badge
                        symptomBadge.remove();
                    });

                    // Add to container
                    customSymptomsContainer.appendChild(symptomBadge);

                    // Clear input
                    customSymptomInput.value = '';
                    customSymptomInput.focus();

                    console.log('Added custom symptom:', symptomText);
                    console.log('Current custom symptoms:', customSymptoms);
                }

                // Add event listeners
                addCustomSymptomBtn.addEventListener('click', addCustomSymptom);

                customSymptomInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault(); // Prevent form submission
                        addCustomSymptom();
                    }
                });

            } catch (error) {
                console.error('Error initializing Choices.js:', error);
            }
        });



    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('reports');
        const selectedFilesContainer = document.getElementById('selected-files');
        const fileStorageContainer = document.getElementById('file-storage-container');
         const uploadStatus = document.getElementById('upload-status');
        const addMoreFilesBtn = document.getElementById('add-more-files-btn');
        const uploadZone = document.querySelector('.upload-zone');

        // Store all selected files
        let selectedFiles;
        let selectedFilesArray = []; // Fallback for browsers without DataTransfer support

        // Check if DataTransfer is supported
        const isDataTransferSupported = (function() {
            try {
                return !!new DataTransfer();
            } catch (e) {
                return false;
            }
        })();

        if (isDataTransferSupported) {
            selectedFiles = new DataTransfer();
            console.log('Using DataTransfer API for file handling');
        } else {
            console.log('DataTransfer API not supported, using fallback');
        }

        // Add drag and drop functionality to upload zone
        if (uploadZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, preventDefaults, false);
            });

            // Highlight drop zone when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, unhighlight, false);
            });

            // Handle dropped files
            uploadZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight() {
                uploadZone.classList.add('border-primary');
                uploadZone.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            }

            function unhighlight() {
                uploadZone.classList.remove('border-primary');
                uploadZone.style.backgroundColor = '';
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    if (isDataTransferSupported) {
                        Array.from(files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = Array.from(selectedFiles.files).some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFiles.items.add(file);
                            }
                        });

                        // Update the file input with all files
                        fileInput.files = selectedFiles.files;
                    } else {
                        // For browsers without DataTransfer support
                        Array.from(files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = selectedFilesArray.some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFilesArray.push(file);
                            }
                        });
                    }

                    updateFileListDisplay();

                    // Show success message
                    uploadStatus.innerHTML = `
                        <div class="alert alert-success py-2 px-3 fade show">
                            <i class="fas fa-check-circle me-2"></i> Files added successfully!
                            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const alert = uploadStatus.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                }
            }
        }

        // Initialize tooltip
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle file info button click
        const fileInfoBtn = document.getElementById('file-info-btn');
        if (fileInfoBtn) {
            fileInfoBtn.addEventListener('click', function() {
                // Create modal for file upload instructions
                const modalHtml = `
                    <div class="modal fade" id="fileUploadInfoModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" style="word-break: break-word; line-height: 1.3; font-size: 1.1rem;">File Upload Instructions</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" style="word-break: break-word; line-height: 1.5; font-size: 0.95rem;">
                                    <div class="mb-3">
                                        <h6 style="word-break: break-word; font-size: 1rem;"><i class="fas fa-info-circle text-primary me-2"></i>How to Add Multiple Files</h6>
                                        <p style="margin-bottom: 0.8rem;">You can add files in two ways:</p>
                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;">
                                                <strong>Method 1:</strong> Select multiple files at once
                                                <ul class="mt-2" style="padding-left: 1.2rem;">
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;"><strong>Windows:</strong> Hold <kbd>Ctrl</kbd> and click each file</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;"><strong>Mac:</strong> Hold <kbd>⌘ Command</kbd> and click each file</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;">
                                                <strong>Method 2:</strong> Add files incrementally
                                                <ul class="mt-2" style="padding-left: 1.2rem;">
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Select one or more files</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Click the <i class="fas fa-plus"></i> button to add more files</li>
                                                    <li style="margin-bottom: 0.3rem; word-break: break-word;">Repeat as needed to add different file types</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mb-3">
                                        <h6 style="word-break: break-word; font-size: 1rem;"><i class="fas fa-file-medical text-danger me-2"></i>Supported File Types</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;"><strong>Images:</strong> JPG, JPEG, PNG, GIF, BMP, WEBP</li>
                                            <li class="list-group-item" style="word-break: break-word; padding: 0.75rem;"><strong>Documents:</strong> PDF, DOCX, DOC, TXT, RTF</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-info" style="word-break: break-word; font-size: 0.9rem;">
                                        <i class="fas fa-robot me-2"></i> The AI will analyze <strong>all uploaded files together</strong> to provide a comprehensive analysis.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Add modal to body if it doesn't exist
                if (!document.getElementById('fileUploadInfoModal')) {
                    const modalContainer = document.createElement('div');
                    modalContainer.innerHTML = modalHtml;
                    document.body.appendChild(modalContainer);
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('fileUploadInfoModal'));
                modal.show();
            });
        }

        // Function to get all selected files
        function getSelectedFiles() {
            if (isDataTransferSupported) {
                return selectedFiles.files;
            } else {
                return selectedFilesArray;
            }
        }

        // Function to get the count of selected files
        function getSelectedFilesCount() {
            if (isDataTransferSupported) {
                return selectedFiles.files.length;
            } else {
                return selectedFilesArray.length;
            }
        }

        // Function to update the file list display
        function updateFileListDisplay() {
            selectedFilesContainer.innerHTML = '';

            const files = getSelectedFiles();
            const filesCount = getSelectedFilesCount();

            if (filesCount > 0) {
                // Create a container for file items
                const fileList = document.createElement('div');

                // Function to create file item element with improved styling
                function createFileItem(file, index) {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'selected-file';

                    // Determine file type and icon
                    let fileIcon = 'fa-file';
                    let iconColor = 'text-secondary';

                    // Get file extension
                    const fileExt = file.name.split('.').pop().toLowerCase();

                    // Set icon based on file type
                    if (file.type.match(/image\/.*/)) {
                        fileIcon = 'fa-file-image';
                        iconColor = 'text-primary';
                    } else if (file.type === 'application/pdf' || fileExt === 'pdf') {
                        fileIcon = 'fa-file-pdf';
                        iconColor = 'text-danger';
                    } else if (file.type.match(/.*word.*/) || ['doc', 'docx'].includes(fileExt)) {
                        fileIcon = 'fa-file-word';
                        iconColor = 'text-info';
                    } else if (file.type === 'text/plain' || fileExt === 'txt') {
                        fileIcon = 'fa-file-lines';
                        iconColor = 'text-secondary';
                    } else if (['xls', 'xlsx', 'csv'].includes(fileExt)) {
                        fileIcon = 'fa-file-excel';
                        iconColor = 'text-success';
                    } else if (['ppt', 'pptx'].includes(fileExt)) {
                        fileIcon = 'fa-file-powerpoint';
                        iconColor = 'text-warning';
                    } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(fileExt)) {
                        fileIcon = 'fa-file-archive';
                        iconColor = 'text-secondary';
                    } else if (['mp3', 'wav', 'ogg'].includes(fileExt)) {
                        fileIcon = 'fa-file-audio';
                        iconColor = 'text-info';
                    } else if (['mp4', 'avi', 'mov', 'wmv'].includes(fileExt)) {
                        fileIcon = 'fa-file-video';
                        iconColor = 'text-danger';
                    } else if (['html', 'htm', 'xml', 'json', 'js', 'css', 'php'].includes(fileExt)) {
                        fileIcon = 'fa-file-code';
                        iconColor = 'text-primary';
                    }

                    // Format file size
                    const fileSize = file.size < 1024 * 1024
                        ? Math.round(file.size / 1024) + ' KB'
                        : Math.round(file.size / (1024 * 1024) * 10) / 10 + ' MB';

                    // Create file item HTML with improved styling
                    fileItem.innerHTML = `
                        <span class="file-icon ${iconColor}"><i class="fas ${fileIcon}"></i></span>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${fileSize}</span>
                        <span class="file-remove" data-index="${index}" title="Remove file"><i class="fas fa-times-circle"></i></span>
                    `;

                    // Add event listener to remove button
                    const removeBtn = fileItem.querySelector('.file-remove');
                    removeBtn.addEventListener('click', function() {
                        const fileIndex = parseInt(this.getAttribute('data-index'));
                        removeFile(fileIndex);
                    });

                    return fileItem;
                }

                // Add all files to the list
                Array.from(files).forEach((file, index) => {
                    fileList.appendChild(createFileItem(file, index));
                });

                selectedFilesContainer.appendChild(fileList);

                // Check total size
                let totalSize = 0;
                for (let i = 0; i < filesCount; i++) {
                    totalSize += files[i].size;
                }

                // Add file count and total size info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'd-flex justify-content-between align-items-center mt-3';

                // Format total size
                const formattedTotalSize = totalSize < 1024 * 1024
                    ? Math.round(totalSize / 1024) + ' KB'
                    : Math.round(totalSize / (1024 * 1024) * 10) / 10 + ' MB';

                fileInfo.innerHTML = `
                    <div class="text-muted">
                        <small>${filesCount} file(s) selected (${formattedTotalSize})</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button">
                        <i class="fas fa-times me-1"></i> Clear All
                    </button>
                `;

                selectedFilesContainer.appendChild(fileInfo);

                // Add event listener to clear all button
                const clearAllBtn = fileInfo.querySelector('button');
                clearAllBtn.addEventListener('click', function() {
                    if (isDataTransferSupported) {
                        selectedFiles = new DataTransfer();
                        fileInput.files = selectedFiles.files;
                    } else {
                        selectedFilesArray = [];
                        fileInput.value = '';
                    }
                    updateFileListDisplay();

                    // Show status message
                    uploadStatus.innerHTML = `
                        <div class="alert alert-info py-2 px-3 fade show">
                            <i class="fas fa-info-circle me-2"></i> All files cleared
                            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        const alert = uploadStatus.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                });

                // Display warning if total size is large
                if (totalSize > 20 * 1024 * 1024) { // 20MB
                    const warning = document.createElement('div');
                    warning.className = 'alert alert-warning py-2 px-3 mt-2';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Large files may take longer to process';
                    selectedFilesContainer.appendChild(warning);
                }
            } else {
                // No files selected
                selectedFilesContainer.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-file-upload me-2"></i>No files selected yet
                    </div>
                `;
            }
        }

        // Function to remove a file by index
        function removeFile(index) {
            if (isDataTransferSupported) {
                const newFiles = new DataTransfer();

                Array.from(selectedFiles.files)
                    .filter((_, i) => i !== index)
                    .forEach(file => newFiles.items.add(file));

                selectedFiles = newFiles;
                fileInput.files = selectedFiles.files;
            } else {
                selectedFilesArray = selectedFilesArray.filter((_, i) => i !== index);

                // We can't update the file input directly in this case
                // The user will need to reselect files if they want to submit
                if (selectedFilesArray.length === 0) {
                    fileInput.value = '';
                }
            }
            updateFileListDisplay();
        }

        // Handle file input change
        if (fileInput && selectedFilesContainer) {
            fileInput.addEventListener('change', function() {
                // Add newly selected files to our collection
                if (this.files.length > 0) {
                    if (isDataTransferSupported) {
                        Array.from(this.files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = Array.from(selectedFiles.files).some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFiles.items.add(file);
                            }
                        });

                        // Update the file input with all files
                        fileInput.files = selectedFiles.files;
                    } else {
                        // For browsers without DataTransfer support
                        // We'll store the files in our array and display them
                        // But we can't modify the file input directly
                        Array.from(this.files).forEach(file => {
                            // Check if file with same name already exists
                            const fileExists = selectedFilesArray.some(f => f.name === file.name);
                            if (!fileExists) {
                                selectedFilesArray.push(file);
                            }
                        });

                        // Show a warning for browsers without DataTransfer support
                        if (!document.getElementById('dataTransferWarning')) {
                            const warning = document.createElement('div');
                            warning.id = 'dataTransferWarning';
                            warning.className = 'alert alert-warning py-1 px-2 mt-2';
                            warning.innerHTML = '<small><i class="fas fa-exclamation-triangle"></i> Your browser has limited support for file uploads. For best results, use Chrome, Edge, or Firefox.</small>';
                            fileStorageContainer.parentNode.insertBefore(warning, fileStorageContainer);
                        }
                    }

                    updateFileListDisplay();
                }
            });

            // Add "Add More Files" button handler
            if (addMoreFilesBtn) {
                addMoreFilesBtn.addEventListener('click', function() {
                    // Reset the file input to allow selecting the same file again
                    fileInput.value = '';
                    fileInput.click();
                });
            }

            // Add form submit handler to show upload status
            const form = document.getElementById('openaiForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const filesCount = getSelectedFilesCount();

                    if (filesCount > 0) {
                        // For browsers without DataTransfer support, we need to handle this differently
                        if (!isDataTransferSupported && selectedFilesArray.length > 0) {
                            // If the current file input doesn't match our stored files, we need to warn the user
                            if (fileInput.files.length !== selectedFilesArray.length) {
                                e.preventDefault();
                                alert('Please reselect all files before submitting. Your browser requires selecting all files at once.');
                                return;
                            }
                        }

                        // Show loading indicator
                        const existingLoader = document.getElementById('canvas-loader-overlay');
                        if (!existingLoader) {
                            const body = document.body;
                            const loaderHTML = body.getAttribute('data-loader-html');
                            if (loaderHTML) {
                                const loaderOverlay = document.createElement('div');
                                loaderOverlay.id = 'canvas-loader-overlay';
                                loaderOverlay.innerHTML = loaderHTML;
                                loaderOverlay.style.cssText = `
                                    position: fixed;
                                    top: 0;
                                    left: 0;
                                    width: 100%;
                                    height: 100%;
                                    background: rgba(44, 62, 80, 0.9);
                                    z-index: 9999;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    backdrop-filter: blur(5px);
                                `;

                                const svgContainer = loaderOverlay.querySelector('#css3-spinner-svg-pulse-wrapper');
                                if (svgContainer) {
                                    svgContainer.style.cssText = `
                                        text-align: center;
                                        padding: 20px;
                                    `;
                                }

                                document.body.appendChild(loaderOverlay);
                            } else {
                                document.getElementById('page-loader').style.display = 'flex';
                            }
                        }

                        // Update status
                        uploadStatus.innerHTML = `
                            <div class="alert alert-info py-1 px-2">
                                <small><i class="fas fa-spinner fa-spin"></i> Uploading and analyzing ${filesCount} file(s)...</small>
                            </div>
                        `;
                    }
                });
            }
        }
    });

    // Toggle function for Level 2 detailed report
    function toggleLevel2() {
        const content = document.getElementById('level2-content');
        const icon = document.querySelector('.toggle-icon');
        const hint = document.querySelector('.toggle-hint');

        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            icon.textContent = '▲';
            icon.classList.add('rotated');
            hint.textContent = 'Click to Collapse';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
            icon.classList.remove('rotated');
            hint.textContent = 'Click to Expand';
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
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
    });