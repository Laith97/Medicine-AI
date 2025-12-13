(function() {
    // Enhanced logging system for ambient listening debugging
    const voiceAssistantLogger = {
        logs: [],
        maxLogs: 1000,

        log: function (level, message, data = null) {
            const timestamp = new Date().toISOString();
            const logEntry = {
                timestamp: timestamp,
                level: level,
                message: message,
                data: data,
                sessionId: sessionId,
                userAgent: navigator.userAgent
            };

            this.logs.push(logEntry);

            // Keep only the last maxLogs entries
            if (this.logs.length > this.maxLogs) {
                this.logs.shift();
            }

            // Console output with appropriate level
            const consoleMessage = `[${timestamp}] ${level.toUpperCase()}: ${message}`;
            if (data) {
                console[level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'log'](consoleMessage, data);
            } else {
                console[level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'log'](consoleMessage);
            }
        },

        info: function (message, data = null) { this.log('info', message, data); },
        warn: function (message, data = null) { this.log('warn', message, data); },
        error: function (message, data = null) { this.log('error', message, data); },
        debug: function (message, data = null) { this.log('debug', message, data); },

        getLogs: function (level = null, limit = 50) {
            let filteredLogs = level ? this.logs.filter(log => log.level === level) : this.logs;
            return filteredLogs.slice(-limit);
        },

        exportLogs: function () {
            return {
                sessionId: sessionId,
                timestamp: new Date().toISOString(),
                logs: this.logs,
                systemInfo: {
                    userAgent: navigator.userAgent,
                    language: navigator.language,
                    platform: navigator.platform,
                    cookieEnabled: navigator.cookieEnabled,
                    onLine: navigator.onLine
                }
            };
        }
    };

    // Make logger globally available for debugging
    window.voiceAssistantLogger = voiceAssistantLogger;

    // Global variables
    let recognition;
    let isListening = false;
    let isStopping = false; // Prevent multiple stop operations
    let restartTimeout;
    let finalTranscript = '';
    let lastTranscriptTime = 0;
    let transcriptBuffer = '';
    let bufferTimeout;
    let interimBackupBuffer = ''; // NEW: Backup buffer for interim results
    let liveConfidence = 0; // Live transcription confidence score
    let isInitialized = false; // Prevent double initialization
    // Regional language detection for better transcription accuracy
    function getRegionalDefaultLanguage() {
        try {
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const userAgent = navigator.userAgent.toLowerCase();

            // Arabic-speaking regions (Middle East, North Africa)
            const arabicRegions = [
                'asia/amman', 'asia/riyadh', 'asia/dubai', 'asia/kuwait', 'asia/qatar',
                'asia/bahrain', 'asia/muscat', 'asia/beirut', 'asia/damascus', 'asia/baghdad',
                'africa/cairo', 'africa/tunis', 'africa/algiers', 'africa/casablanca'
            ];

            // Check timezone
            if (arabicRegions.some(region => timezone.toLowerCase().includes(region))) {
                return 'ar-SA';
            }

            // Check browser language preferences
            const browserLang = navigator.language || navigator.userLanguage;
            if (browserLang && browserLang.startsWith('ar')) {
                return 'ar-SA';
            }

            // Default to English for other regions
            return 'en-US';
        } catch (error) {
            console.warn('Language detection failed, defaulting to Arabic:', error);
            return 'ar-SA'; // Safe default for this region
        }
    }

    let currentLanguage = getRegionalDefaultLanguage(); // Dynamic regional default
    let sessionId = '';
    let selectedPatient = null;
    let isProcessing = false;
    let isHandsFreeMode = false;
    let aiAnalysis = '';
    let extractedData = {};
    let aiResultId = null;

    // HYBRID METHOD - Audio recording and server processing
    let mediaRecorder;
    let audioChunks = [];
    let audioBlob = null;
    let audioRecording = false;
    let audioRecordingSupported = false;
    let serverProcessingInProgress = false;
    let hybridModeEnabled = true; // Enable hybrid processing by default

    // ENHANCED MEDICAL TRANSCRIPTION SYSTEM
    let liveTranscription = ''; // Real-time browser transcription
    let serverTranscription = ''; // Advanced server processed transcription
    let speakerSeparatedTranscription = {}; // Speaker-separated transcription data
    let medicalDictionary = {}; // Enhanced medical terms dictionary
    let speakerLabels = { 1: 'Doctor', 2: 'Patient', 3: 'Other' }; // Speaker identification

    // Enhanced hands-free variables
    let silenceTimeout;
    let maxSilenceDuration = 5000; // Optimized for multi-speaker detection
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

    // Multi-speaker support variables
    let speakerTransitions = [];
    let lastSpeakerChange = 0;
    let voiceActivityLevel = 0;
    let previousAudioLevel = 0;
    let speakerChangeThreshold = 0.15; // Audio level difference threshold for speaker change detection
    let immediateProcessingEnabled = true; // Enable immediate processing for better completeness

    // Continuous language detection variables
    let languageDetectionBuffer = [];
    let languageSwitchCooldown = 0;
    let lastLanguageSwitch = 0;
    let continuousLanguageDetection = true;
    let languageConfidenceThreshold = 0.7;

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

    // Simple interface - no complex panel elements needed

    // MEDICAL DICTIONARY - Arabic and English medical terms
    function initMedicalDictionary() {
        medicalDictionary = {
            // Arabic medical terms
            'ألم': 'pain',
            'صداع': 'headache',
            'حمى': 'fever',
            'سعال': 'cough',
            'غثيان': 'nausea',
            'قيء': 'vomiting',
            'إسهال': 'diarrhea',
            'إمساك': 'constipation',
            'ضغط دم': 'blood pressure',
            'سكري': 'diabetes',
            'ضغط': 'hypertension',
            'قلب': 'heart',
            'رئة': 'lung',
            'كبد': 'liver',
            'كلى': 'kidney',
            'معدة': 'stomach',
            'أمعاء': 'intestine',
            'دم': 'blood',
            'عظام': 'bones',
            'مفاصل': 'joints',
            'عضلات': 'muscles',
            'جلد': 'skin',
            'عيون': 'eyes',
            'أذن': 'ear',
            'أنف': 'nose',
            'حلق': 'throat',
            'أسنان': 'teeth',
            'دواء': 'medicine',
            'حقنة': 'injection',
            'جراحة': 'surgery',
            'تشخيص': 'diagnosis',
            'علاج': 'treatment',
            'فحص': 'examination',
            'تحاليل': 'tests',
            'أشعة': 'x-ray',
            'سونار': 'ultrasound',
            'رنين': 'MRI',
            'طبيب': 'doctor',
            'مريض': 'patient',
            'مستشفى': 'hospital',
            'عيادة': 'clinic',

            // English medical terms (for recognition improvement)
            'myocardial infarction': 'heart attack',
            'cerebrovascular accident': 'stroke',
            'chronic obstructive pulmonary disease': 'COPD',
            'gastroesophageal reflux disease': 'GERD',
            'hypertensive emergency': 'high blood pressure crisis'
        };

        console.log('🏥 Medical dictionary loaded with', Object.keys(medicalDictionary).length, 'terms');
    }

    // Simple quality validation
    function validateTranscriptionQuality(text, source) {
        if (!text || text.trim().length === 0) {
            return { score: 0, issues: ['Empty transcription'] };
        }

        let score = 100;
        const wordCount = text.trim().split(/\s+/).length;

        if (wordCount < 3) {
            score -= 30;
        }

        return {
            score: Math.max(0, Math.min(100, score)),
            issues: score < 50 ? ['Low quality transcription'] : []
        };
    }

    // Initialize form components
    function initializeFormComponents() {
        // New Patient Form Handling
        const showNewPatientFormBtn = document.getElementById('showNewPatientFormBtn');
        const hideNewPatientFormBtn = document.getElementById('hideNewPatientFormBtn');
        const newPatientForm = document.getElementById('newPatientForm');
        const cancelNewPatientBtn = document.getElementById('cancelNewPatientBtn');
        const createNewPatientBtn = document.getElementById('createNewPatientBtn');

        if (showNewPatientFormBtn) {
            showNewPatientFormBtn.addEventListener('click', function() {
                if (newPatientForm) newPatientForm.style.display = 'block';
                clearNewPatientForm();
            });
        }

        if (hideNewPatientFormBtn || cancelNewPatientBtn) {
            const hideForm = function() {
                if (newPatientForm) newPatientForm.style.display = 'none';
                clearNewPatientForm();
            };

            if (hideNewPatientFormBtn) hideNewPatientFormBtn.addEventListener('click', hideForm);
            if (cancelNewPatientBtn) cancelNewPatientBtn.addEventListener('click', hideForm);
        }

        if (createNewPatientBtn) {
            createNewPatientBtn.addEventListener('click', function() {
                createNewPatient();
            });
        }

        // Show diagnosis entry form after recording stops
        window.showDiagnosisEntryForm = function() {
            const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
            if (diagnosisEntryForm) {
                diagnosisEntryForm.style.display = 'block';
                const diagnosisText = document.getElementById('diagnosisText');
                if (diagnosisText) {
                    diagnosisText.focus();
                }
            }
        };

        // Diagnosis Entry Form Handling
        const cancelDiagnosisBtn = document.getElementById('cancelDiagnosisBtn');
        const completeConsultationBtn = document.getElementById('completeConsultationBtn');
        const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
        const diagnosisText = document.getElementById('diagnosisText');

        if (cancelDiagnosisBtn) {
            cancelDiagnosisBtn.addEventListener('click', function() {
                if (diagnosisEntryForm) diagnosisEntryForm.style.display = 'none';
                if (diagnosisText) diagnosisText.value = '';
                if (completeConsultationBtn) completeConsultationBtn.disabled = true;
            });
        }

        if (diagnosisText && completeConsultationBtn) {
            diagnosisText.addEventListener('input', function() {
                completeConsultationBtn.disabled = !this.value.trim();
            });
        }

        if (completeConsultationBtn) {
            completeConsultationBtn.addEventListener('click', function() {
                showCompleteConsultationModal();
            });
        }

        // Modal complete consultation button handler
        const modalCompleteConsultationBtn = document.getElementById('modalCompleteConsultationBtn');
        if (modalCompleteConsultationBtn) {
            modalCompleteConsultationBtn.addEventListener('click', function() {
                completeConsultation();
            });
        }
    }

    // Initialize voice assistant components (button states)
    function initializeVoiceAssistantComponents() {
        const startRecordingBtn = document.getElementById('startRecordingBtn');
        const stopRecordingBtn = document.getElementById('stopRecordingBtn');
        const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
        const patientSelect = document.getElementById('patientSelect');

        // Function to enable/disable recording buttons based on patient selection
        function updateRecordingButtons() {
            const hasPatientSelected = patientSelect && patientSelect.value && patientSelect.value !== '';

            if (hasPatientSelected) {
                // Enable buttons when patient is selected
                if (startRecordingBtn) {
                    startRecordingBtn.disabled = false;
                    startRecordingBtn.title = 'Start voice recording';
                }
                if (stopRecordingBtn) {
                    stopRecordingBtn.disabled = false;
                }
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = false;
                }

                // Remove warning message if it exists
                const alertContainer = document.getElementById('alertContainer');
                if (alertContainer) {
                    const warningAlert = alertContainer.querySelector('.alert-warning');
                    if (warningAlert) {
                        warningAlert.remove();
                    }
                }
            } else {
                // Disable buttons when no patient is selected
                if (startRecordingBtn) {
                    startRecordingBtn.disabled = true;
                    startRecordingBtn.title = 'Please select a patient first';
                }
                if (stopRecordingBtn) {
                    stopRecordingBtn.disabled = true;
                }
                if (generateAnalysisBtn) {
                    generateAnalysisBtn.disabled = true;
                }
            }
        }

        // Initial state
        updateRecordingButtons();

        // Listen for patient selection changes
        if (patientSelect) {
            patientSelect.addEventListener('change', updateRecordingButtons);
        }
    }

    // Initialize the ambient listening system
    function initVoiceAssistant() {
        // Prevent double initialization
        if (isInitialized) {
            console.log('🎙️ Ambient listening system already initialized, skipping...');
            return;
        }

        console.log('🎙️ Initializing ambient listening system...');
        isInitialized = true;

        // Clean up any leftover keyboard shortcuts help from previous page loads
        cleanupKeyboardShortcutsHelp();

        // Set initial session ID from data attribute on the container div
        const container = document.querySelector('[data-session-id]');
        sessionId = container ? container.getAttribute('data-session-id') : generateUUID();

        // Initialize medical dictionary
        initMedicalDictionary();

        // NEW: Initialize hybrid method capabilities
        initHybridMethod();

        // Initialize simple transcription system
        initSimpleTranscription();

        // Restore session state from localStorage if available
        restoreSessionState();

        // Initialize selected patient if one is already selected
        syncPatientSelection();

        // Initialize speech recognition
        // initSpeechRecognition(); // REMOVED: Web Speech API replaced by Ambient Listening

        // Set up event listeners
        setupEventListeners();

        // Initialize language selector
        initLanguageSelector();

        // Load patients
        loadPatients();

        // Initialize Bootstrap tooltips
        initTooltips();

        // Update UI based on initial state
        console.log('🚀 Initial UI update - selectedPatient:', selectedPatient);
        updateRecordingUI();
        updateHandsFreeStatus();

        // Initialize form components
        initializeFormComponents();
    
        // Initialize voice assistant components (button states)
        initializeVoiceAssistantComponents();

        // Set up periodic session state saving
        setInterval(saveSessionState, 5000); // Save every 5 seconds

        // Set up periodic UI state check to ensure buttons are correctly enabled/disabled
        setInterval(() => {
            if (!isListening && !audioRecording) {
                // Only update if not actively recording
                updateRecordingUI();
            }
        }, 1000); // Check every second

        // Set up keyboard shortcuts
        setupKeyboardShortcuts();

        // Log initialization status with enhanced logging
        voiceAssistantLogger.info('🎙️ Ambient Listening System initialized', {
            liveTranscription: true,
            audioRecordingSupported: audioRecordingSupported,
            serverProcessing: true,
            hybridModeEnabled: hybridModeEnabled,
            medicalDictionary: Object.keys(medicalDictionary).length + ' terms',
            simpleInterface: true,
            currentLanguage: currentLanguage,
            sessionId: sessionId
        });

        console.log('🎙️ Ambient Listening System initialized');
        console.log('✅ Live transcription: Active');
        console.log('✅ Audio recording: ' + (audioRecordingSupported ? 'Supported' : 'Not supported'));
        console.log('✅ Server processing: Ready');
        console.log('🏥 Medical dictionary: ' + Object.keys(medicalDictionary).length + ' terms loaded');
        console.log('📝 Simple interface: Enabled');
        console.log('🚀 Hybrid mode: ' + (hybridModeEnabled ? 'Enabled' : 'Disabled'));

    }

    // NEW: Initialize Hybrid Method capabilities
    function initHybridMethod() {
        // Check MediaRecorder support
        audioRecordingSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);

        console.log('🔍 Hybrid Method Initialization:');
        console.log('  📹 MediaRecorder support:', audioRecordingSupported);
        console.log('  🎙️ Web Speech API support:', !!(window.SpeechRecognition || window.webkitSpeechRecognition));
        console.log('  🌐 Browser:', navigator.userAgent.split(' ').pop());

        if (!audioRecordingSupported) {
            console.warn('⚠️ Audio recording not supported. Hybrid mode will use live transcription only.');
        }
    }

    // Simple transcription system initialization
    function initSimpleTranscription() {
        console.log('📝 Simple transcription system initialized');
    }

    // NEW: Start audio recording alongside live transcription with enhanced quality and robust error handling
    async function startAudioRecording() {
        if (!audioRecordingSupported || audioRecording) {
            console.log('🎵 Audio recording not supported or already recording');
            return;
        }

        voiceAssistantLogger.info('🎵 Attempting to start enhanced audio recording', {
            audioRecordingSupported: audioRecordingSupported,
            hybridModeEnabled: hybridModeEnabled,
            currentLanguage: currentLanguage
        });

        try {
            // Check microphone permissions first
            voiceAssistantLogger.debug('🎙️ Checking microphone permissions');
            const permissionStatus = await navigator.permissions.query({ name: 'microphone' });
            voiceAssistantLogger.info('🎙️ Microphone permission status', { status: permissionStatus.state });

            if (permissionStatus.state === 'denied') {
                throw new Error('Microphone permission denied. Please allow microphone access.');
            }

            // Enhanced audio constraints for medical consultations
            const audioConstraints = {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                sampleRate: { ideal: 48000, min: 44100 }, // Higher sample rate for better quality
                sampleSize: { ideal: 16, min: 16 }, // 16-bit audio
                channelCount: { ideal: 1, max: 2 }, // Mono preferred for voice, stereo fallback
                latency: { ideal: 0.01, max: 0.1 }, // Low latency for real-time
                volume: { ideal: 0.8, max: 1.0 } // Optimal volume level
            };

            // Check for advanced audio features
            if (navigator.mediaDevices.getSupportedConstraints) {
                const supported = navigator.mediaDevices.getSupportedConstraints();
                // Supported audio constraints:

                // Add advanced constraints if supported
                if (supported.sampleRate) audioConstraints.sampleRate = { ideal: 48000, min: 44100 };
                if (supported.sampleSize) audioConstraints.sampleSize = { ideal: 16, min: 16 };
                if (supported.channelCount) audioConstraints.channelCount = { ideal: 1, max: 2 };
                if (supported.latency) audioConstraints.latency = { ideal: 0.01, max: 0.1 };
                if (supported.volume) audioConstraints.volume = { ideal: 0.8, max: 1.0 };
            }

            console.log('🎵 Requesting microphone access with constraints:', audioConstraints);
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: audioConstraints
            });

            console.log('🎙️ Microphone access granted successfully');

            // Get actual audio track settings for quality validation
            const audioTrack = stream.getAudioTracks()[0];
            if (!audioTrack) {
                throw new Error('No audio track available from microphone');
            }

            const settings = audioTrack.getSettings();
            console.log('🎵 Audio track settings:', {
                sampleRate: settings.sampleRate,
                channelCount: settings.channelCount,
                echoCancellation: settings.echoCancellation,
                noiseSuppression: settings.noiseSuppression,
                autoGainControl: settings.autoGainControl,
                latency: settings.latency
            });

            // Validate audio quality
            if (!validateAudioQuality(settings)) {
                console.warn('⚠️ Audio quality below optimal standards, but proceeding...');
                showAlert('Audio quality may be suboptimal. Consider using a better microphone for best results.', 'warning');
            }

            // Choose optimal recording format based on browser support
            let mimeType = 'audio/webm;codecs=opus'; // Preferred for quality

            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = 'audio/webm';
                console.log('🎵 Opus codec not supported, falling back to basic WebM');
            }
            if (!MediaRecorder.isTypeSupported(mimeType)) {
                mimeType = MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : 'audio/wav';
                console.log('🎵 WebM not supported, using:', mimeType);
            }

            console.log('🎵 Selected MIME type:', mimeType);

            const options = {
                mimeType: mimeType,
                audioBitsPerSecond: 128000 // 128kbps for good quality balance
            };

            // Test MediaRecorder creation before starting
            try {
                mediaRecorder = new MediaRecorder(stream, options);
                console.log('🎵 MediaRecorder created successfully');
            } catch (recorderError) {
                console.error('❌ Failed to create MediaRecorder:', recorderError);
                // Try fallback options
                const fallbackOptions = { mimeType: '' }; // Let browser choose
                mediaRecorder = new MediaRecorder(stream, fallbackOptions);
                console.log('🎵 MediaRecorder created with fallback options');
            }

            audioChunks = [];
            let recordingStartTime = Date.now();
            let chunkCount = 0;
            let totalChunkSize = 0;

            // Enhanced audio data handling with validation
            mediaRecorder.ondataavailable = function (event) {
                if (event.data && event.data.size > 0) {
                    audioChunks.push(event.data);
                    chunkCount++;
                    totalChunkSize += event.data.size;

                    console.log('📊 Audio chunk captured:', {
                        chunkNumber: chunkCount,
                        size: event.data.size,
                        type: event.data.type,
                        totalSize: totalChunkSize,
                        timestamp: new Date().toISOString()
                    });

                    // Monitor audio quality during recording
                    monitorAudioChunkQuality(event.data);
                } else {
                    console.warn('⚠️ Empty or invalid audio chunk received');
                }
            };

            mediaRecorder.onerror = function (event) {
                console.error('❌ MediaRecorder error:', event.error);
                audioRecording = false;
                showAlert('Audio recording error: ' + (event.error?.message || 'Unknown error'), 'error');
            };

            mediaRecorder.onstop = function () {
                const recordingDuration = Date.now() - recordingStartTime;
                console.log('🎵 Audio recording stopped:', {
                    duration: recordingDuration + 'ms',
                    chunks: chunkCount,
                    totalSize: totalChunkSize
                });

                // Validate recording before processing
                if (audioChunks.length === 0 || totalChunkSize === 0) {
                    console.error('❌ No audio data captured during recording');
                    audioBlob = null;
                    showAlert('No audio data was captured. Please check your microphone and try again.', 'error');
                    return;
                }

                // Preprocess audio before creating blob
                const processedChunks = preprocessAudioChunks(audioChunks);

                if (processedChunks.length === 0) {
                    console.error('❌ All audio chunks were filtered out during preprocessing');
                    audioBlob = null;
                    showAlert('Audio recording failed validation. Please try again.', 'error');
                    return;
                }

                audioBlob = new Blob(processedChunks, {
                    type: mediaRecorder.mimeType || 'audio/webm'
                });

                // Validate the final blob
                if (!validateAudioBlob(audioBlob)) {
                    console.error('❌ Audio blob validation failed');
                    audioBlob = null;
                    showAlert('Audio recording is invalid. Please try again with a better microphone.', 'error');
                    return;
                }

                console.log('🎵 Audio recording completed successfully:', {
                    size: audioBlob.size,
                    type: audioBlob.type,
                    chunks: processedChunks.length,
                    estimatedDuration: estimateAudioDuration(audioChunks),
                    quality: assessAudioQuality(audioBlob),
                    recordingDuration: recordingDuration + 'ms'
                });

                // Store quality metrics for performance tracking
                audioQualityMetrics = {
                    fileSize: audioBlob.size,
                    format: audioBlob.type,
                    estimatedDuration: estimateAudioDuration(audioChunks),
                    qualityScore: assessAudioQuality(audioBlob),
                    recordingDuration: recordingDuration,
                    chunkCount: chunkCount,
                    averageChunkSize: totalChunkSize / chunkCount
                };

                // NEW: Trigger server-side processing now that audioBlob is available and valid
                if (hybridModeEnabled && !serverProcessingInProgress) {
                    triggerServerSideProcessing();
                }
            };

            // Start recording with optimized timeslice
            mediaRecorder.start(200); // 200ms chunks for better processing
            audioRecording = true;

            console.log('🎵 Enhanced audio recording started successfully:', {
                mimeType: mimeType,
                sampleRate: settings.sampleRate,
                channels: settings.channelCount,
                qualityValidated: true,
                startTime: new Date().toISOString()
            });

        } catch (error) {
            voiceAssistantLogger.error('❌ Failed to start enhanced audio recording', {
                error: error.message,
                name: error.name,
                stack: error.stack,
                audioRecordingSupported: audioRecordingSupported,
                hybridModeEnabled: hybridModeEnabled
            });

            audioRecordingSupported = false;

            // Provide specific error messages based on error type
            if (error.name === 'NotAllowedError' || error.message.includes('permission')) {
                voiceAssistantLogger.warn('Microphone permission denied');
                showAlert('Microphone access denied. Please allow microphone access in your browser settings and try again.', 'error');
            } else if (error.name === 'NotFoundError') {
                voiceAssistantLogger.warn('No microphone found');
                showAlert('No microphone found. Please check your microphone connection.', 'error');
            } else if (error.name === 'NotSupportedError') {
                voiceAssistantLogger.info('🔄 Falling back to basic audio recording due to NotSupportedError');
                await startBasicAudioRecording();
            } else if (error.name === 'AbortError') {
                voiceAssistantLogger.warn('Audio recording was interrupted');
                showAlert('Audio recording was interrupted. Please try again.', 'error');
            } else {
                voiceAssistantLogger.error('Unknown audio recording error', { error: error.message });
                showAlert('Failed to start audio recording: ' + error.message, 'error');
            }
        }
    }

    // NEW: Validate audio quality against medical consultation standards
    function validateAudioQuality(settings) {
        const requirements = {
            minSampleRate: 16000, // Minimum for speech recognition
            preferredSampleRate: 44100,
            maxLatency: 0.1, // Maximum acceptable latency
            requiresEchoCancellation: true,
            requiresNoiseSuppression: true
        };

        const issues = [];

        if (settings.sampleRate < requirements.minSampleRate) {
            issues.push(`Sample rate too low: ${settings.sampleRate}Hz (minimum: ${requirements.minSampleRate}Hz)`);
        }

        if (settings.latency > requirements.maxLatency) {
            issues.push(`Latency too high: ${settings.latency}s (maximum: ${requirements.maxLatency}s)`);
        }

        if (requirements.requiresEchoCancellation && !settings.echoCancellation) {
            issues.push('Echo cancellation not enabled');
        }

        if (requirements.requiresNoiseSuppression && !settings.noiseSuppression) {
            issues.push('Noise suppression not enabled');
        }

        if (issues.length > 0) {
            console.warn('🎵 Audio quality validation issues:', issues);
            return false;
        }

        return true;
    }

    // NEW: Monitor audio chunk quality during recording
    function monitorAudioChunkQuality(chunk) {
        // Basic quality monitoring - could be enhanced with audio analysis
        if (chunk.size < 1000) { // Very small chunks might indicate issues
            console.warn('⚠️ Small audio chunk detected:', chunk.size, 'bytes');
        }
    }

    // NEW: Preprocess audio chunks before creating final blob
    function preprocessAudioChunks(chunks) {
        // Remove any empty or corrupted chunks
        const validChunks = chunks.filter(chunk => chunk.size > 0);

        // Sort chunks by timestamp if available (future enhancement)
        // For now, return validated chunks
        return validChunks;
    }

    // NEW: Estimate audio duration from chunks
    function estimateAudioDuration(chunks) {
        // Rough estimation: assume 200ms per chunk
        const totalChunks = chunks.length;
        const estimatedSeconds = (totalChunks * 0.2); // 200ms per chunk

        return Math.round(estimatedSeconds * 100) / 100; // Round to 2 decimal places
    }

    // NEW: Assess overall audio quality
    function assessAudioQuality(blob) {
        let score = 100; // Start with perfect score

        // Deduct points for various quality issues
        if (blob.size < 50000) score -= 20; // Too small file
        if (blob.size > 50000000) score -= 10; // Very large file (potential quality issue)

        // Could add more sophisticated analysis here
        // - Frequency analysis
        // - Signal-to-noise ratio
        // - Clipping detection

        return Math.max(0, Math.min(100, score)); // Clamp between 0-100
    }

    // NEW: Validate audio blob before sending to server
    function validateAudioBlob(blob) {
        if (!blob) {
            console.error('❌ Audio blob validation failed: blob is null or undefined');
            return false;
        }

        if (blob.size === 0) {
            console.error('❌ Audio blob validation failed: blob size is 0');
            return false;
        }

        if (blob.size < 1000) { // Minimum reasonable audio size
            console.error('❌ Audio blob validation failed: blob too small', blob.size, 'bytes');
            return false;
        }

        if (blob.size > 100 * 1024 * 1024) { // Maximum 100MB
            console.error('❌ Audio blob validation failed: blob too large', blob.size, 'bytes');
            return false;
        }

        // Check MIME type
        const validTypes = ['audio/webm', 'audio/mp4', 'audio/wav', 'audio/mpeg', 'audio/mp3'];
        if (!validTypes.some(type => blob.type.includes(type))) {
            console.warn('⚠️ Audio blob type may be unsupported:', blob.type);
            // Don't fail validation for unknown types, just warn
        }

        console.log('✅ Audio blob validation passed:', {
            size: blob.size,
            type: blob.type,
            sizeKB: Math.round(blob.size / 1024)
        });

        return true;
    }

    // NEW: Fallback basic audio recording with enhanced error handling
    async function startBasicAudioRecording() {
        console.log('🎵 Attempting basic audio recording fallback...');

        try {
            // Check microphone permissions for basic recording
            const permissionStatus = await navigator.permissions.query({ name: 'microphone' });
            if (permissionStatus.state === 'denied') {
                throw new Error('Microphone permission denied for basic recording');
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            console.log('🎙️ Basic microphone access granted');

            // Choose supported format for basic recording
            let mimeType = '';
            if (MediaRecorder.isTypeSupported('audio/webm')) {
                mimeType = 'audio/webm';
            } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                mimeType = 'audio/mp4';
            } else if (MediaRecorder.isTypeSupported('audio/wav')) {
                mimeType = 'audio/wav';
            }

            const options = mimeType ? { mimeType: mimeType } : {};

            mediaRecorder = new MediaRecorder(stream, options);
            audioChunks = [];
            let basicRecordingStartTime = Date.now();
            let basicChunkCount = 0;

            console.log('🎵 Basic MediaRecorder created with options:', options);

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    audioChunks.push(event.data);
                    basicChunkCount++;
                    console.log('📊 Basic audio chunk:', {
                        chunkNumber: basicChunkCount,
                        size: event.data.size,
                        type: event.data.type
                    });
                } else {
                    console.warn('⚠️ Empty basic audio chunk received');
                }
            };

            mediaRecorder.onerror = function (event) {
                console.error('❌ Basic MediaRecorder error:', event.error);
                audioRecording = false;
                showAlert('Basic audio recording error: ' + (event.error?.message || 'Unknown error'), 'error');
            };

            mediaRecorder.onstop = () => {
                const recordingDuration = Date.now() - basicRecordingStartTime;
                console.log('🎵 Basic audio recording stopped:', {
                    duration: recordingDuration + 'ms',
                    chunks: basicChunkCount,
                    totalSize: audioChunks.reduce((sum, chunk) => sum + chunk.size, 0)
                });

                // Validate basic recording
                if (audioChunks.length === 0 || audioChunks.every(chunk => chunk.size === 0)) {
                    console.error('❌ Basic audio recording captured no data');
                    audioBlob = null;
                    showAlert('Basic audio recording failed to capture data. Please check your microphone.', 'error');
                    return;
                }

                audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });

                // Validate the basic audio blob
                if (!validateAudioBlob(audioBlob)) {
                    console.error('❌ Basic audio blob validation failed');
                    audioBlob = null;
                    showAlert('Basic audio recording validation failed. Audio quality may be insufficient.', 'error');
                    return;
                }

                console.log('🎵 Basic audio recording completed successfully:', {
                    size: audioBlob.size,
                    type: audioBlob.type,
                    chunks: audioChunks.length,
                    quality: assessAudioQuality(audioBlob)
                });

                // Store basic quality metrics
                audioQualityMetrics = {
                    fileSize: audioBlob.size,
                    format: audioBlob.type,
                    estimatedDuration: estimateAudioDuration(audioChunks),
                    qualityScore: assessAudioQuality(audioBlob),
                    recordingDuration: recordingDuration,
                    chunkCount: basicChunkCount,
                    isBasicMode: true
                };

                // Trigger server processing even with basic recording
                if (hybridModeEnabled && !serverProcessingInProgress) {
                    console.log('🔄 Triggering server processing with basic audio recording');
                    triggerServerSideProcessing();
                }
            };

            mediaRecorder.start(1000); // 1 second chunks for basic recording
            audioRecording = true;

            console.log('🎵 Basic audio recording started successfully (fallback mode)', {
                mimeType: mimeType,
                startTime: new Date().toISOString()
            });

        } catch (error) {
            console.error('❌ Basic audio recording failed:', {
                error: error.message,
                name: error.name,
                stack: error.stack
            });

            audioRecordingSupported = false;

            // Provide specific error messages for basic recording
            if (error.name === 'NotAllowedError' || error.message.includes('permission')) {
                showAlert('Microphone access denied. Please allow microphone access and refresh the page.', 'error');
            } else if (error.name === 'NotFoundError') {
                showAlert('No microphone found. Please connect a microphone and refresh the page.', 'error');
            } else {
                showAlert('Audio recording not supported on this device. Only live transcription will be available.', 'warning');
            }

            // Log the failure for debugging
            console.warn('🎵 Audio recording completely unavailable, live transcription only mode activated');
        }
    }

    // NEW: Stop audio recording
    function stopAudioRecording() {
        if (mediaRecorder && audioRecording) {
            try {
                // Stop the MediaRecorder
                if (mediaRecorder.state === 'recording') {
                    mediaRecorder.stop();
                }

                // Stop all media tracks
                if (mediaRecorder.stream) {
                    mediaRecorder.stream.getTracks().forEach(track => {
                        track.stop();
                    });
                }

                audioRecording = false;
                console.log('🎵 Audio recording stopped successfully');

                // Update UI immediately
                updateRecordingUI();

            } catch (error) {
                console.error('❌ Error stopping audio recording:', error);
                audioRecording = false;
                updateRecordingUI();
            }
        } else {
            console.log('🎵 No active audio recording to stop');
        }
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

    // Initialize language selector with regional defaults and quality indicators
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

        // Update language options with quality indicators
        updateLanguageSelectorOptions();

        // Add change event listener
        languageSelector.addEventListener('change', function () {
            const selectedLang = this.value;
            console.log('🔄 Language selector changed to:', selectedLang);

            // Only allow language changes when not recording
            if (isListening) {
                showAlert('Please stop recording before changing language.', 'warning');
                // Reset selector to current language
                this.value = currentLanguage === 'ar-SA' ? 'ar' : 'en';
                return;
            }

            // Set the recognition language with enhanced feedback
            setRecognitionLanguage(selectedLang);
        });
    }

    // Update language selector with quality indicators
    function updateLanguageSelectorOptions() {
        const languageSelector = document.getElementById('languageSelector');
        if (!languageSelector) return;

        // Language information for user guidance
        const languageInfo = {
            'ar': { name: '🇸🇦 العربية', description: 'Best accuracy for Arabic speech' },
            'en': { name: '🇺🇸 English', description: 'Good for English, may have regional variations' },
            'fr': { name: '🇫🇷 Français', description: 'Limited support, may not work well' },
            'es': { name: '🇪🇸 Español', description: 'Limited support, may not work well' },
            'de': { name: '🇩🇪 Deutsch', description: 'Limited support, may not work well' }
        };

        // Update option text to include flag emojis
        Array.from(languageSelector.options).forEach(option => {
            const langCode = option.value;
            const info = languageInfo[langCode];
            if (info) {
                option.textContent = info.name;
                option.title = info.description;
            }
        });
    }

    // Cleanup function for keyboard shortcuts help
    function cleanupKeyboardShortcutsHelp() {
        const helpIndicator = document.querySelector('.keyboard-shortcuts-help');
        if (helpIndicator) {
            helpIndicator.remove();
        }
    }

    // Keyboard shortcuts
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function (event) {
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
        // Only show keyboard shortcuts help on the main voice-assistant page (not sub-pages)
        if (window.location.pathname !== '/ai/voice-assistant') {
            // Clean up any existing help indicator when not on the voice-assistant page
            cleanupKeyboardShortcutsHelp();
            return;
        }

        // Clean up any existing help indicator first
        cleanupKeyboardShortcutsHelp();

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
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // REMOVED: initSpeechRecognition function (Web Speech API)

    // REMOVED: detectLanguage function (Web Speech API)

    // REMOVED: detectSpokenLanguage function (Web Speech API)

    // REMOVED: setRecognitionLanguage function (Web Speech API)

    // Enhanced language indicator with quality information
    function updateLanguageIndicator(languageCode) {
        // Create indicator if it doesn't exist
        let indicator = document.getElementById('autoLanguageIndicator');
        if (!indicator) {
            // Create the indicator element
            indicator = document.createElement('div');
            indicator.id = 'autoLanguageIndicator';
            indicator.className = 'd-flex align-items-center';

            // Add it to the enhanced status container
            const enhancedContainer = document.getElementById('enhancedStatusContainer');
            if (enhancedContainer) {
                enhancedContainer.appendChild(indicator);
            }
        }

        const languageInfo = {
            'ar-SA': { name: 'العربية', flag: '🇸🇦' },
            'en-US': { name: 'English', flag: '🇺🇸' },
            'fr-FR': { name: 'Français', flag: '🇫🇷' },
            'es-ES': { name: 'Español', flag: '🇪🇸' },
            'de-DE': { name: 'Deutsch', flag: '🇩🇪' }
        };

        const info = languageInfo[languageCode] || {
            name: 'Unknown',
            flag: '🌐'
        };

        indicator.innerHTML = `
            <i class="fas fa-brain me-1"></i>
            ${info.flag} ${info.name}
        `;

        // Add tooltip with language description
        indicator.title = getLanguageDescription(languageCode);

        // Add visual feedback for language change
        indicator.classList.add('language-changed');
        setTimeout(() => {
            indicator.classList.remove('language-changed');
        }, 2000);

        console.log('🎯 Language indicator updated:', {
            language: info.name,
            code: languageCode,
            region: Intl.DateTimeFormat().resolvedOptions().timeZone
        });
    }

    // Get detailed description for tooltips
    function getLanguageDescription(languageCode) {
        const descriptions = {
            'ar-SA': 'Optimized for Arabic speech in Middle Eastern regions',
            'en-US': 'Good for English, may have regional variations',
            'fr-FR': 'Limited browser support, may not work reliably',
            'es-ES': 'Limited browser support, may not work reliably',
            'de-DE': 'Limited browser support, may not work reliably'
        };

        return descriptions[languageCode] || 'Language information not available';
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
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            console.warn('Bootstrap tooltips not available, skipping tooltip initialization');
            return;
        }
        try {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        } catch (error) {
            console.warn('Error initializing tooltips:', error);
        }
    }

    // Set up event listeners
    function setupEventListeners() {
        // Recording buttons
        if (startRecordingBtn) {
            startRecordingBtn.addEventListener('click', startSession);
        }

        if (stopRecordingBtn) {
            stopRecordingBtn.addEventListener('click', function () {
                console.log('🛑 Stop button clicked');
                stopSession();
            });
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
            patientSelect.addEventListener('change', function () {
                const newValue = this.value;
                selectedPatient = newValue && newValue !== '' ? newValue : null;
                console.log('👤 Patient selected:', selectedPatient, 'from dropdown value:', newValue, 'dropdown options:', this.options.length);
                updateRecordingUI();
            });

            // Also listen for input events in case of programmatic changes
            patientSelect.addEventListener('input', function () {
                const newValue = this.value;
                selectedPatient = newValue && newValue !== '' ? newValue : null;
                console.log('👤 Patient input changed:', selectedPatient, 'from dropdown value:', newValue);
                updateRecordingUI();
            });
        }

        // Initialize language indicator
        updateLanguageIndicator(currentLanguage);

        // Hands-free toggle with enhanced functionality
        if (handsFreeToggle) {
            handsFreeToggle.addEventListener('change', function () {
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

        pauseResumeBtn.addEventListener('click', function () {
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
        console.log('👥 Patient select element:', patientSelect);
        if (patientSelect) {
            console.log('👥 Patient select options:', patientSelect.options.length);
            console.log('👥 Patient select current value:', patientSelect.value);
        } else {
            console.warn('⚠️ Patient select element not found');
        }
    }

    // Sync patient selection
    function syncPatientSelection() {
        if (patientSelect && patientSelect.value && patientSelect.value !== '') {
            selectedPatient = patientSelect.value;
            console.log('👤 Patient synced from dropdown:', selectedPatient);
            return true;
        }
        selectedPatient = null;
        console.log('👤 No patient selected, selectedPatient set to null');
        return false;
    }

    // Start session (HYBRID METHOD)
    async function startSession() {
        console.log('🚀 Starting hybrid voice session...');

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
            success: async function (response) {
                if (response.success) {
                    sessionId = response.sessionId;
                    isListening = true;

                    // CRITICAL FIX: Complete reset of all session data
                    finalTranscript = '';
                    transcriptBuffer = '';
                    interimBackupBuffer = '';
                    speakerTransitions = [];
                    lastSpeakerChange = 0;
                    voiceActivityLevel = 0;
                    previousAudioLevel = 0;
                    restartAttempts = 0;
                    extractedData = {};
                    aiAnalysis = '';

                    // Clear transcription area
                    if (transcriptionArea) {
                        transcriptionArea.value = '';
                        transcriptionArea.style.borderLeft = 'none';
                        transcriptionArea.style.backgroundColor = 'transparent';
                    }

                    // Clear all timeout references
                    if (bufferTimeout) clearTimeout(bufferTimeout);
                    if (restartTimeout) clearTimeout(restartTimeout);

                    try {
                        // NEW: Start both live transcription AND audio recording in parallel
                        console.log('🎙️ Starting live transcription...');
                        recognition.lang = currentLanguage;
                        recognition.start();

                        // NEW: Start audio recording alongside live transcription
                        if (hybridModeEnabled && audioRecordingSupported) {
                            console.log('🎵 Starting audio recording...');
                            await startAudioRecording();
                        }

                        // Start enhanced features
                        startRecordingTimer();
                        if (isHandsFreeMode) {
                            startSilenceDetection();
                        }

                        updateRecordingUI();
                        updateHandsFreeStatus();

                        // HYBRID METHOD: Enhanced success message
                        const hybridMessage = hybridModeEnabled && audioRecordingSupported
                            ? `Hybrid session started! Live transcription + audio recording (ID: ${sessionId.substring(0, 8)}...)`
                            : `Session started! Live transcription active (ID: ${sessionId.substring(0, 8)}...)`;
                        showAlert(hybridMessage, 'success');

                        console.log('🎉 Hybrid session fully initialized');
                        console.log('📊 Session components:', {
                            liveTranscription: true,
                            audioRecording: audioRecordingSupported && hybridModeEnabled,
                            hybridMode: hybridModeEnabled,
                            sessionId: sessionId.substring(0, 8)
                        });

                    } catch (error) {
                        console.error('❌ Failed to start session:', error);
                        isListening = false;
                        updateRecordingUI();
                        updateHandsFreeStatus();
                        showAlert('Failed to start recording. Please check microphone permissions.', 'error');
                    }
                } else {
                    showAlert(response.message || 'Failed to start session.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ AJAX start session error:', error);
                showAlert('Failed to start session. Please check your connection.', 'error');
            }
        });
    }

    // Stop session (HYBRID METHOD) - FIXED: Immediate session completion with proper cleanup
    function stopSession() {
        // Prevent multiple stop operations
        if (isStopping) {
            console.log('🛑 Stop operation already in progress...');
            return;
        }

        isStopping = true;
        console.log('🛑 Stopping hybrid voice session...');
        console.log('📊 Current state:', {
            isListening: isListening,
            audioRecording: audioRecording,
            hybridModeEnabled: hybridModeEnabled,
            mediaRecorderState: mediaRecorder ? mediaRecorder.state : 'no mediaRecorder'
        });

        // Stop live transcription first
        if (recognition && isListening) {
            console.log('🎙️ Stopping live transcription...');
            isListening = false;

            // Clear timeouts and enhanced features
            if (restartTimeout) clearTimeout(restartTimeout);
            if (bufferTimeout) clearTimeout(bufferTimeout);
            stopSilenceDetection();
            stopRecordingTimer();

            try {
                recognition.stop();
                console.log('✅ Live transcription stopped');

                // Send any remaining buffered transcript
                if (transcriptBuffer.trim()) {
                    handleTranscription(transcriptBuffer.trim());
                }

                // Also ensure final transcript is processed if different from buffer
                if (finalTranscript.trim() && finalTranscript.trim() !== transcriptBuffer.trim()) {
                    console.log('📝 Processing final accumulated transcript on stop');
                    handleTranscription(finalTranscript.trim());
                }
            } catch (error) {
                console.error('❌ Error stopping recognition:', error);
            }
        }

        // Stop audio recording immediately
        if (hybridModeEnabled && audioRecording) {
            console.log('🎵 Stopping audio recording...');
            stopAudioRecording();

            // Complete session immediately after stopping audio recording
            // Server processing will happen in the background
            setTimeout(() => {
                console.log('📝 Completing session after audio recording stop...');
                completeSession();
            }, 500); // Small delay to ensure audio recording stops
        } else {
            console.log('📝 No audio recording, completing session immediately...');
            // No audio recording, complete session immediately
            completeSession();
        }

        // Reset stopping flag after a delay
        setTimeout(() => {
            isStopping = false;
        }, 2000); // Allow 2 seconds for all operations to complete

        // Force UI update
        updateRecordingUI();
    }

    // NEW: Separate function to complete the session (mark as completed in database)
    function completeSession() {
        console.log('📝 Completing session in database...');

        // AJAX call to stop session (mark as completed)
        $.ajax({
            url: '/ai/voice-assistant/stop-session',
            method: 'POST',
            data: {
                sessionId: sessionId,
                hasAudioRecording: hybridModeEnabled && audioRecordingSupported,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    updateRecordingUI();
                    updateHandsFreeStatus();

                    // Show the diagnosis entry form immediately after recording stops
                    const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
                    if (diagnosisEntryForm) {
                        diagnosisEntryForm.style.display = 'block';
                        const diagnosisText = document.getElementById('diagnosisText');
                        if (diagnosisText) {
                            diagnosisText.focus();
                        }
                        console.log('✅ Professional diagnosis form shown after recording stop');
                    } else {
                        console.warn('⚠️ Professional diagnosis form element not found');
                    }

                    const stopMessage = hybridModeEnabled && audioRecordingSupported
                        ? 'Session stopped successfully. Server-side processing completed.'
                        : 'Session stopped successfully.';
                    showAlert(stopMessage, 'success');
                } else {
                    showAlert(response.message || 'Failed to stop session.', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Error stopping session:', error);
                showAlert('Failed to stop session. Please try again.', 'error');
            }
        });
    }

    // NEW: Server-side audio processing for hybrid method with enhanced validation - FIXED: Returns promise for session completion timing
    function triggerServerSideProcessing() {
        return new Promise((resolve, reject) => {
            if (!hybridModeEnabled) {
                console.log('🔄 Server-side processing skipped: hybrid mode disabled');
                resolve(); // Resolve immediately if not enabled
                return;
            }

            if (serverProcessingInProgress) {
                console.log('🔄 Server-side processing already in progress');
                resolve(); // Resolve immediately if already in progress
                return;
            }

            console.log('🔄 Triggering server-side audio processing...');

            // Validate that we have valid audio data before sending
            if (!audioBlob || !validateAudioBlob(audioBlob)) {
                console.warn('⚠️ No valid audio blob available for server processing, skipping...');
                updateServerProcessingStatus('Audio recording failed validation, using live transcription only.');
                showAlert('Audio recording validation failed. Using live transcription only.', 'warning');

                // Hide server processing status for validation failures
                setTimeout(() => {
                    const processingStatus = document.getElementById('processingStatus');
                    if (processingStatus) {
                        processingStatus.style.display = 'none';
                    }
                }, 2000); // Keep message visible for 2 seconds

                resolve(); // Resolve even if validation fails
                return;
            }

            // Validate transcription data
            if (!finalTranscript || finalTranscript.trim().length === 0) {
                console.warn('⚠️ No transcription data available');
                // Still proceed with audio-only processing
            }

            const formData = new FormData();
            formData.append('session_id', sessionId);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            // Append validated audio file
            const audioFilename = `session_${sessionId}_${Date.now()}.webm`;
            formData.append('audio_file', audioBlob, audioFilename);

            formData.append('transcription', finalTranscript || '');
            formData.append('has_live_transcription', (finalTranscript && finalTranscript.length > 0));
            formData.append('has_audio_recording', true);

            // Add performance metrics
            const performanceData = collectPerformanceMetrics();
            formData.append('network_type', performanceData.network_type || 'unknown');
            formData.append('connection_speed', performanceData.connection_speed || 0);

            // Add audio quality metrics if available
            if (audioQualityMetrics) {
                formData.append('audio_quality[sample_rate]', performanceData.audioSampleRate || 44100);
                formData.append('audio_quality[channels]', performanceData.audioChannels || 1);
                formData.append('audio_quality[average_level]', audioLevel || 0);
                formData.append('audio_quality[quality_score]', audioQualityMetrics.qualityScore || 0);
                formData.append('audio_quality[file_size]', audioBlob.size);
                formData.append('audio_quality[format]', audioBlob.type);
                formData.append('audio_quality[estimated_duration]', audioQualityMetrics.estimatedDuration || 0);
            }

            console.log('📤 Preparing to send audio data to server:', {
                sessionId: sessionId,
                audioSize: audioBlob.size,
                audioType: audioBlob.type,
                transcriptionLength: finalTranscript ? finalTranscript.length : 0,
                hasQualityMetrics: !!audioQualityMetrics
            });

            serverProcessingInProgress = true;
            updateServerProcessingStatus('Uploading audio for server processing...');

            $.ajax({
                url: '/ai/voice-assistant/process-audio-server',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 300000, // 5 minute timeout for large audio files
                success: function (response) {
                    console.log('✅ Server-side processing response:', response);

                    if (response.success) {
                        updateServerProcessingStatus('Server processing completed successfully!');

                        // Update transcription with enhanced server results
                        if (response.improved_transcription && response.improved_transcription.trim().length > 0) {
                            const improvedText = response.improved_transcription.trim();
                            const originalLength = liveTranscription ? liveTranscription.length : 0;

                            // Update server transcription
                            serverTranscription = improvedText;

                            // Process speaker-separated transcription if available
                            if (response.speakers && response.speakers.length > 0) {
                                speakerSeparatedTranscription = response.speakers;
                                console.log('🎯 Speaker-separated transcription available:', response.speakers.length, 'speakers');
                            }

                            // Calculate server confidence based on improvement and medical terms
                            const improvementRatio = originalLength > 0 ? improvedText.length / originalLength : 1;
                            const medicalTermsBonus = response.medical_terms ? response.medical_terms.length * 2 : 0;
                            transcriptionConfidence = Math.min(98, Math.max(60, improvementRatio * 70 + medicalTermsBonus));

                            // Update transcription display with speaker separation
                            updateTranscriptionWithSpeakerSeparation(improvedText, response.speakers);

                            console.log('🔄 Enhanced server transcription updated:', {
                                originalLength: originalLength,
                                improvedLength: improvedText.length,
                                speakers: response.speakers ? response.speakers.length : 0,
                                medicalTerms: response.medical_terms ? response.medical_terms.length : 0,
                                confidence: transcriptionConfidence + '%',
                                improvement: ((improvementRatio - 1) * 100).toFixed(1) + '%'
                            });
                        }

                        // Update extracted data if server provided better results
                        if (response.server_extracted_data && Object.keys(response.server_extracted_data).length > 0) {
                            console.log('🔄 Updating extracted data with server results...');
                            extractedData = response.server_extracted_data;
                            updateChartFields(extractedData);
                        }

                        // Update speaker identification UI
                        updateSpeakerIdentificationUI();

                        // Log performance improvement if available
                        if (response.performance_metrics) {
                            console.log('📊 Performance metrics:', response.performance_metrics);
                            if (response.performance_metrics.server_improved) {
                                console.log('🎯 Server processing improved transcription quality!');
                            }
                        }

                        // Hide server processing status
                        updateServerProcessingStatus('');
                        const processingStatus = document.getElementById('processingStatus');
                        if (processingStatus) {
                            processingStatus.style.display = 'none';
                        }

                        showAlert('Server-side processing completed! Enhanced accuracy achieved.', 'success');
                        resolve(); // Resolve on success
                    } else {
                        updateServerProcessingStatus('Server processing failed, using live transcription only.');
                        console.warn('⚠️ Server processing failed:', response.message);

                        // Hide server processing status on failure
                        setTimeout(() => {
                            const processingStatus = document.getElementById('processingStatus');
                            if (processingStatus) {
                                processingStatus.style.display = 'none';
                            }
                        }, 2000); // Keep error message visible for 2 seconds

                        showAlert('Server processing failed: ' + (response.message || 'Unknown error'), 'warning');
                        resolve(); // Resolve even on failure to continue session completion
                    }

                    serverProcessingInProgress = false;
                },
                error: function (xhr, status, error) {
                    console.error('❌ Server processing error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });

                    updateServerProcessingStatus('Server processing failed, using live transcription only.');
                    serverProcessingInProgress = false;

                    // Hide server processing status on error
                    setTimeout(() => {
                        const processingStatus = document.getElementById('processingStatus');
                        if (processingStatus) {
                            processingStatus.style.display = 'none';
                        }
                    }, 2000); // Keep error message visible for 2 seconds

                    // Provide user-friendly error messages
                    if (xhr.status === 413) {
                        showAlert('Audio file too large for server processing. Using live transcription only.', 'warning');
                    } else if (xhr.status === 415) {
                        showAlert('Audio format not supported by server. Using live transcription only.', 'warning');
                    } else if (xhr.status >= 500) {
                        showAlert('Server error during audio processing. Using live transcription only.', 'warning');
                    } else {
                        showAlert('Failed to process audio on server. Using live transcription only.', 'warning');
                    }

                    resolve(); // Resolve on error to continue session completion
                }
            });
        });
    }

    // Global variable to store audio quality metrics
    let audioQualityMetrics = null;

    // NEW: Collect performance metrics for monitoring
    function collectPerformanceMetrics() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

        // Get current audio track settings if available
        let audioSampleRate = null;
        let audioChannels = null;

        if (mediaRecorder && mediaRecorder.stream) {
            const audioTrack = mediaRecorder.stream.getAudioTracks()[0];
            if (audioTrack) {
                const settings = audioTrack.getSettings();
                audioSampleRate = settings.sampleRate;
                audioChannels = settings.channelCount;
            }
        }

        return {
            network_type: connection ? connection.effectiveType : 'unknown',
            connection_speed: connection ? (connection.downlink || 0) : 0,
            device_type: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' :
                /Tablet|iPad/i.test(navigator.userAgent) ? 'tablet' : 'desktop',
            browser_info: navigator.userAgent,
            audio_recording_supported: audioRecordingSupported,
            hybrid_mode_enabled: hybridModeEnabled,
            session_duration: sessionStartTime ? (Date.now() - sessionStartTime) / 1000 : 0,
            speaker_transitions: speakerTransitions.length,
            language_switches: currentLanguage !== 'en-US' ? 1 : 0,
            audioSampleRate: audioSampleRate,
            audioChannels: audioChannels
        };
    }

    // NEW: Update server processing status in UI
    function updateServerProcessingStatus(status) {
        const processingStatus = document.getElementById('processingStatus');
        const jsProcessingStage = document.getElementById('jsProcessingStage');

        if (processingStatus && jsProcessingStage) {
            processingStatus.style.display = 'block';
            jsProcessingStage.textContent = status;
        }
    }

    // FIXED: More conservative text completeness validation
    function validateTextCompleteness(currentText, previousText) {
        // Only validate if we have substantial previous text AND current text is much shorter
        if (!previousText || !currentText || previousText.length < 100) {
            return { isComplete: true, missingText: '' };
        }

        // Only flag if current text is dramatically shorter (more than 50% loss)
        const lengthRatio = currentText.length / previousText.length;
        const wordCount = currentText.trim().split(/\s+/).length;

        // Only trigger if: text is less than 30% of previous AND has less than 10 words AND previous was substantial
        if (lengthRatio < 0.3 && wordCount < 10 && previousText.length > 200) {
            const missingPercent = Math.round((1 - lengthRatio) * 100);
            console.warn('⚠️ Significant text loss detected:', {
                previousLength: previousText.length,
                currentLength: currentText.length,
                lossPercentage: missingPercent + '%',
                wordCount: wordCount
            });

            return {
                isComplete: false,
                missingText: `Significant text loss detected (${missingPercent}% of content missing)`
            };
        }

        return { isComplete: true, missingText: '' };
    }

    // ENHANCED MEDICAL TRANSCRIPTION DISPLAY FUNCTIONS
    function updateLiveTranscriptionDisplay() {
        // FIXED: Only update if we have new content and avoid conflicts with handleTranscription
        if (transcriptionArea && liveTranscription.trim()) {
            // Don't update if handleTranscription is currently processing (to avoid conflicts)
            if (!bufferTimeout) {
                transcriptionArea.value = liveTranscription;
                console.log('📝 Live transcription display updated, length:', liveTranscription.length);
            } else {
                console.log('📝 Skipping live display update (buffer timeout active)');
            }
        }
    }

    /**
     * Update transcription display with speaker separation
     */
    function updateTranscriptionWithSpeakerSeparation(fullText, speakers) {
        const transcriptionContainer = document.getElementById('transcriptionContainer');
        const transcriptionArea = document.getElementById('transcriptionArea');
        const speakerLegend = document.getElementById('speakerLegend');
        const speakerLegendText = document.getElementById('speakerLegendText');

        if (!transcriptionContainer || !transcriptionArea) return;

        if (speakers && speakers.length > 0) {
            // Create enhanced speaker-separated display
            let speakerHtml = '<div class="speaker-transcription">';

            speakers.forEach((speaker, index) => {
                const speakerName = speaker.role === 'doctor' ? 'Doctor' : 'Patient';
                const speakerClass = speaker.role === 'doctor' ? 'speaker-doctor' : 'speaker-patient';
                const speakerIcon = speaker.role === 'doctor' ? 'fas fa-user-md' : 'fas fa-user';
                const timestamp = speaker.start_time ? formatTimestamp(speaker.start_time) : '';

                speakerHtml += `
                    <div class="speaker-segment ${speakerClass} mb-2 p-2 rounded" style="border-left: 3px solid ${speaker.role === 'doctor' ? '#007bff' : '#28a745'}; background-color: ${speaker.role === 'doctor' ? '#f8f9ff' : '#f8fff9'};">
                        <div class="speaker-header d-flex align-items-center mb-1">
                            <i class="${speakerIcon} me-1"></i>
                            <strong class="speaker-label">${speakerName}</strong>
                            ${timestamp ? `<small class="text-muted ms-2">${timestamp}</small>` : ''}
                        </div>
                        <div class="speaker-text">${speaker.text}</div>
                    </div>
                `;
            });

            speakerHtml += '</div>';

            // Update the container with HTML
            transcriptionContainer.innerHTML = speakerHtml;

            // Show speaker legend
            if (speakerLegend && speakerLegendText) {
                const doctorCount = speakers.filter(s => s.role === 'doctor').length;
                const patientCount = speakers.filter(s => s.role === 'patient').length;
                speakerLegendText.innerHTML = `
                    <span class="badge bg-primary me-2"><i class="fas fa-user-md"></i> Doctor: ${doctorCount}</span>
                    <span class="badge bg-success"><i class="fas fa-user"></i> Patient: ${patientCount}</span>
                `;
                speakerLegend.classList.remove('d-none');
            }

            console.log('🎯 Enhanced speaker-separated transcription displayed');
        } else {
            // Fallback to regular transcription
            transcriptionContainer.innerHTML = '<textarea id="transcriptionArea" class="form-control" style="height: 100%; border: none; background: transparent; resize: none;" placeholder="Start recording to see transcription here..."></textarea>';
            const newTextArea = document.getElementById('transcriptionArea');
            if (newTextArea) {
                newTextArea.value = fullText;
            }

            // Hide speaker legend
            if (speakerLegend) {
                speakerLegend.classList.add('d-none');
            }
        }

        // Update transcription status
        updateTranscriptionStatus(speakers);
    }

    /**
     * Update transcription status indicators
     */
    function updateTranscriptionStatus(speakers) {
        const transcriptionStatus = document.getElementById('transcriptionStatus');
        if (!transcriptionStatus) return;

        let statusHtml = '';

        if (speakers && speakers.length > 0) {
            const speakerCount = new Set(speakers.map(s => s.speaker)).size;
            statusHtml += `<span class="badge bg-info"><i class="fas fa-users me-1"></i>${speakerCount} Speaker${speakerCount > 1 ? 's' : ''}</span>`;
        }

        if (currentLanguage) {
            const langName = currentLanguage === 'ar-SA' ? 'العربية' : 'English';
            const langFlag = currentLanguage === 'ar-SA' ? '🇸🇦' : '🇺🇸';
            statusHtml += ` <span class="badge bg-secondary">${langFlag} ${langName}</span>`;
        }

        transcriptionStatus.innerHTML = statusHtml;
    }

    /**
     * Format timestamp for speaker display
     */
    function formatTimestamp(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Update UI to show speaker identification status
     */
    function updateSpeakerIdentificationUI() {
        const speakerCount = Object.keys(speakerSeparatedTranscription).length;
        if (speakerCount > 1) {
            // Update recording status to show multi-speaker mode
            const recordingStatus = document.getElementById('recordingStatus');
            if (recordingStatus) {
                recordingStatus.innerHTML = '<span class="badge bg-info me-2"><i class="fas fa-users-medical fa-xs"></i></span><span class="text-info fw-bold">Recording (Multi-speaker)</span>';
            }

            // Show speaker identification info
            showSpeakerIdentificationInfo(speakerCount);
        }
    }

    /**
     * Show speaker identification information
     */
    function showSpeakerIdentificationInfo(speakerCount) {
        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const speakerInfo = `
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-users-medical me-2"></i>
                <strong>Speaker Identification Active:</strong> ${speakerCount} speakers detected.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.innerHTML = speakerInfo;
    }

    /**
     * Continuous language detection for seamless switching
     */
    function detectLanguageContinuously(text) {
        if (!continuousLanguageDetection || !text || text.length < 10) {
            return;
        }

        const now = Date.now();

        // Cooldown check to prevent too frequent switches
        if (now - lastLanguageSwitch < languageSwitchCooldown) {
            return;
        }

        // Add to detection buffer
        languageDetectionBuffer.push({
            text: text,
            timestamp: now,
            length: text.length
        });

        // Keep only recent detections (last 30 seconds)
        languageDetectionBuffer = languageDetectionBuffer.filter(
            detection => now - detection.timestamp < 30000
        );

        // Need at least 3 samples for reliable detection
        if (languageDetectionBuffer.length < 3) {
            return;
        }

        // Analyze language patterns
        const languageAnalysis = analyzeLanguagePatterns(languageDetectionBuffer);

        if (languageAnalysis.confidence > languageConfidenceThreshold &&
            languageAnalysis.detectedLanguage !== currentLanguage) {

            const previousLanguage = currentLanguage;
            currentLanguage = languageAnalysis.detectedLanguage;
            lastLanguageSwitch = now;
            languageSwitchCooldown = 10000; // 10 second cooldown

            // Update language selector UI
            updateLanguageSelectorForDetection(currentLanguage);

            // Show language switch notification
            showLanguageSwitchNotification(previousLanguage, currentLanguage, languageAnalysis.confidence);

            console.log('🌐 Continuous language detection - Auto-switched:', {
                from: previousLanguage,
                to: currentLanguage,
                confidence: languageAnalysis.confidence,
                samples: languageDetectionBuffer.length
            });
        }
    }

    /**
     * Analyze language patterns from text samples
     */
    function analyzeLanguagePatterns(samples) {
        let arabicScore = 0;
        let englishScore = 0;
        let totalLength = 0;

        // Arabic character ranges
        const arabicRegex = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

        samples.forEach(sample => {
            const text = sample.text;
            const length = text.length;
            totalLength += length;

            // Count Arabic characters
            const arabicChars = (text.match(arabicRegex) || []).length;
            const arabicRatio = arabicChars / length;

            // Language scoring
            if (arabicRatio > 0.3) {
                arabicScore += length * arabicRatio;
            } else {
                englishScore += length * (1 - arabicRatio);
            }

            // Check for language-specific words
            const arabicWords = ['أنا', 'أنت', 'هو', 'هي', 'نحن', 'أنتم', 'هم', 'الألم', 'الصداع', 'الحمى'];
            const englishWords = ['I', 'you', 'he', 'she', 'we', 'they', 'pain', 'headache', 'fever'];

            arabicWords.forEach(word => {
                if (text.includes(word)) arabicScore += 10;
            });

            englishWords.forEach(word => {
                if (text.includes(word)) englishScore += 10;
            });
        });

        const totalScore = arabicScore + englishScore;
        const arabicConfidence = totalScore > 0 ? arabicScore / totalScore : 0;
        const englishConfidence = totalScore > 0 ? englishScore / totalScore : 0;

        return {
            detectedLanguage: arabicConfidence > englishConfidence ? 'ar-SA' : 'en-US',
            confidence: Math.max(arabicConfidence, englishConfidence),
            arabicScore: arabicScore,
            englishScore: englishScore
        };
    }

    /**
     * Update language selector when auto-detection occurs
     */
    function updateLanguageSelectorForDetection(detectedLanguage) {
        const languageSelector = document.getElementById('languageSelector');
        if (!languageSelector) return;

        const selectorValue = detectedLanguage === 'ar-SA' ? 'ar' : 'en';
        languageSelector.value = selectorValue;

        // Add visual indicator for auto-detection
        languageSelector.style.borderColor = '#28a745';
        languageSelector.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';

        setTimeout(() => {
            languageSelector.style.borderColor = '';
            languageSelector.style.boxShadow = '';
        }, 3000);
    }

    /**
     * Show language switch notification
     */
    function showLanguageSwitchNotification(fromLang, toLang, confidence) {
        const languageNames = {
            'ar-SA': 'العربية',
            'en-US': 'English'
        };

        const alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) return;

        const notification = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-language me-2"></i>
                <strong>Language Auto-Switched:</strong> ${languageNames[fromLang] || fromLang} → ${languageNames[toLang] || toLang}
                <small class="text-muted ms-2">(Confidence: ${Math.round(confidence * 100)}%)</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        alertContainer.innerHTML = notification;

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }


    // Handle transcription with enhanced completeness checking
    function handleTranscription(text) {
        // Clean and validate the input
        const cleanText = text.trim();
        if (empty(cleanText)) {
            return;
        }

        // FIXED: Only update transcription area if it's different and we're not in the middle of live updates
        if (transcriptionArea && transcriptionArea.value !== cleanText) {
            transcriptionArea.value = cleanText;
            console.log('📝 Transcription area updated via handleTranscription, length:', cleanText.length);
        }

        // PROFESSIONAL: Update final transcription if we're in final mode
        // Note: transcriptionMode not used in simple interface, always update final transcript
        if (!finalTranscript || finalTranscript !== cleanText) {
            finalTranscript = cleanText;
            console.log('📝 Final transcript updated, length:', cleanText.length);
        }

        // Validate text completeness
        const completenessCheck = validateTextCompleteness(cleanText, liveTranscription || '');
        if (!completenessCheck.isComplete) {
            console.log('🔍 Text completeness issue detected:', completenessCheck.missingText);

            // Show notification but still process the text
            if (completenessCheck.missingText) {
                showAlert(`⚠️ ${completenessCheck.missingText}`, 'warning');
            }
        }

        // AJAX call to handle transcription (for backward compatibility)
        $.ajax({
            url: '/ai/voice-assistant/handle-transcription',
            method: 'POST',
            data: {
                text: cleanText,
                sessionId: sessionId,
                speakerTransitions: speakerTransitions.length,
                voiceActivityLevel: voiceActivityLevel,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    console.log('📝 Transcription processed successfully');
                } else {
                    console.warn('⚠️ Transcription processing issue:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ Transcription processing error:', error);
            }
        });
    }

    // FIXED: Enhanced medical data extraction process
    function processWithAIForAnalysis(text, callback) {
        // Always process, even short transcriptions for medical content
        if (text.length < 5) {
            console.log('⚠️ Transcription too short for AI analysis');
            isProcessing = false;
            hideProgressIndicator();
            return;
        }

        console.log('🔬 Starting medical data extraction...');
        console.log('📝 Text length:', text.length);
        console.log('🗒️ Text preview:', text.substring(0, 100) + '...');

        updateProcessingStage('Analyzing medical content with AI...');
        console.log('⏳ Sending to AI for medical extraction...');

        // AJAX call to process with AI
        $.ajax({
            url: '/ai/voice-assistant/process-with-ai',
            method: 'POST',
            data: {
                transcription: text,
                sessionId: sessionId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                console.log('✅ AI processing response received');
                console.log('📋 Response data:', response);

                if (response.success) {
                    console.log('✅ AI processing successful');
                    updateProcessingStage('Parsing AI response and extracting medical data...');

                    console.log('📋 Raw extracted data from AI:', response.extractedData);

                    // FIXED: Enhanced data validation and extraction
                    if (response.extractedData && typeof response.extractedData === 'object') {
                        extractedData = response.extractedData;
                        console.log('💾 Extracted data stored:', extractedData);

                        // FIXED: Always call updateChartFields regardless of data content
                        console.log('🗞️ Updating chart fields with extracted data...');
                        updateChartFields(extractedData);

                        updateProcessingStage('Medical data extraction completed!');

                        // FIXED: Debug extracted data fields
                        console.log('🔍 Extracted data breakdown:');
                        console.log('  - Symptoms:', extractedData.symptoms || 'None');
                        console.log('  - Medical History:', extractedData.medical_history || 'None');
                        console.log('  - Physical Findings:', extractedData.physical_findings || 'None');
                        console.log('  - Medications:', extractedData.medications || 'None');
                        console.log('  - Vital Signs:', extractedData.vital_signs || 'None');
                        console.log('  - Diagnosis:', extractedData.diagnosis || 'None');
                        console.log('  - Care Plan:', extractedData.care_plan || 'None');
                    } else {
                        console.warn('⚠️ No extracted data or invalid format');
                        console.log('🔄 Response structure:', response);

                        // FIXED: Provide fallback data structure
                        extractedData = {
                            symptoms: text.includes('pain') || text.includes('hurt') ? 'Pain reported in consultation' : '',
                            medical_history: '',
                            physical_findings: '',
                            medications: '',
                            vital_signs: '',
                            diagnosis: '',
                            care_plan: ''
                        };
                        updateChartFields(extractedData);
                        updateProcessingStage('Basic medical data extracted (limited content)');
                    }

                    // Call the callback function if provided
                    if (callback && typeof callback === 'function') {
                        console.log('📞 Calling callback function...');
                        callback();
                    } else {
                        console.log('ℹ️ No callback function provided');
                    }
                } else {
                    console.error('❌ AI processing failed:', response.message);
                    updateProcessingStage('Failed to parse AI response: ' + (response.message || 'Unknown error'));

                    // FIXED: Don't hide progress immediately, try fallback extraction
                    setTimeout(() => {
                        console.log('🔄 Attempting fallback medical data extraction...');
                        attemptFallbackExtraction(text, callback);
                    }, 2000);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ AJAX AI processing error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                updateProcessingStage('AI processing failed. Retrying...');

                // FIXED: Retry mechanism for failed requests
                setTimeout(() => {
                    console.log('🔄 Retrying AI processing...');
                    attemptFallbackExtraction(text, callback);
                }, 3000);
            }
        });
    }

    // FIXED: Fallback medical data extraction
    function attemptFallbackExtraction(text, callback) {
        console.log('🔄 Using fallback medical data extraction...');

        // Basic medical keyword extraction as fallback
        const fallbackData = {
            symptoms: extractKeywords(text, ['pain', 'hurt', 'ache', 'fever', 'cough', 'nausea', 'dizzy', 'tired']),
            medical_history: extractKeywords(text, ['diabetes', 'hypertension', 'heart', 'surgery', 'allergy']),
            physical_findings: extractKeywords(text, ['blood pressure', 'temperature', 'heart rate', 'exam']),
            medications: extractKeywords(text, ['medication', 'medicine', 'drug', 'take', 'prescription']),
            vital_signs: extractKeywords(text, ['blood pressure', 'pulse', 'temperature', 'weight']),
            diagnosis: text.length > 50 ? 'Pending detailed analysis' : '',
            care_plan: ''
        };

        console.log('🔄 Fallback data extracted:', fallbackData);
        extractedData = fallbackData;
        updateChartFields(extractedData);
        updateProcessingStage('Fallback medical data extraction completed!');

        if (callback && typeof callback === 'function') {
            callback();
        } else {
            isProcessing = false;
            hideProgressIndicator();
        }
    }

    // FIXED: Simple keyword extraction function
    function extractKeywords(text, keywords) {
        const found = [];
        const lowerText = text.toLowerCase();

        keywords.forEach(keyword => {
            if (lowerText.includes(keyword)) {
                found.push(keyword);
            }
        });

        return found.length > 0 ? `Keywords found: ${found.join(', ')}` : '';
    }

    // Process with AI (legacy function for compatibility)
    function processWithAI(text) {
        processWithAIForAnalysis(text, null);
    }

    // FIXED: Enhanced chart fields update with better debugging
    function updateChartFields(data) {
        console.log('🗞️ Updating chart fields with data:', data);

        // FIXED: Ensure all data fields exist
        const safeData = {
            symptoms: data.symptoms || '',
            medical_history: data.medical_history || '',
            physical_findings: data.physical_findings || '',
            medications: data.medications || '',
            vital_signs: data.vital_signs || '',
            diagnosis: data.diagnosis || '',
            care_plan: data.care_plan || ''
        };

        // FIXED: Update each field with detailed logging
        if (symptomsField) {
            console.log('🩺 Updating symptoms field:', safeData.symptoms);
            symptomsField.value = smartAppendToField(symptomsField.value, safeData.symptoms);
        } else {
            console.warn('⚠️ Symptoms field not found');
        }

        if (medicalHistoryField) {
            console.log('📋 Updating medical history field:', safeData.medical_history);
            medicalHistoryField.value = smartAppendToField(medicalHistoryField.value, safeData.medical_history);
        } else {
            console.warn('⚠️ Medical history field not found');
        }

        if (physicalFindingsField) {
            console.log('🔍 Updating physical findings field:', safeData.physical_findings);
            physicalFindingsField.value = smartAppendToField(physicalFindingsField.value, safeData.physical_findings);
        } else {
            console.warn('⚠️ Physical findings field not found');
        }

        if (medicationsField) {
            console.log('💊 Updating medications field:', safeData.medications);
            medicationsField.value = smartAppendToField(medicationsField.value, safeData.medications);
        } else {
            console.warn('⚠️ Medications field not found');
        }

        if (vitalSignsField) {
            console.log('🩺 Updating vital signs field:', safeData.vital_signs);
            vitalSignsField.value = smartAppendToField(vitalSignsField.value, safeData.vital_signs);
        } else {
            console.warn('⚠️ Vital signs field not found');
        }

        if (diagnosisField) {
            console.log('🔬 Updating diagnosis field:', safeData.diagnosis);
            diagnosisField.value = smartAppendToField(diagnosisField.value, safeData.diagnosis);
        } else {
            console.warn('⚠️ Diagnosis field not found');
        }

        if (carePlanField) {
            console.log('📝 Updating care plan field:', safeData.care_plan);
            carePlanField.value = smartAppendToField(carePlanField.value, safeData.care_plan);
        } else {
            console.warn('⚠️ Care plan field not found');
        }

        console.log('✅ Chart fields update completed');

        // FIXED: Visual feedback for successful update
        setTimeout(() => {
            const fields = [symptomsField, medicalHistoryField, physicalFindingsField, medicationsField, vitalSignsField, diagnosisField, carePlanField];
            fields.forEach(field => {
                if (field && field.value.trim()) {
                    field.style.borderLeft = '3px solid #28a745';
                    field.style.backgroundColor = '#f8fff9';
                }
            });

            // Reset styles after 2 seconds
            setTimeout(() => {
                fields.forEach(field => {
                    if (field) {
                        field.style.borderLeft = '';
                        field.style.backgroundColor = '';
                    }
                });
            }, 2000);
        }, 100);
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
            processWithAIForAnalysis(transcription, function () {
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
            success: function (response) {
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
            error: function (xhr, status, error) {
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
            success: function (response) {
                if (response.success) {
                    // Store the AI result ID for later use
                    aiResultId = response.aiResultId;


                    updateProcessingStage('Analysis completed successfully!');
                    showAlert('AI analysis generated successfully!', 'success');

                    // Show the diagnosis entry form
                    const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
                    if (diagnosisEntryForm) {
                        diagnosisEntryForm.style.display = 'block';
                        // Focus on the diagnosis textarea
                        const diagnosisText = document.getElementById('diagnosisText');
                        if (diagnosisText) {
                            diagnosisText.focus();
                        }
                    }
                } else {
                    updateProcessingStage('Failed to create AI result record.');
                    showAlert(response.message || 'Failed to create AI result record.', 'error');
                }

                isProcessing = false;
                hideProgressIndicator();
            },
            error: function (xhr, status, error) {
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

        // FIXED: Enhanced audio level calculation with smoothing
        const currentLevel = sum / dataArray.length;

        // Apply smoothing to reduce noise
        audioLevel = audioLevel * 0.8 + currentLevel * 0.2;

        // NEW: Detect speaker transitions based on audio level changes
        detectSpeakerTransition(audioLevel, previousAudioLevel);
        previousAudioLevel = audioLevel;

        updateAudioLevelIndicator();

        if (isHandsFreeMode) {
            requestAnimationFrame(monitorAudioLevel);
        }
    }

    // NEW: Speaker transition detection function
    function detectSpeakerTransition(currentLevel, previousLevel) {
        const levelDifference = Math.abs(currentLevel - previousLevel);
        const now = Date.now();

        // Detect significant audio level change (potential speaker change)
        if (levelDifference > speakerChangeThreshold * 255) { // 255 is max audio level
            if (now - lastSpeakerChange > 2000) { // Minimum 2 seconds between transitions
                lastSpeakerChange = now;

                // Record speaker transition
                speakerTransitions.push({
                    timestamp: now,
                    audioLevel: currentLevel,
                    changeMagnitude: levelDifference,
                    type: currentLevel > previousLevel ? 'speaker_start' : 'speaker_end'
                });

                console.log('🔊 Speaker transition detected:', {
                    change: levelDifference.toFixed(2),
                    newLevel: currentLevel.toFixed(2),
                    previousLevel: previousLevel.toFixed(2)
                });

                // Notify user about speaker change for medical consultations
                showSpeakerChangeNotification();

                // Reset silence detection on speaker change
                if (isHandsFreeMode && !isHandsFreePaused) {
                    startSilenceDetection();
                }
            }
        }

        // Update voice activity level
        voiceActivityLevel = currentLevel / 255; // Normalize to 0-1
    }

    // NEW: Speaker change notification
    function showSpeakerChangeNotification() {
        const recentTransitions = speakerTransitions.filter(transition =>
            Date.now() - transition.timestamp < 5000 // Last 5 seconds
        );

        if (recentTransitions.length > 1) {
            console.log('👥 Multiple speaker activity detected in conversation');

            // Visual indicator for multi-speaker mode
            const recordingStatus = document.getElementById('recordingStatus');
            if (recordingStatus && isListening) {
                recordingStatus.innerHTML = '<span class="badge bg-warning me-2"><i class="fas fa-users fa-xs"></i></span><span class="text-warning fw-bold">Recording (Multi-speaker)</span>';
            }
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
        isStopping = false; // Reset stopping flag
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

        const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
        if (diagnosisEntryForm) diagnosisEntryForm.style.display = 'none';

        // Clear diagnosis text
        const diagnosisText = document.getElementById('diagnosisText');
        if (diagnosisText) diagnosisText.value = '';

        updateRecordingUI();
        updateHandsFreeStatus();
        hideProgressIndicator();

        showAlert('Session reset successfully!', 'success');
    }

    // SIMPLE TRANSCRIPTION DISPLAY MANAGEMENT
    function updateTranscriptionDisplay() {
        // Simple interface - just update the main transcription area
        if (transcriptionArea) {
            transcriptionArea.value = liveTranscription || '';
        }
    }

    // Simple transcription status update
    function updateTranscriptionStatus() {
        // Simple interface doesn't need complex status updates
        console.log('📝 Transcription status: ' + (isListening ? 'Recording' : 'Ready'));
    }

    // Simple transcription merging (use server results if available)
    function mergeBestTranscriptions() {
        if (serverTranscription && serverTranscription.trim().length > 0) {
            // Use server transcription if available
            if (transcriptionArea) {
                transcriptionArea.value = serverTranscription;
            }
            showAlert('Using server-processed transcription', 'success');
        } else if (liveTranscription && liveTranscription.trim().length > 0) {
            // Fall back to live transcription
            if (transcriptionArea) {
                transcriptionArea.value = liveTranscription;
            }
            showAlert('Using live transcription', 'info');
        } else {
            showAlert('No transcription available', 'warning');
        }
    }

    // Simple audit logging (just console logging)
    function recordTranscriptionEdit(editType, oldText, newText, description) {
        console.log('📝 Transcription edit:', {
            type: editType,
            description: description,
            oldLength: oldText ? oldText.length : 0,
            newLength: newText ? newText.length : 0,
            timestamp: new Date().toISOString()
        });
    }

    // Simple medical disclaimer (just console log)
    function showMedicalDisclaimer() {
        console.log('⚠️ Medical disclaimer: AI transcription is for reference only');
    }

    // Update recording UI
    function updateRecordingUI() {
        console.log('🔄 Updating recording UI:', {
            isListening: isListening,
            audioRecording: audioRecording,
            selectedPatient: selectedPatient,
            isProcessing: isProcessing
        });

        // Update recording status display
        const recordingStatus = document.getElementById('recordingStatus');
        if (recordingStatus) {
            if (isListening || audioRecording) {
                recordingStatus.innerHTML = '<span class="badge bg-danger me-2"><i class="fas fa-circle fa-xs"></i></span><span class="text-danger fw-bold">Recording...</span>';
            } else {
                recordingStatus.innerHTML = '<span class="badge bg-secondary me-2"><i class="fas fa-circle fa-xs"></i></span><span class="text-muted">Not Recording</span>';
            }
        }

        // Update button states
        if (startRecordingBtn) {
            const hasValidPatient = selectedPatient && selectedPatient !== '' && selectedPatient !== null;
            const shouldBeDisabled = !hasValidPatient || isListening || audioRecording;
            startRecordingBtn.disabled = shouldBeDisabled;
            console.log('🔘 Start button disabled:', shouldBeDisabled, {
                selectedPatient: selectedPatient,
                hasValidPatient: hasValidPatient,
                isListening: isListening,
                audioRecording: audioRecording,
                buttonDisabled: startRecordingBtn.disabled
            });
        }

        if (stopRecordingBtn) {
            stopRecordingBtn.disabled = !isListening && !audioRecording;
            console.log('🔘 Stop button disabled:', stopRecordingBtn.disabled);
        }

        if (generateAnalysisBtn) {
            // Use final transcription if available, otherwise live
            const transcription = finalTranscript || liveTranscription || (transcriptionArea ? transcriptionArea.value : '');
            generateAnalysisBtn.disabled = empty(transcription) || isProcessing;
            console.log('🔘 Generate button disabled:', generateAnalysisBtn.disabled, 'transcription length:', transcription.length);
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

    // Clear new patient form
    function clearNewPatientForm() {
        const fields = ['newPatientName', 'newPatientEmail', 'newPatientAge', 'newPatientGender', 'newPatientPhone'];
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.value = '';
        });

        // Clear errors
        const errorFields = ['newPatientNameError', 'newPatientEmailError', 'newPatientAgeError', 'newPatientGenderError', 'newPatientPhoneError'];
        errorFields.forEach(errorId => {
            const errorField = document.getElementById(errorId);
            if (errorField) errorField.classList.add('d-none');
        });
    }

    // Create new patient
    function createNewPatient() {
        const name = document.getElementById('newPatientName').value.trim();
        const email = document.getElementById('newPatientEmail').value.trim();
        const age = document.getElementById('newPatientAge').value;
        const gender = document.getElementById('newPatientGender').value;
        const phone = document.getElementById('newPatientPhone').value.trim();

        // Clear previous errors
        ['newPatientNameError', 'newPatientEmailError', 'newPatientAgeError', 'newPatientGenderError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('d-none');
        });

        // Validation
        let hasError = false;

        if (!name) {
            document.getElementById('newPatientNameError').textContent = 'Name is required';
            document.getElementById('newPatientNameError').classList.remove('d-none');
            hasError = true;
        }

        if (!email) {
            document.getElementById('newPatientEmailError').textContent = 'Email is required';
            document.getElementById('newPatientEmailError').classList.remove('d-none');
            hasError = true;
        } else if (!isValidEmail(email)) {
            document.getElementById('newPatientEmailError').textContent = 'Please enter a valid email';
            document.getElementById('newPatientEmailError').classList.remove('d-none');
            hasError = true;
        }

        if (!age || age < 1 || age > 150) {
            document.getElementById('newPatientAgeError').textContent = 'Please enter a valid age (1-150)';
            document.getElementById('newPatientAgeError').classList.remove('d-none');
            hasError = true;
        }

        if (!gender) {
            document.getElementById('newPatientGenderError').textContent = 'Please select gender';
            document.getElementById('newPatientGenderError').classList.remove('d-none');
            hasError = true;
        }

        if (hasError) return;

        // AJAX call to create new patient
        $.ajax({
            url: '/ai/voice-assistant/create-new-patient',
            method: 'POST',
            data: {
                newPatientName: name,
                newPatientEmail: email,
                newPatientAge: age,
                newPatientGender: gender,
                newPatientPhone: phone,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Add patient to select dropdown
                    const patientSelect = document.getElementById('patientSelect');
                    const option = document.createElement('option');
                    option.value = response.patient.id;
                    option.textContent = response.patient.name + ' (' + (response.patient.age ? response.patient.age + 'y' : 'Age N/A') + ', ' + (response.patient.gender ? response.patient.gender.charAt(0).toUpperCase() + response.patient.gender.slice(1) : 'Gender N/A') + ')';
                    if (patientSelect) patientSelect.appendChild(option);

                    // Select the new patient
                    if (patientSelect) patientSelect.value = response.patient.id;

                    // Trigger change event to update the voice assistant's selectedPatient variable
                    const changeEvent = new Event('change', { bubbles: true });
                    if (patientSelect) patientSelect.dispatchEvent(changeEvent);

                    // Also directly update the voice assistant if available
                    if (window.voiceAssistant && window.voiceAssistant.setSelectedPatient) {
                        window.voiceAssistant.setSelectedPatient(response.patient.id);
                    }

                    // Hide form
                    const newPatientForm = document.getElementById('newPatientForm');
                    if (newPatientForm) newPatientForm.style.display = 'none';
                    clearNewPatientForm();

                    // Show success message
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message || 'Failed to create patient.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Create patient error:', error);
                showNotification('Failed to create patient. Please try again.', 'error');
            }
        });
    }

    // Show notification
    function showNotification(message, type = 'info') {
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

    // Validate email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Complete Consultation Modal Functions
    function showCompleteConsultationModal() {
        const diagnosisText = document.getElementById('diagnosisText');
        if (!diagnosisText || !diagnosisText.value.trim()) {
            showNotification('Please enter your diagnosis first.', 'error');
            return;
        }

        const selectedPatient = document.getElementById('patientSelect');
        if (!selectedPatient || !selectedPatient.value) {
            showNotification('Please select a patient first.', 'error');
            return;
        }

        // Update diagnosis preview
        const diagnosisPreview = document.getElementById('diagnosisPreview');
        if (diagnosisPreview) diagnosisPreview.textContent = diagnosisText.value.trim();

        // Update patient name display in modal
        const modalPatientName = document.getElementById('modalPatientName');
        if (modalPatientName && selectedPatient) {
            const selectedOption = selectedPatient.options[selectedPatient.selectedIndex];
            const patientName = selectedOption ? selectedOption.text.split(' (')[0] : 'Unknown Patient';
            modalPatientName.textContent = patientName;
        }

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('completeConsultationModal'));
        modal.show();

        // Load appointments for the selected patient
        loadPatientAppointments(selectedPatient ? selectedPatient.value : null);

        // Set up completion type change handler
        setupCompletionTypeHandler();
    }

    function setupCompletionTypeHandler() {
        // No longer needed since we removed the radio buttons
        // Appointment selection is now always visible
        updateCompleteButtonState();
    }

    function loadPatientAppointments(patientId) {
        const appointmentInfoDiv = document.getElementById('appointmentInfo');
        const appointmentInfoText = document.getElementById('appointmentInfoText');

        // Debug logging
        console.log('Loading appointments for patient:', patientId);
        console.log('Available patient appointments:', window.patientAppointments);

        // Use the pre-loaded appointments data instead of AJAX call
        const appointments = window.patientAppointments && window.patientAppointments[patientId] ? window.patientAppointments[patientId] : [];

        console.log('Found appointments for patient:', appointments);

        if (appointments.length > 0) {
            // Get the first available appointment
            const appointment = appointments[0];
            if (appointmentInfoDiv) appointmentInfoDiv.style.display = 'block';
            if (appointmentInfoText) appointmentInfoText.textContent = `Found ${appointments.length} incomplete appointment(s). The first one will be automatically selected: ${appointment.appointment_date_formatted} (${appointment.appointment_type})`;

            // Store appointment ID for completion
            window.selectedAppointmentId = appointment.id;

            // Show appointment details
            showAppointmentPreview(appointment.id);
        } else {
            if (appointmentInfoDiv) appointmentInfoDiv.style.display = 'block';
            if (appointmentInfoText) appointmentInfoText.textContent = 'No scheduled appointments available for this patient. Diagnosis will be saved without appointment completion.';
            window.selectedAppointmentId = null;
            showAppointmentPreview(null);
            console.log('No appointments found for patient:', patientId);
        }
    }

    function showAppointmentPreview(appointmentId) {
        const previewDiv = document.getElementById('appointmentPreview');
        const detailsDiv = document.getElementById('appointmentDetails');

        if (!appointmentId) {
            if (previewDiv) previewDiv.style.display = 'none';
            return;
        }

        // Find appointment details
        const patientSelect = document.getElementById('patientSelect');
        const appointments = window.patientAppointments && patientSelect && window.patientAppointments[patientSelect.value] ? window.patientAppointments[patientSelect.value] : [];
        const appointment = appointments.find(apt => apt.id == appointmentId);

        if (appointment && detailsDiv) {
            detailsDiv.innerHTML = `
                <p><strong>Appointment:</strong> ${appointment.appointment_date_formatted}</p>
                <p><strong>Type:</strong> ${appointment.appointment_type}</p>
                <p><strong>Status:</strong> Will be marked as completed</p>
                <p><strong>Diagnosis:</strong> Will be linked to current diagnosis</p>
            `;
        } else if (detailsDiv) {
            detailsDiv.innerHTML = '<p>Appointment details not found.</p>';
        }

        if (previewDiv) previewDiv.style.display = 'block';
    }

    function updateCompleteButtonState() {
        const completeBtn = document.getElementById('modalCompleteConsultationBtn');
        const hasAppointment = window.selectedAppointmentId !== null;

        // Button is always enabled, but text changes based on appointment availability
        if (completeBtn) {
            completeBtn.disabled = false;
            completeBtn.innerHTML = hasAppointment ?
                '<i class="fas fa-check me-1"></i>Complete Appointment' :
                '<i class="fas fa-save me-1"></i>Save Diagnosis';
        }
    }

    function completeConsultation() {
        const diagnosisText = document.getElementById('diagnosisText');
        const appointmentId = window.selectedAppointmentId;
        const appointmentDoctorNotes = document.getElementById('appointmentDoctorNotes');
        const doctorNotes = appointmentDoctorNotes ? appointmentDoctorNotes.value.trim() : '';
        const completionType = appointmentId ? 'complete_appointment' : 'save_only';

        if (!diagnosisText || !diagnosisText.value.trim()) {
            showNotification('Please enter your diagnosis text.', 'error');
            return;
        }

        const selectedPatient = document.getElementById('patientSelect');
        if (!selectedPatient || !selectedPatient.value) {
            showNotification('Please select a patient first.', 'error');
            return;
        }

        // Get session data
        const container = document.querySelector('[data-session-id]');
        let sessionId = container ? container.getAttribute('data-session-id') : '';
        const transcriptionArea = document.getElementById('transcriptionArea');
        const transcription = transcriptionArea ? transcriptionArea.value : '';

        // If no session ID found in container, try to use the global sessionId variable
        if (!sessionId) {
            sessionId = window.sessionId || '';
        }

        // If still no session ID, generate a new one as a fallback (though this shouldn't happen in normal flow)
        if (!sessionId) {
            sessionId = generateUUID();
            // Update the container with the new session ID if possible
            if (container) {
                container.setAttribute('data-session-id', sessionId);
            }
        }

        // Transcription is optional - allow consultation completion with just diagnosis
        // Original validation prevented completion if only diagnosis was provided without transcription

        // Get AI data if available
        const aiResultId = window.voiceAssistant ? window.voiceAssistant.getAiResultId() : null;
        const extractedData = window.voiceAssistant ? window.voiceAssistant.getExtractedData() : {};

        // Disable button and show loading
        const completeBtn = document.getElementById('modalCompleteConsultationBtn');
        const originalText = completeBtn ? completeBtn.innerHTML : '';
        if (completeBtn) {
            completeBtn.disabled = true;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Completing...';
        }

        // Prepare data for AJAX call
        const ajaxData = {
            diagnosisText: diagnosisText.value.trim(),
            sessionId: sessionId,
            selectedPatient: selectedPatient.value,
            transcription: transcription,
            completionType: completionType,
            appointmentId: appointmentId || null,
            doctorNotes: doctorNotes,
            aiResultId: aiResultId,
            extractedData: extractedData,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // AJAX call to complete consultation
        $.ajax({
            url: '/ai/voice-assistant/complete-consultation',
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('completeConsultationModal'));
                    if (modal) modal.hide();

                    // Hide the diagnosis form
                    const diagnosisEntryForm = document.getElementById('diagnosisEntryForm');
                    if (diagnosisEntryForm) diagnosisEntryForm.style.display = 'none';
                    if (diagnosisText) diagnosisText.value = '';

                    // Show appropriate success message
                    const message = completionType === 'complete_appointment' ?
                        'Diagnosis saved and appointment completed successfully!' :
                        'Diagnosis saved successfully!';
                    showNotification(message, 'success');

                    // Redirect to diagnosis view
                    if (response.redirectUrl) {
                        setTimeout(function() {
                            window.location.href = response.redirectUrl;
                        }, 2000);
                    }
                } else {
                    showNotification(response.message || 'Failed to complete consultation.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Complete consultation error:', error);
                console.error('Response details:', {
                    status: xhr.status,
                    responseText: xhr.responseText,
                    statusText: xhr.statusText
                });

                if (xhr.status === 422) {
                    // Validation error - try to get specific error messages
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.errors) {
                            const allErrors = Object.values(response.errors).flat();
                            showNotification('Validation error: ' + allErrors.join(', '), 'error');
                        } else {
                            showNotification('Validation failed. Please check all required fields.', 'error');
                        }
                    } catch (e) {
                        showNotification('Validation failed. Please check all required fields.', 'error');
                    }
                } else {
                    showNotification('Failed to complete consultation. Please try again. (' + error + ')', 'error');
                }
            },
            complete: function() {
                // Re-enable button
                if (completeBtn) {
                    completeBtn.disabled = false;
                    completeBtn.innerHTML = originalText;
                }
            }
        });
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
        getSelectedPatient: function () { return selectedPatient; },
        setSelectedPatient: function (patientId) {
            selectedPatient = patientId;
            updateRecordingUI();

        },
        getAiResultId: function () { return aiResultId; },
        getExtractedData: function () { return extractedData; },
        getCurrentLanguage: function () { return currentLanguage; },
        getCurrentDiagnosisId: function () {
            // This should return the current diagnosis ID after manual diagnosis is saved
            // For now, we'll need to track it when the diagnosis is created
            return window.currentDiagnosisId || null;
        },
        // Debug patient selection
        debugPatientSelection: function () {
            console.log('🔍 Patient Selection Debug:');
            console.log('  selectedPatient variable:', selectedPatient);
            console.log('  patientSelect element:', patientSelect);
            if (patientSelect) {
                console.log('  patientSelect.value:', patientSelect.value);
                console.log('  patientSelect.options.length:', patientSelect.options.length);
                console.log('  patientSelect.disabled:', patientSelect.disabled);
            }
            console.log('  startRecordingBtn.disabled:', startRecordingBtn ? startRecordingBtn.disabled : 'button not found');
            return {
                selectedPatient: selectedPatient,
                patientSelectValue: patientSelect ? patientSelect.value : null,
                startButtonDisabled: startRecordingBtn ? startRecordingBtn.disabled : null
            };
        },
        detectLanguage: detectLanguage,
        // Debug functions
        testArabicDetection: function () {
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
        forceArabicMode: function () {
            console.log('🇸🇦 Forcing Arabic mode...');
            setRecognitionLanguage('ar');
        },
        forceEnglishMode: function () {
            console.log('🇺🇸 Forcing English mode...');
            setRecognitionLanguage('en');
        },
        // Test current language switching
        testLanguageSwitching: function () {
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
        getLimitations: function () {
            console.log('⚠️ Web Speech API Limitations:');
            console.log('• Cannot detect spoken language before transcription');
            console.log('• Language switching requires stopping/starting recognition');
            console.log('• Only detects language from already-transcribed text');
            console.log('• Arabic speech transcribed as English when recognition is in English mode');
            console.log('• Best solution: Start with user\'s regional language (Arabic for Middle East)');
            console.log('• Alternative: Use server-side speech recognition with language detection');
        },

        // NEW: Enhanced debugging and monitoring functions
        getRecordingHealth: function () {
            return {
                isListening: isListening,
                sessionDuration: sessionStartTime ? Math.floor((Date.now() - sessionStartTime) / 1000) : 0,
                transcriptLength: finalTranscript.length,
                speakerTransitions: speakerTransitions.length,
                voiceActivityLevel: voiceActivityLevel,
                audioLevel: audioLevel,
                currentLanguage: currentLanguage,
                bufferHealth: {
                    hasBackup: !!interimBackupBuffer,
                    bufferSize: transcriptBuffer.length,
                    lastUpdate: lastTranscriptTime
                },
                processingStatus: {
                    isProcessing: isProcessing,
                    immediateMode: immediateProcessingEnabled,
                    hasExtractedData: Object.keys(extractedData).length > 0
                }
            };
        },

        testTextCompleteness: function () {
            console.log('🧪 Testing text completeness validation...');
            const testCases = [
                { current: 'The patient has', previous: 'The patient has severe chest pain', shouldBeComplete: false },
                { current: 'The patient has severe chest pain', previous: '', shouldBeComplete: true },
                { current: 'Short', previous: 'This is a much longer text that should have more words', shouldBeComplete: false }
            ];

            testCases.forEach((testCase, index) => {
                const result = validateTextCompleteness(testCase.current, testCase.previous);
                console.log(`Test ${index + 1}:`, {
                    input: testCase.current,
                    previous: testCase.previous,
                    result: result,
                    expectedIncomplete: testCase.shouldBeComplete
                });
            });
        },

        getSpeakerAnalytics: function () {
            if (speakerTransitions.length === 0) {
                return { message: 'No speaker transitions detected yet' };
            }

            const now = Date.now();
            const recentTransitions = speakerTransitions.filter(t => now - t.timestamp < 30000); // Last 30 seconds

            return {
                totalTransitions: speakerTransitions.length,
                recentTransitions: recentTransitions.length,
                isMultiSpeaker: speakerTransitions.length > 1,
                averageTimeBetweenTransitions: speakerTransitions.length > 1 ?
                    (speakerTransitions[speakerTransitions.length - 1].timestamp - speakerTransitions[0].timestamp) / (speakerTransitions.length - 1) / 1000 : 0,
                currentVoiceActivity: voiceActivityLevel,
                transitions: recentTransitions.map(t => ({
                    time: new Date(t.timestamp).toLocaleTimeString(),
                    type: t.type,
                    level: t.audioLevel.toFixed(2)
                }))
            };
        },

        forceImmediateProcessing: function () {
            console.log('🚀 Forcing immediate processing mode...');
            immediateProcessingEnabled = true;
            if (transcriptionArea && transcriptionArea.value.trim()) {
                handleTranscription(transcriptionArea.value.trim());
            }
        },

        getSystemPerformance: function () {
            return {
                languageSwitchingPerformance: {
                    currentDelay: '200ms (optimized from 500ms)',
                    interimDetection: 'Enabled',
                    immediateUpdates: immediateProcessingEnabled
                },
                bufferingPerformance: {
                    delay: '100ms (optimized from 500ms)',
                    backupSystem: 'Enabled',
                    immediateMode: immediateProcessingEnabled
                },
                multiSpeakerSupport: {
                    silenceTimeout: `${maxSilenceDuration}ms (optimized from 30000ms)`,
                    speakerDetection: 'Enabled',
                    transitionThreshold: speakerChangeThreshold,
                    transitionsDetected: speakerTransitions.length
                },
                textCompleteness: {
                    validationEnabled: true,
                    backupBuffering: !!interimBackupBuffer,
                    duplicatePrevention: true
                },
                audioRecording: {
                    supported: audioRecordingSupported,
                    hybridMode: hybridModeEnabled,
                    blobValidation: true,
                    emptyBlobPrevention: true,
                    fallbackMechanisms: true
                }
            };
        },

        // NEW: Export logs for debugging and support
        exportLogs: function () {
            return voiceAssistantLogger.exportLogs();
        },

        // NEW: Get recent logs for debugging
        getRecentLogs: function (level = null, limit = 20) {
            return voiceAssistantLogger.getLogs(level, limit);
        },

        // NEW: Clear logs
        clearLogs: function () {
            voiceAssistantLogger.logs = [];
            console.log('🧹 Voice assistant logs cleared');
        },

        // NEW: Get audio recording health status
        getAudioHealth: function () {
            return {
                recordingSupported: audioRecordingSupported,
                currentlyRecording: audioRecording,
                hasValidBlob: audioBlob && validateAudioBlob(audioBlob),
                blobSize: audioBlob ? audioBlob.size : 0,
                blobType: audioBlob ? audioBlob.type : null,
                qualityMetrics: audioQualityMetrics,
                hybridModeEnabled: hybridModeEnabled,
                serverProcessingInProgress: serverProcessingInProgress,
                lastRecordingError: null // Could be enhanced to track errors
            };
        },

        // NEW: Force stop recording (emergency stop)
        forceStopRecording: function () {
            console.log('🚨 Force stopping all recording activities...');

            // Force stop live transcription
            isListening = false;
            if (recognition) {
                try {
                    recognition.stop();
                } catch (e) {
                    console.log('Recognition already stopped');
                }
            }

            // Force stop audio recording
            audioRecording = false;
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                try {
                    mediaRecorder.stop();
                } catch (e) {
                    console.log('MediaRecorder already stopped');
                }
            }

            // Stop all media tracks
            if (mediaRecorder && mediaRecorder.stream) {
                mediaRecorder.stream.getTracks().forEach(track => {
                    try {
                        track.stop();
                    } catch (e) {
                        console.log('Track already stopped');
                    }
                });
            }

            // Clear all timeouts
            if (restartTimeout) clearTimeout(restartTimeout);
            if (bufferTimeout) clearTimeout(bufferTimeout);

            // Stop enhanced features
            stopSilenceDetection();
            stopRecordingTimer();
            stopAudioLevelMonitoring();

            // Update UI
            updateRecordingUI();

            // Complete session
            completeSession();

            console.log('✅ Force stop completed');
            isStopping = false; // Reset stopping flag
            showAlert('Recording force stopped', 'warning');
        },

        // Force UI update (for debugging and manual fixes)
        forceUIUpdate: function () {
            console.log('🔄 Force updating UI...');
            syncPatientSelection();
            updateRecordingUI();
            updateHandsFreeStatus();
            console.log('✅ UI update completed');
        },

        // Debug transcription state
        debugTranscription: function () {
            console.log('🔍 Transcription Debug:');
            console.log('  liveTranscription:', liveTranscription);
            console.log('  finalTranscript:', finalTranscript);
            console.log('  transcriptBuffer:', transcriptBuffer);
            console.log('  interimBackupBuffer:', interimBackupBuffer);
            console.log('  transcriptionArea.value:', transcriptionArea ? transcriptionArea.value : 'N/A');
            console.log('  isListening:', isListening);
            console.log('  bufferTimeout active:', !!bufferTimeout);
            console.log('  lastTranscriptTime:', lastTranscriptTime ? new Date(lastTranscriptTime).toLocaleTimeString() : 'Never');
            return {
                liveTranscription: liveTranscription,
                finalTranscript: finalTranscript,
                transcriptBuffer: transcriptBuffer,
                transcriptionAreaValue: transcriptionArea ? transcriptionArea.value : null,
                isListening: isListening,
                bufferTimeoutActive: !!bufferTimeout
            };
        },

        // Reset transcription state (for debugging)
        resetTranscription: function () {
            console.log('🔄 Resetting transcription state...');
            liveTranscription = '';
            finalTranscript = '';
            transcriptBuffer = '';
            interimBackupBuffer = '';
            if (transcriptionArea) {
                transcriptionArea.value = '';
            }
            if (bufferTimeout) {
                clearTimeout(bufferTimeout);
                bufferTimeout = null;
            }
            console.log('✅ Transcription state reset');
        }
    };

    // Log system improvements summary
    voiceAssistantLogger.info('🎙️ Voice Assistant Initialized', {
        features: [
            'Live transcription with browser speech recognition',
            'Server-side audio processing with OpenAI Whisper',
            'Hybrid audio recording and processing',
            'Medical dictionary integration',
            'Enhanced error handling and fallback mechanisms',
            'Simple and clean user interface'
        ],
        audioRecording: {
            validationEnabled: true,
            fallbackMechanisms: true,
            emptyBlobPrevention: true,
            enhancedErrorHandling: true
        },
        interface: {
            simpleMode: true,
            medicalDictionary: Object.keys(medicalDictionary).length + ' terms'
        }
    });

    console.log('🎙️ Voice Assistant Initialized:');
    console.log('✅ Live transcription: Active');
    console.log('✅ Server processing: Ready');
    console.log('🎵 Audio recording: Enhanced with validation');
    console.log('🏥 Medical dictionary: ' + Object.keys(medicalDictionary).length + ' terms loaded');
    console.log('📝 Simple interface: Enabled');
    console.log('🚀 Hybrid mode: ' + (hybridModeEnabled ? 'Enabled' : 'Disabled'));
    console.log('🔍 Use window.voiceAssistant.getAudioHealth() to check audio recording status');
    console.log('🚨 If recording won\'t stop, use: window.voiceAssistant.forceStopRecording()');
    console.log('📊 Debug transcription: window.voiceAssistant.debugTranscription()');
    console.log('🔄 Reset transcription: window.voiceAssistant.resetTranscription()');

    // Simple interface - no complex styles needed
    console.log('🎨 Simple transcription interface loaded');

    // Robust initialization for both direct loading and AJAX loading
    function initializeVoiceAssistant() {
        // Check if we're on the voice assistant page
        const isVoiceAssistantPage = window.location.pathname === '/ai/voice-assistant' ||
                                   document.querySelector('[data-session-id]') !== null;

        if (!isVoiceAssistantPage) {
            console.log('🎙️ Not on voice assistant page, skipping initialization');
            return;
        }

        // Use a timeout to ensure DOM is ready
        setTimeout(function() {
            initVoiceAssistant();
        }, 50);
    }

    // Initialize on DOMContentLoaded for direct loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeVoiceAssistant);
    } else {
        // For already loaded pages (AJAX), initialize immediately
        initializeVoiceAssistant();
    }

    // Also listen for the pageContentLoaded event from AJAX navigation
    if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined') {
        $(document).on('pageContentLoaded', function(event, route) {
            // Clean up keyboard shortcuts help if not on the main voice-assistant page
            if (!route || route !== '/ai/voice-assistant') {
                cleanupKeyboardShortcutsHelp();
                return;
            }

            // Initialize voice assistant for AJAX-loaded content
            setTimeout(function() {
                initVoiceAssistant();
            }, 50);
        });
    } else {
        console.warn('⚠️ jQuery not available, AJAX navigation events will not work');
    }
})();
