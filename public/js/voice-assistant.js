document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let recognition;
    let isListening = false;
    let restartTimeout;
    let finalTranscript = '';
    let lastTranscriptTime = 0;
    let transcriptBuffer = '';
    let bufferTimeout;
    let currentLanguage = 'ar-SA'; // Start with Arabic for Middle East users - this is the best we can do with Web Speech API
    let sessionId = '';
    let selectedPatient = null;
    let isProcessing = false;
    let isHandsFreeMode = false;
    let aiAnalysis = '';
    let extractedData = {};
    let aiResultId = null;

    // Enhanced hands-free variables
    let silenceTimeout;
    let maxSilenceDuration = 30000; // 30 seconds
    let restartAttempts = 0;
    let maxRestartAttempts = 5;
    let audioContext;
    let analyser;
    let microphone;
    let audioLevel = 0;
    let isHandsFreePaused = false;
    let sessionStartTime = null;
    let totalRecordingTime = 0;
    let recordingTimer;

    // DOM Elements
    const startRecordingBtn = document.getElementById('startRecordingBtn');
    const stopRecordingBtn = document.getElementById('stopRecordingBtn');
    const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
    const resetSessionBtn = document.getElementById('resetSessionBtn');
    const patientSelect = document.getElementById('patientSelect');
    const transcriptionArea = document.getElementById('transcriptionArea');
    const languageSelector = document.getElementById('languageSelector');
    const handsFreeToggle = document.getElementById('handsFreeToggle');
    const jsProgressIndicator = document.getElementById('jsProgressIndicator');
    const jsProcessingStage = document.getElementById('jsProcessingStage');
    const aiAnalysisArea = document.getElementById('aiAnalysisArea');
    const symptomsField = document.getElementById('symptoms');
    const medicalHistoryField = document.getElementById('medicalHistory');
    const physicalFindingsField = document.getElementById('physicalFindings');
    const medicationsField = document.getElementById('medications');
    const vitalSignsField = document.getElementById('vitalSigns');
    const diagnosisField = document.getElementById('diagnosis');
    const carePlanField = document.getElementById('carePlan');

    // Initialize the voice assistant
    function initVoiceAssistant() {
        // Set initial session ID from data attribute on the container div
        const container = document.querySelector('[data-session-id]');
        sessionId = container ? container.getAttribute('data-session-id') : generateUUID();

        // Restore session state from localStorage if available
        restoreSessionState();

        // Initialize selected patient if one is already selected
        syncPatientSelection();

        // Initialize speech recognition
        initSpeechRecognition();

        // Set up event listeners
        setupEventListeners();

        // Initialize language selector
        initLanguageSelector();

        // Load patients
        loadPatients();

        // Initialize Bootstrap tooltips
        initTooltips();
    
        // Update UI based on initial state
        updateRecordingUI();
        updateHandsFreeStatus();
    
        // Set up periodic session state saving
        setInterval(saveSessionState, 5000); // Save every 5 seconds
    
        // Set up keyboard shortcuts
        setupKeyboardShortcuts();
    
        // Log initialization status
        
        
        
        
    }

    // Session persistence functions
    function saveSessionState() {
        if (!sessionId) return;

        const state = {
            sessionId: sessionId,
            selectedPatient: selectedPatient,
            isHandsFreeMode: isHandsFreeMode,
            finalTranscript: finalTranscript,
            extractedData: extractedData,
            aiAnalysis: aiAnalysis,
            timestamp: Date.now()
        };

        try {
            localStorage.setItem('voiceAssistantState', JSON.stringify(state));
        } catch (error) {
            ;
        }
    }

    function restoreSessionState() {
        try {
            const savedState = localStorage.getItem('voiceAssistantState');
            if (!savedState) return;

            const state = JSON.parse(savedState);

            // Only restore if the state is recent (within 1 hour)
            if (Date.now() - state.timestamp > 3600000) {
                localStorage.removeItem('voiceAssistantState');
                return;
            }

            // Restore state
            if (state.sessionId) sessionId = state.sessionId;
            if (state.selectedPatient) selectedPatient = state.selectedPatient;
            if (state.isHandsFreeMode) {
                isHandsFreeMode = state.isHandsFreeMode;
                if (handsFreeToggle) handsFreeToggle.checked = true;
            }
            if (state.finalTranscript) finalTranscript = state.finalTranscript;
            if (state.extractedData) extractedData = state.extractedData;
            if (state.aiAnalysis) aiAnalysis = state.aiAnalysis;

            
        } catch (error) {
            ;
            localStorage.removeItem('voiceAssistantState');
        }
    }

    function clearSessionState() {
        try {
            localStorage.removeItem('voiceAssistantState');
        } catch (error) {
            ;
        }
    }

    // Initialize language selector
    function initLanguageSelector() {
        const languageSelector = document.getElementById('languageSelector');
        if (!languageSelector) return;

        // Set initial value based on current language
        if (currentLanguage === 'ar-SA') {
            languageSelector.value = 'ar';
        } else if (currentLanguage === 'en-US') {
            languageSelector.value = 'en';
        } else {
            languageSelector.value = 'ar'; // Default to Arabic
        }

        // Add change event listener
        languageSelector.addEventListener('change', function() {
            const selectedLang = this.value;
            console.log('🔄 Language selector changed to:', selectedLang);

            // Only allow language changes when not recording
            if (isListening) {
                showAlert('Please stop recording before changing language.', 'warning');
                // Reset selector to current language
                this.value = currentLanguage === 'ar-SA' ? 'ar' : 'en';
                return;
            }

            // Set the recognition language
            setRecognitionLanguage(selectedLang);
        });
    }

    // Keyboard shortcuts
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(event) {
            // Only activate shortcuts when not typing in input fields
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA' || event.target.tagName === 'SELECT') {
                return;
            }

            // Ctrl/Cmd + Space: Toggle recording
            if ((event.ctrlKey || event.metaKey) && event.code === 'Space') {
                event.preventDefault();
                if (isListening) {
                    stopSession();
                } else {
                    startSession();
                }
            }

            // Ctrl/Cmd + H: Toggle hands-free mode
            if ((event.ctrlKey || event.metaKey) && event.code === 'KeyH') {
                event.preventDefault();
                if (handsFreeToggle) {
                    handsFreeToggle.checked = !handsFreeToggle.checked;
                    handsFreeToggle.dispatchEvent(new Event('change'));
                }
            }

            // Ctrl/Cmd + P: Pause/Resume hands-free mode
            if ((event.ctrlKey || event.metaKey) && event.code === 'KeyP') {
                event.preventDefault();
                const pauseResumeBtn = document.getElementById('pauseResumeBtn');
                if (pauseResumeBtn && pauseResumeBtn.style.display !== 'none') {
                    pauseResumeBtn.click();
                }
            }

            // Ctrl/Cmd + R: Reset session
            if ((event.ctrlKey || event.metaKey) && event.code === 'KeyR') {
                event.preventDefault();
                resetSession();
            }

            // Ctrl/Cmd + G: Generate AI analysis
            if ((event.ctrlKey || event.metaKey) && event.code === 'KeyG') {
                event.preventDefault();
                if (generateAnalysisBtn && !generateAnalysisBtn.disabled) {
                    generateAnalysisBtn.click();
                }
            }
        });

        // Show keyboard shortcuts help
        showKeyboardShortcutsHelp();
    }

    function showKeyboardShortcutsHelp() {
        // Create a small help indicator
        const helpIndicator = document.createElement('div');
        helpIndicator.className = 'position-fixed bottom-0 end-0 m-3 p-3 keyboard-shortcuts-help text-white rounded shadow-lg';
        helpIndicator.style.zIndex = '1050';
        helpIndicator.style.fontSize = '0.75rem';
        helpIndicator.innerHTML = `
            <div class="fw-bold mb-2">
                <i class="fas fa-keyboard me-2"></i>
                Keyboard Shortcuts
            </div>
            <div class="mb-1"><kbd>Ctrl+Space</kbd> Start/Stop Recording</div>
            <div class="mb-1"><kbd>Ctrl+H</kbd> Toggle Hands-Free</div>
            <div class="mb-1"><kbd>Ctrl+P</kbd> Pause/Resume</div>
            <div class="mb-1"><kbd>Ctrl+R</kbd> Reset Session</div>
            <div class="mb-1"><kbd>Ctrl+G</kbd> Generate Analysis</div>
            <div class="mt-2 small text-muted">
                <i class="fas fa-clock me-1"></i>
                Auto-hide in 10s
            </div>
        `;

        document.body.appendChild(helpIndicator);

        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (helpIndicator.parentNode) {
                helpIndicator.remove();
            }
        }, 10000);
    }

    // Generate UUID for session ID
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Initialize speech recognition
    function initSpeechRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();

            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = currentLanguage;
            recognition.maxAlternatives = 3;

            // Debug logging for language changes
            console.log('🎙️ Speech recognition initialized with language:', currentLanguage);
            console.log('🌐 Supported languages will be auto-detected from speech patterns');
            console.log('🕐 Current timezone:', Intl.DateTimeFormat().resolvedOptions().timeZone);
            console.log('� Debug: Use window.voiceAssistant.forceArabicMode() or forceEnglishMode()');

            recognition.onresult = function(event) {
                let interimTranscript = '';
                let newFinalTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const result = event.results[i];
                    const transcript = result[0].transcript;

                    if (result.isFinal) {
                        newFinalTranscript += transcript + ' ';
                        console.log('🎤 Final transcript received:', transcript);

                        // Detect language and switch if needed - only on final results
                        console.log('🎤 Processing final transcript:', transcript);
                        const detectedLang = detectLanguage(transcript);
                        console.log('🔍 Detected language:', detectedLang, 'Current language:', currentLanguage);

                        if (detectedLang !== currentLanguage) {
                            const previousLanguage = currentLanguage;
                            currentLanguage = detectedLang;

                            // Show notification when language changes
                            const languageNames = {
                                'ar-SA': 'العربية',
                                'en-US': 'English',
                                'fr-FR': 'Français',
                                'es-ES': 'Español',
                                'de-DE': 'Deutsch'
                            };

                            if (previousLanguage !== detectedLang) {
                                showAlert(`Language automatically switched to ${languageNames[detectedLang] || detectedLang}`, 'info');
                                console.log('🔄 Language switched from', previousLanguage, 'to', detectedLang);

                                // Force restart recognition with new language
                                if (isListening) {
                                    console.log('🔄 Restarting recognition with new language:', detectedLang);
                                    recognition.stop();
                                    setTimeout(() => {
                                        if (isListening) {
                                            recognition.lang = currentLanguage;
                                            recognition.start();
                                            console.log('✅ Recognition restarted with language:', currentLanguage);
                                        }
                                    }, 500); // Increased delay for better stability
                                }
                            }
                        } else {
                            // If we're in Arabic mode and getting Arabic text, ensure recognition stays in Arabic
                            if (currentLanguage === 'ar-SA' && transcript.trim().length > 0) {
                                console.log('🇸🇦 Maintaining Arabic recognition for text:', transcript);
                            } else if (currentLanguage === 'en-US' && transcript.trim().length > 0) {
                                console.log('🇺🇸 Maintaining English recognition for text:', transcript);
                            }
                        }
                    } else {
                        interimTranscript += transcript;
                        // Also try to detect language from interim results for faster switching
                        if (interimTranscript.length > 1) { // Very sensitive - trigger after just 1 character
                            console.log('⚡ Checking interim transcript:', interimTranscript);
                            const interimDetectedLang = detectLanguage(interimTranscript);
                            console.log('⚡ Interim detection result:', interimDetectedLang, 'Current:', currentLanguage);
                            if (interimDetectedLang !== currentLanguage) {
                                console.log('⚡ Early language detection from interim result:', interimTranscript, '->', interimDetectedLang);
                                const oldLanguage = currentLanguage;
                                currentLanguage = interimDetectedLang;
                                updateLanguageIndicator(currentLanguage);

                                // Immediately update recognition language without stopping
                                if (isListening) {
                                    recognition.lang = currentLanguage;
                                    console.log('🔄 Immediately updated recognition language from', oldLanguage, 'to:', currentLanguage);
                                }
                            }
                        }
                    }
                }

                // Update final transcript
                if (newFinalTranscript) {
                    finalTranscript += newFinalTranscript;
                    lastTranscriptTime = Date.now();
                }

                // Buffer the transcript to avoid too frequent updates
                transcriptBuffer = finalTranscript + interimTranscript;

                // Clear existing timeout
                if (bufferTimeout) {
                    clearTimeout(bufferTimeout);
                }

                // Send transcript after a short delay to batch updates
                bufferTimeout = setTimeout(() => {
                    if (transcriptBuffer.trim()) {
                        handleTranscription(transcriptBuffer.trim());
                    }
                }, 500);
            };

            recognition.onerror = function(event) {
                ;

                switch(event.error) {
                    case 'not-allowed':
                        showAlert('Microphone access denied. Please allow microphone access and try again.', 'error');
                        isListening = false;
                        updateRecordingUI();
                        break;
                    case 'no-speech':
                        
                        break;
                    case 'audio-capture':
                        showAlert('No microphone found. Please check your microphone connection.', 'error');
                        isListening = false;
                        updateRecordingUI();
                        break;
                    case 'network':
                        
                        break;
                    case 'aborted':
                        
                        break;
                    default:
                        
                }
            };

            recognition.onstart = function() {
                
            };

            recognition.onend = function() {
                

                if (isListening) {
                    // Auto-restart in hands-free mode with enhanced error handling
                    if (isHandsFreeMode && !isHandsFreePaused) {
                        if (restartAttempts < maxRestartAttempts) {
                            const delay = Math.min(100 * Math.pow(2, restartAttempts), 5000); // Exponential backoff
                            restartTimeout = setTimeout(() => {
                                if (isListening && isHandsFreeMode && !isHandsFreePaused) {
                                    try {
                                        recognition.lang = currentLanguage;
                                        recognition.start();
                                        restartAttempts = 0; // Reset on successful restart
                                        
                                    } catch (error) {
                                        ;
                                        restartAttempts++;

                                        if (restartAttempts >= maxRestartAttempts) {
                                            showAlert('Voice recognition failed multiple times. Please check your microphone and try again.', 'error');
                                            isListening = false;
                                            isHandsFreeMode = false;
                                            if (handsFreeToggle) handsFreeToggle.checked = false;
                                            updateRecordingUI();
                                            updateHandsFreeStatus();
                                        }
                                    }
                                }
                            }, delay);
                        } else {
                            showAlert('Maximum restart attempts reached. Hands-free mode disabled.', 'error');
                            isHandsFreeMode = false;
                            if (handsFreeToggle) handsFreeToggle.checked = false;
                            updateHandsFreeStatus();
                        }
                    }
                }
            };
        } else {
            showAlert('Your browser does not support speech recognition. Please use Chrome, Edge, or Safari.', 'error');
        }
    }

    // Enhanced automatic language detection with speech pattern analysis
    function detectLanguage(text) {
        if (!text || text.trim().length === 0) {
            return currentLanguage;
        }

        const cleanText = text.trim();

        // Debug logging
        console.log('🔍 Detecting language for text:', cleanText);
        console.log('📏 Text length:', cleanText.length);

        // Arabic detection (more comprehensive - includes all Arabic Unicode blocks)
        const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/g;
        const arabicChars = (cleanText.match(arabicPattern) || []).length;

        // English detection
        const englishPattern = /[a-zA-Z]/g;
        const englishChars = (cleanText.match(englishPattern) || []).length;

        // French detection (common French characters)
        const frenchPattern = /[àâäéèêëïîôöùûüÿçÀÂÄÉÈÊËÏÎÔÖÙÛÜŸÇ]/g;
        const frenchChars = (cleanText.match(frenchPattern) || []).length;

        // Spanish detection (common Spanish characters)
        const spanishPattern = /[áéíóúüñ¿¡ÁÉÍÓÚÜÑ]/g;
        const spanishChars = (cleanText.match(spanishPattern) || []).length;

        // German detection (common German characters)
        const germanPattern = /[äöüßÄÖÜẞ]/g;
        const germanChars = (cleanText.match(germanPattern) || []).length;

        // Determine dominant language based on character count
        const totalChars = cleanText.length;
        const arabicRatio = arabicChars / totalChars;
        const englishRatio = englishChars / totalChars;
        const frenchRatio = frenchChars / totalChars;
        const spanishRatio = spanishChars / totalChars;
        const germanRatio = germanChars / totalChars;

        console.log('📊 Language ratios:', {
            arabic: arabicRatio.toFixed(3),
            english: englishRatio.toFixed(3),
            french: frenchRatio.toFixed(3),
            spanish: spanishRatio.toFixed(3),
            german: germanRatio.toFixed(3),
            totalChars: totalChars,
            arabicChars: arabicChars,
            englishChars: englishChars
        });

        // Language mapping with confidence thresholds
        const languages = [
            { code: 'ar-SA', ratio: arabicRatio, threshold: 0.01, name: 'العربية' }, // Very low threshold for Arabic
            { code: 'fr-FR', ratio: frenchRatio, threshold: 0.1, name: 'Français' },
            { code: 'es-ES', ratio: spanishRatio, threshold: 0.1, name: 'Español' },
            { code: 'de-DE', ratio: germanRatio, threshold: 0.1, name: 'Deutsch' },
            { code: 'en-US', ratio: englishRatio, threshold: 0.01, name: 'English' } // Very low threshold for English
        ];

        // Find the language with highest ratio above threshold
        let detectedLang = currentLanguage;
        let maxRatio = 0;

        for (const lang of languages) {
            if (lang.ratio > lang.threshold && lang.ratio > maxRatio) {
                detectedLang = lang.code;
                maxRatio = lang.ratio;
                console.log('🎯 Detected language:', lang.name, 'with ratio:', lang.ratio.toFixed(3));
            }
        }

        // Special handling: if we have ANY Arabic characters, prioritize Arabic
        if (arabicChars > 0) {
            detectedLang = 'ar-SA';
            console.log('🇸🇦 Arabic detected! Switching to Arabic (ar-SA)');
        }

        // Update UI indicator if language changed
        if (detectedLang !== currentLanguage) {
            console.log('🔄 Language changed from', currentLanguage, 'to', detectedLang);
            updateLanguageIndicator(detectedLang);
        }

        return detectedLang;
    }

    // Advanced language detection based on speech patterns (not just transcribed text)
    function detectSpokenLanguage(audioData) {
        // This is a placeholder for more advanced speech pattern analysis
        // In a real implementation, this would analyze audio features like:
        // - Phoneme patterns
        // - Speech rhythm
        // - Acoustic features
        // - Language-specific sound patterns

        // For now, we'll rely on text-based detection after transcription
        // But this function could be extended with audio analysis libraries

        return new Promise((resolve) => {
            // Simulate analysis delay
            setTimeout(() => {
                resolve(currentLanguage);
            }, 100);
        });
    }

    // Set recognition language (now only used internally for auto-detection)
    function setRecognitionLanguage(lang) {
        const supportedLanguages = {
            'ar': 'ar-SA',
            'en': 'en-US',
            'fr': 'fr-FR',
            'es': 'es-ES',
            'de': 'de-DE',
        };

        if (supportedLanguages[lang]) {
            const newLanguage = supportedLanguages[lang];
            console.log('🔄 Manually setting language to:', newLanguage);

            if (newLanguage !== currentLanguage) {
                const oldLanguage = currentLanguage;
                currentLanguage = newLanguage;
                updateLanguageIndicator(currentLanguage);

                if (recognition && isListening) {
                    console.log('🔄 Restarting recognition with new language:', currentLanguage);
                    recognition.stop();
                    setTimeout(() => {
                        if (isListening) {
                            recognition.lang = currentLanguage;
                            recognition.start();
                            console.log('✅ Recognition restarted with language:', currentLanguage);
                        }
                    }, 300);
                }

                showAlert(`Language switched to ${newLanguage === 'ar-SA' ? 'العربية' : 'English'}`, 'info');
            }
        }
    }

    // Update language indicator in UI
    function updateLanguageIndicator(languageCode) {
        const indicator = document.getElementById('autoLanguageIndicator');
        if (!indicator) return;

        const languageNames = {
            'ar-SA': 'العربية',
            'en-US': 'English',
            'fr-FR': 'Français',
            'es-ES': 'Español',
            'de-DE': 'Deutsch'
        };

        const languageName = languageNames[languageCode] || 'Auto-Detecting';
        const flagIcon = getLanguageFlag(languageCode);

        indicator.innerHTML = `
            <i class="fas fa-brain me-1"></i>
            ${flagIcon} ${languageName}
        `;

        // Add visual feedback for language change
        indicator.classList.add('language-changed');
        setTimeout(() => {
            indicator.classList.remove('language-changed');
        }, 2000);

        console.log('🎯 Language indicator updated to:', languageName, '(', languageCode, ')');
    }

    // Get flag emoji for language
    function getLanguageFlag(languageCode) {
        const flags = {
            'ar-SA': '🇸🇦',
            'en-US': '🇺🇸',
            'fr-FR': '🇫🇷',
            'es-ES': '🇪🇸',
            'de-DE': '🇩🇪'
        };
        return flags[languageCode] || '🌐';
    }

    // Initialize Bootstrap tooltips
    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Set up event listeners
    function setupEventListeners() {
        // Recording buttons
        if (startRecordingBtn) {
            startRecordingBtn.addEventListener('click', startSession);
        }

        if (stopRecordingBtn) {
            stopRecordingBtn.addEventListener('click', stopSession);
        }

        // Generate analysis button
        if (generateAnalysisBtn) {
            generateAnalysisBtn.addEventListener('click', generateAnalysis);
        }

        // Reset session button
        if (resetSessionBtn) {
            resetSessionBtn.addEventListener('click', resetSession);
        }

        // Patient selection
        if (patientSelect) {
            patientSelect.addEventListener('change', function() {
                selectedPatient = this.value || null;
                
                
                updateRecordingUI();
            });

            // Also listen for input events in case of programmatic changes
            patientSelect.addEventListener('input', function() {
                selectedPatient = this.value || null;
                
                updateRecordingUI();
            });
        }

        // Initialize language indicator
        updateLanguageIndicator(currentLanguage);

        // Hands-free toggle with enhanced functionality
        if (handsFreeToggle) {
            handsFreeToggle.addEventListener('change', function() {
                isHandsFreeMode = this.checked;
                isHandsFreePaused = false;
                restartAttempts = 0;

                if (isHandsFreeMode) {
                    if (!isListening) {
                        startSession();
                    }
                    initAudioLevelMonitoring();
                    startSilenceDetection();
                    showAlert('Hands-free mode enabled. Recording will continue automatically.', 'info');
                } else {
                    stopSilenceDetection();
                    stopAudioLevelMonitoring();
                    showAlert('Hands-free mode disabled.', 'info');
                }

                updateHandsFreeStatus();
            });
        }

        // Add pause/resume button for hands-free mode
        const pauseResumeBtn = document.createElement('button');
        pauseResumeBtn.id = 'pauseResumeBtn';
        pauseResumeBtn.className = 'btn btn-warning btn-sm';
        pauseResumeBtn.style.display = 'none';
        pauseResumeBtn.innerHTML = '<i class="fas fa-pause me-1"></i>Pause';

        if (resetSessionBtn && resetSessionBtn.parentNode) {
            resetSessionBtn.parentNode.insertBefore(pauseResumeBtn, resetSessionBtn);
        }

        pauseResumeBtn.addEventListener('click', function() {
            if (isHandsFreePaused) {
                resumeHandsFree();
            } else {
                pauseHandsFree();
            }
        });
    }

    // Load patients
    function loadPatients() {
        // This would typically be an AJAX call to load patients
        // For now, we'll just check if patients are already loaded
        
    }

    // Sync patient selection
    function syncPatientSelection() {
        if (patientSelect && patientSelect.value) {
            selectedPatient = patientSelect.value;
            
            return true;
        }
        selectedPatient = null;
        
        return false;
    }

    // Start session
    function startSession() {
        // Debug logging
        
        

        // Sync patient selection to ensure we have the latest value
        syncPatientSelection();

        if (!selectedPatient || selectedPatient === '') {
            showAlert('Please select a patient first.', 'error');
            return;
        }

        // AJAX call to start session
        $.ajax({
            url: '/ai/voice-assistant/start-session',
            method: 'POST',
            data: {
                selectedPatient: selectedPatient,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    sessionId = response.sessionId;
                    isListening = true;
                    finalTranscript = '';
                    transcriptBuffer = '';
                    restartAttempts = 0;

                    // Clear any existing timeouts
                    if (bufferTimeout) clearTimeout(bufferTimeout);
                    if (restartTimeout) clearTimeout(restartTimeout);

                    try {
                        recognition.lang = currentLanguage;
                        recognition.start();
                        

                        // Start enhanced features
                        startRecordingTimer();
                        if (isHandsFreeMode) {
                            startSilenceDetection();
                        }

                        updateRecordingUI();
                        updateHandsFreeStatus();
                        showAlert('Session started successfully. Language detection is active.', 'success');
                    } catch (error) {
                        ;
                        isListening = false;
                        updateRecordingUI();
                        updateHandsFreeStatus();
                    }
                } else {
                    showAlert(response.message || 'Failed to start session.', 'error');
                }
            },
            error: function(xhr, status, error) {
                ;
                showAlert('Failed to start session. Please try again.', 'error');
            }
        });
    }

    // Stop session
    function stopSession() {
        if (recognition && isListening) {
            isListening = false;

            // Clear timeouts and enhanced features
            if (restartTimeout) clearTimeout(restartTimeout);
            if (bufferTimeout) clearTimeout(bufferTimeout);
            stopSilenceDetection();
            stopRecordingTimer();

            try {
                recognition.stop();
                

                // Send any remaining buffered transcript
                if (transcriptBuffer.trim()) {
                    handleTranscription(transcriptBuffer.trim());
                }
            } catch (error) {
                ;
            }
        }

        // AJAX call to stop session
        $.ajax({
            url: '/ai/voice-assistant/stop-session',
            method: 'POST',
            data: {
                sessionId: sessionId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateRecordingUI();
                    updateHandsFreeStatus();
                    showAlert('Session stopped successfully.', 'success');
                } else {
                    showAlert(response.message || 'Failed to stop session.', 'error');
                }
            },
            error: function(xhr, status, error) {
                ;
                showAlert('Failed to stop session. Please try again.', 'error');
            }
        });
    }

    // Handle transcription
    function handleTranscription(text) {
        // Clean and validate the input
        const cleanText = text.trim();
        if (empty(cleanText)) {
            return;
        }

        // Avoid duplicate processing of the same text
        if (transcriptionArea && transcriptionArea.value === cleanText) {
            return;
        }

        // Update UI
        if (transcriptionArea) {
            transcriptionArea.value = cleanText;
        }

        // AJAX call to handle transcription
        $.ajax({
            url: '/ai/voice-assistant/handle-transcription',
            method: 'POST',
            data: {
                text: cleanText,
                sessionId: sessionId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    
                } else {
                    ;
                }
            },
            error: function(xhr, status, error) {
                ;
            }
        });
    }

    // Process with AI (for analysis generation with callback)
    function processWithAIForAnalysis(text, callback) {
        // Skip processing if transcription is too short
        if (text.length < 10) {
            isProcessing = false;
            hideProgressIndicator();
            return;
        }

        updateProcessingStage('Analyzing medical content with AI...');

        // AJAX call to process with AI
        $.ajax({
            url: '/ai/voice-assistant/process-with-ai',
            method: 'POST',
            data: {
                transcription: text,
                sessionId: sessionId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateProcessingStage('Parsing AI response and extracting medical data...');
                    extractedData = response.extractedData;
                    updateChartFields(extractedData);
                    updateProcessingStage('Medical data extraction completed!');
                    

                    // Call the callback function if provided
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                } else {
                    updateProcessingStage('Failed to parse AI response.');
                    ;
                    isProcessing = false;
                    hideProgressIndicator();
                }
            },
            error: function(xhr, status, error) {
                ;
                updateProcessingStage('AI processing failed.');
                isProcessing = false;
                hideProgressIndicator();
            }
        });
    }

    // Process with AI (legacy function for compatibility)
    function processWithAI(text) {
        processWithAIForAnalysis(text, null);
    }

    // Update chart fields
    function updateChartFields(data) {
        if (symptomsField) symptomsField.value = smartAppendToField(symptomsField.value, data.symptoms || '');
        if (medicalHistoryField) medicalHistoryField.value = smartAppendToField(medicalHistoryField.value, data.medical_history || '');
        if (physicalFindingsField) physicalFindingsField.value = smartAppendToField(physicalFindingsField.value, data.physical_findings || '');
        if (medicationsField) medicationsField.value = smartAppendToField(medicationsField.value, data.medications || '');
        if (vitalSignsField) vitalSignsField.value = smartAppendToField(vitalSignsField.value, data.vital_signs || '');
        if (diagnosisField) diagnosisField.value = smartAppendToField(diagnosisField.value, data.diagnosis || '');
        if (carePlanField) carePlanField.value = smartAppendToField(carePlanField.value, data.care_plan || '');
    }

    // Smart append to field
    function smartAppendToField(existing, newContent) {
        if (empty(newContent)) return existing;
        if (empty(existing)) return newContent;

        // Clean and normalize text
        const existingClean = existing.trim();
        const newClean = newContent.trim();

        // Avoid duplicating similar content
        const existingWords = existingClean.toLowerCase().split(' ');
        const newWords = newClean.toLowerCase().split(' ');
        const commonWords = existingWords.filter(word => newWords.includes(word));

        // If more than 70% of words are common, replace instead of append
        if (commonWords.length > 0.7 * newWords.length) {
            return newClean; // Replace with newer, potentially more complete information
        }

        return existingClean + "\n\n" + newClean;
    }

    // Generate AI analysis
    function generateAnalysis() {
        const transcription = transcriptionArea ? transcriptionArea.value : '';

        if (empty(transcription)) {
            showAlert('No transcription available. Please record some audio first.', 'error');
            return;
        }

        if (!selectedPatient) {
            showAlert('Please select a patient first.', 'error');
            return;
        }

        // Force UI update by setting processing state first
        isProcessing = true;
        showProgressIndicator();
        updateProcessingStage('Initializing AI analysis...');

        // First, extract structured data from transcription if not already done
        if (Object.keys(extractedData).length === 0) {
            updateProcessingStage('Extracting medical data from transcription...');

            // Process with AI first, then generate analysis
            processWithAIForAnalysis(transcription, function() {
                // After processing is complete, generate the analysis
                generateAIAnalysisRequest(transcription);
            });
            return;
        }

        // Generate comprehensive AI analysis
        generateAIAnalysisRequest(transcription);
    }

    // Separate function to handle the AI analysis request
    function generateAIAnalysisRequest(transcription) {
        updateProcessingStage('Generating comprehensive medical analysis...');

        // AJAX call to generate AI analysis
        $.ajax({
            url: '/ai/voice-assistant/generate-ai-analysis',
            method: 'POST',
            data: {
                sessionId: sessionId,
                transcription: transcription,
                extractedData: extractedData,
                selectedPatient: selectedPatient,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    aiAnalysis = response.aiAnalysis;
                    updateProcessingStage('Creating AI result record...');

                    // Update AI analysis area
                    if (aiAnalysisArea) {
                        aiAnalysisArea.innerHTML = '<div style="white-space: pre-wrap;">' + aiAnalysis + '</div>';

                        // Show the AI analysis section
                        const aiAnalysisSection = document.getElementById('aiAnalysisSection');
                        if (aiAnalysisSection) {
                            aiAnalysisSection.style.display = 'block';
                        }
                    }

                    // Now create the AI assistant result record
                    createAiAssistantResult(transcription);
                } else {
                    updateProcessingStage('Analysis failed. Please try again.');
                    showAlert(response.message || 'Failed to generate analysis. Please try again.', 'error');
                    isProcessing = false;
                    hideProgressIndicator();
                }
            },
            error: function(xhr, status, error) {
                ;
                updateProcessingStage('Analysis failed. Please try again.');
                showAlert('Failed to generate analysis. Please try again.', 'error');
                isProcessing = false;
                hideProgressIndicator();
            }
        });
    }

    // Create AI assistant result record
    function createAiAssistantResult(transcription) {
        // AJAX call to create AI assistant result
        $.ajax({
            url: '/ai/voice-assistant/create-ai-result',
            method: 'POST',
            data: {
                sessionId: sessionId,
                transcription: transcription,
                extractedData: extractedData,
                selectedPatient: selectedPatient,
                aiAnalysis: aiAnalysis,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Store the AI result ID for later use
                    aiResultId = response.aiResultId;
                    

                    updateProcessingStage('Analysis completed successfully!');
                    showAlert('AI analysis generated successfully!', 'success');

                    // Show the manual diagnosis form
                    const manualDiagnosisForm = document.getElementById('manualDiagnosisForm');
                    if (manualDiagnosisForm) {
                        manualDiagnosisForm.style.display = 'block';
                    }
                } else {
                    updateProcessingStage('Failed to create AI result record.');
                    showAlert(response.message || 'Failed to create AI result record.', 'error');
                }

                isProcessing = false;
                hideProgressIndicator();
            },
            error: function(xhr, status, error) {
                ;
                updateProcessingStage('Failed to create AI result record.');
                showAlert('Failed to create AI result record. Please try again.', 'error');
                isProcessing = false;
                hideProgressIndicator();
            }
        });
    }

    // Enhanced hands-free functions
    function updateHandsFreeStatus() {
        const pauseResumeBtn = document.getElementById('pauseResumeBtn');
        const handsFreeStatus = document.getElementById('handsFreeStatus') || createHandsFreeStatusElement();

        if (isHandsFreeMode) {
            if (pauseResumeBtn) pauseResumeBtn.style.display = 'inline-block';

            if (isHandsFreePaused) {
                handsFreeStatus.innerHTML = '<span class="badge bg-warning me-2"><i class="fas fa-pause fa-xs"></i></span><span class="text-warning">Hands-Free Paused</span>';
                handsFreeStatus.className = 'd-flex align-items-center';
                if (pauseResumeBtn) {
                    pauseResumeBtn.innerHTML = '<i class="fas fa-play me-1"></i>Resume';
                    pauseResumeBtn.className = 'btn btn-success btn-sm';
                }
            } else {
                handsFreeStatus.innerHTML = '<span class="badge bg-success me-2 status-active"><i class="fas fa-robot fa-xs"></i></span><span class="text-success fw-bold">Hands-Free Active</span>';
                handsFreeStatus.className = 'd-flex align-items-center hands-free-active';
                if (pauseResumeBtn) {
                    pauseResumeBtn.innerHTML = '<i class="fas fa-pause me-1"></i>Pause';
                    pauseResumeBtn.className = 'btn btn-warning btn-sm';
                }
            }
        } else {
            if (pauseResumeBtn) pauseResumeBtn.style.display = 'none';
            handsFreeStatus.innerHTML = '';
        }

        updateRecordingTimer();
    }

    function createHandsFreeStatusElement() {
        const statusElement = document.createElement('div');
        statusElement.id = 'handsFreeStatus';
        statusElement.className = 'd-flex align-items-center';

        const enhancedContainer = document.getElementById('enhancedStatusContainer');
        if (enhancedContainer) {
            enhancedContainer.appendChild(statusElement);
        } else {
            // Fallback to original location
            const recordingStatus = document.getElementById('recordingStatus');
            if (recordingStatus && recordingStatus.parentNode) {
                recordingStatus.parentNode.insertBefore(statusElement, recordingStatus.nextSibling);
            }
        }

        return statusElement;
    }

    function pauseHandsFree() {
        if (!isHandsFreeMode) return;

        isHandsFreePaused = true;
        if (recognition && isListening) {
            try {
                recognition.stop();
            } catch (error) {
                ;
            }
        }

        stopSilenceDetection();
        updateHandsFreeStatus();
        showAlert('Hands-free mode paused.', 'info');
    }

    function resumeHandsFree() {
        if (!isHandsFreeMode) return;

        isHandsFreePaused = false;
        restartAttempts = 0;

        if (isListening) {
            try {
                recognition.lang = currentLanguage;
                recognition.start();
            } catch (error) {
                ;
            }
        }

        startSilenceDetection();
        updateHandsFreeStatus();
        showAlert('Hands-free mode resumed.', 'success');
    }

    function startSilenceDetection() {
        stopSilenceDetection(); // Clear any existing timeout

        silenceTimeout = setTimeout(() => {
            if (isHandsFreeMode && !isHandsFreePaused && isListening) {
                
                showAlert('Long silence detected. Recording continues in hands-free mode.', 'info');

                // Reset the silence timer
                startSilenceDetection();
            }
        }, maxSilenceDuration);
    }

    function stopSilenceDetection() {
        if (silenceTimeout) {
            clearTimeout(silenceTimeout);
            silenceTimeout = null;
        }
    }

    function initAudioLevelMonitoring() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            ;
            return;
        }

        navigator.mediaDevices.getUserMedia({ audio: true })
            .then(stream => {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                analyser = audioContext.createAnalyser();
                microphone = audioContext.createMediaStreamSource(stream);

                analyser.fftSize = 256;
                microphone.connect(analyser);

                monitorAudioLevel();
            })
            .catch(error => {
                ;
            });
    }

    function monitorAudioLevel() {
        if (!analyser) return;

        const dataArray = new Uint8Array(analyser.frequencyBinCount);
        analyser.getByteFrequencyData(dataArray);

        // Calculate average audio level
        let sum = 0;
        for (let i = 0; i < dataArray.length; i++) {
            sum += dataArray[i];
        }
        audioLevel = sum / dataArray.length;

        updateAudioLevelIndicator();

        if (isHandsFreeMode) {
            requestAnimationFrame(monitorAudioLevel);
        }
    }

    function updateAudioLevelIndicator() {
        let indicator = document.getElementById('audioLevelIndicator');
        if (!indicator) {
            indicator = createAudioLevelIndicator();
        }

        const level = Math.min(audioLevel / 50 * 100, 100); // Normalize to 0-100%
        const bar = indicator.querySelector('.audio-level-bar');
        if (bar) {
            bar.style.width = level + '%';

            // Color coding based on level
            if (level > 60) {
                bar.className = 'audio-level-bar bg-success';
            } else if (level > 30) {
                bar.className = 'audio-level-bar bg-warning';
            } else {
                bar.className = 'audio-level-bar bg-danger';
            }
        }
    }

    function createAudioLevelIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'audioLevelIndicator';
        indicator.className = 'd-flex align-items-center';
        indicator.innerHTML = `
            <small class="me-2">Audio:</small>
            <div class="audio-level-container" style="width: 60px; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                <div class="audio-level-bar bg-secondary" style="height: 100%; width: 0%; transition: width 0.1s;"></div>
            </div>
        `;

        const enhancedContainer = document.getElementById('enhancedStatusContainer');
        if (enhancedContainer) {
            enhancedContainer.appendChild(indicator);
        } else {
            // Fallback to original location
            const handsFreeStatus = document.getElementById('handsFreeStatus');
            if (handsFreeStatus && handsFreeStatus.parentNode) {
                handsFreeStatus.parentNode.insertBefore(indicator, handsFreeStatus);
            }
        }

        return indicator;
    }

    function stopAudioLevelMonitoring() {
        if (audioContext) {
            audioContext.close();
            audioContext = null;
        }

        const indicator = document.getElementById('audioLevelIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function updateRecordingTimer() {
        if (!sessionStartTime) return;

        let timerElement = document.getElementById('recordingTimer');
        if (!timerElement) {
            timerElement = createRecordingTimer();
        }

        if (isListening && !isHandsFreePaused) {
            const elapsed = Math.floor((Date.now() - sessionStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            timerElement.querySelector('.badge').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    function createRecordingTimer() {
        const timer = document.createElement('div');
        timer.id = 'recordingTimer';
        timer.className = 'd-flex align-items-center';
        timer.innerHTML = '<small class="me-2">Time:</small><span class="badge bg-primary recording-timer">00:00</span>';

        const enhancedContainer = document.getElementById('enhancedStatusContainer');
        if (enhancedContainer) {
            enhancedContainer.appendChild(timer);
        } else {
            // Fallback to original location
            const audioIndicator = document.getElementById('audioLevelIndicator');
            if (audioIndicator && audioIndicator.parentNode) {
                audioIndicator.parentNode.insertBefore(timer, audioIndicator);
            }
        }

        return timer;
    }

    function startRecordingTimer() {
        sessionStartTime = Date.now();
        recordingTimer = setInterval(updateRecordingTimer, 1000);
    }

    function stopRecordingTimer() {
        if (recordingTimer) {
            clearInterval(recordingTimer);
            recordingTimer = null;
        }
        sessionStartTime = null;

        const timerElement = document.getElementById('recordingTimer');
        if (timerElement) {
            timerElement.remove();
        }
    }

    // Reset session
    function resetSession() {
        // Stop any ongoing recording first
        if (isListening) {
            stopSession();
        }

        // Clear all enhanced hands-free elements
        stopSilenceDetection();
        stopAudioLevelMonitoring();
        stopRecordingTimer();
        clearSessionState();

        sessionId = generateUUID();
        isListening = false;
        isHandsFreeMode = false;
        isHandsFreePaused = false;
        restartAttempts = 0;
        finalTranscript = '';
        transcriptBuffer = '';
        extractedData = {};
        aiAnalysis = '';
        aiResultId = null;
        isProcessing = false;

        // Reset UI fields
        if (transcriptionArea) transcriptionArea.value = '';
        if (symptomsField) symptomsField.value = '';
        if (medicalHistoryField) medicalHistoryField.value = '';
        if (physicalFindingsField) physicalFindingsField.value = '';
        if (medicationsField) medicationsField.value = '';
        if (vitalSignsField) vitalSignsField.value = '';
        if (diagnosisField) diagnosisField.value = '';
        if (carePlanField) carePlanField.value = '';
        if (aiAnalysisArea) aiAnalysisArea.innerHTML = '';
        if (handsFreeToggle) handsFreeToggle.checked = false;

        // Hide analysis sections
        const aiAnalysisSection = document.getElementById('aiAnalysisSection');
        if (aiAnalysisSection) aiAnalysisSection.style.display = 'none';

        const manualDiagnosisForm = document.getElementById('manualDiagnosisForm');
        if (manualDiagnosisForm) manualDiagnosisForm.style.display = 'none';

        // Clear manual diagnosis text
        const manualDiagnosisText = document.getElementById('manualDiagnosisText');
        if (manualDiagnosisText) manualDiagnosisText.value = '';

        updateRecordingUI();
        updateHandsFreeStatus();
        hideProgressIndicator();

        showAlert('Session reset successfully!', 'success');
    }

    // Update recording UI
    function updateRecordingUI() {
        // Update recording status display
        const recordingStatus = document.getElementById('recordingStatus');
        if (recordingStatus) {
            if (isListening) {
                recordingStatus.innerHTML = '<span class="badge bg-danger me-2"><i class="fas fa-circle fa-xs"></i></span><span class="text-danger fw-bold">Recording...</span>';
            } else {
                recordingStatus.innerHTML = '<span class="badge bg-secondary me-2"><i class="fas fa-circle fa-xs"></i></span><span class="text-muted">Not Recording</span>';
            }
        }

        // Update button states
        if (startRecordingBtn) {
            startRecordingBtn.disabled = !selectedPatient || isListening;
        }

        if (stopRecordingBtn) {
            stopRecordingBtn.disabled = !isListening;
        }

        if (generateAnalysisBtn) {
            const transcription = transcriptionArea ? transcriptionArea.value : '';
            generateAnalysisBtn.disabled = empty(transcription) || isProcessing;
        }
    }

    // Show progress indicator
    function showProgressIndicator() {
        if (jsProgressIndicator) {
            jsProgressIndicator.style.display = 'block';

            // Simulate progress stages
            const stages = [
                'Initializing AI analysis...',
                'Extracting medical data from transcription...',
                'Analyzing medical content with AI...',
                'Generating comprehensive medical analysis...',
                'Processing results...'
            ];

            let currentStage = 0;

            const updateStage = () => {
                if (currentStage < stages.length && jsProgressIndicator.style.display !== 'none') {
                    if (jsProcessingStage) {
                        jsProcessingStage.textContent = stages[currentStage];
                    }
                    currentStage++;
                    setTimeout(updateStage, 3000); // Update every 3 seconds
                }
            };

            setTimeout(updateStage, 1000); // Start after 1 second
        }
    }

    // Hide progress indicator
    function hideProgressIndicator() {
        if (jsProgressIndicator) {
            jsProgressIndicator.style.display = 'none';
        }
    }

    // Update processing stage
    function updateProcessingStage(stage) {
        if (jsProcessingStage) {
            jsProcessingStage.textContent = stage;
        }
    }

    // Show alert
    function showAlert(message, type = 'info') {
        // Create alert element
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const alertClass = type === 'error' ? 'alert-danger' :
                          type === 'success' ? 'alert-success' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.innerHTML = alertHtml;

        // Scroll to alert for errors and warnings
        if (type === 'error' || type === 'warning') {
            alertContainer.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }

    // Check if string is empty
    function empty(str) {
        return !str || str.trim().length === 0;
    }

    // Initialize the voice assistant when the page loads
    initVoiceAssistant();

    // Make some functions globally available for debugging and external access
    window.voiceAssistant = {
        startSession: startSession,
        stopSession: stopSession,
        resetSession: resetSession,
        setRecognitionLanguage: setRecognitionLanguage,
        syncPatientSelection: syncPatientSelection,
        updateRecordingUI: updateRecordingUI,
        getSelectedPatient: function() { return selectedPatient; },
        setSelectedPatient: function(patientId) {
            selectedPatient = patientId;
            updateRecordingUI();

        },
        getAiResultId: function() { return aiResultId; },
        getExtractedData: function() { return extractedData; },
        getCurrentLanguage: function() { return currentLanguage; },
        detectLanguage: detectLanguage,
        // Debug functions
        testArabicDetection: function() {
            console.log('🧪 Testing Arabic detection...');
            const testTexts = [
                'مرحبا كيف حالك',
                'Hello world',
                'مرحبا Hello مرحبا',
                'أشعر بألم في الصدر',
                'I have chest pain أشعر بألم في الصدر',
                'صباح الخير',
                'Good morning صباح الخير'
            ];
            testTexts.forEach(text => {
                const detected = detectLanguage(text);
                console.log(`Text: "${text}" -> Detected: ${detected}`);
            });
        },
        forceArabicMode: function() {
            console.log('🇸🇦 Forcing Arabic mode...');
            setRecognitionLanguage('ar');
        },
        forceEnglishMode: function() {
            console.log('🇺🇸 Forcing English mode...');
            setRecognitionLanguage('en');
        },
        // Test current language switching
        testLanguageSwitching: function() {
            console.log('🧪 Testing language switching...');
            console.log('Current language:', currentLanguage);
            console.log('Recognition object:', recognition ? 'Available' : 'Not available');
            console.log('Is listening:', isListening);

            // Test Arabic detection
            const arabicTest = detectLanguage('مرحبا كيف حالك');
            console.log('Arabic test result:', arabicTest);

            // Test English detection
            const englishTest = detectLanguage('Hello how are you');
            console.log('English test result:', englishTest);
        },
        // Web Speech API has fundamental limitations for real-time language switching
        // This is the best we can achieve with current browser APIs
        getLimitations: function() {
            console.log('⚠️ Web Speech API Limitations:');
            console.log('• Cannot detect spoken language before transcription');
            console.log('• Language switching requires stopping/starting recognition');
            console.log('• Only detects language from already-transcribed text');
            console.log('• Arabic speech transcribed as English when recognition is in English mode');
            console.log('• Best solution: Start with user\'s regional language (Arabic for Middle East)');
            console.log('• Alternative: Use server-side speech recognition with language detection');
        }
    };
});
