<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="author" content="SemiColonWeb">
    <meta name="description" content="Kiosk System - MedCura AI">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a8a">

    <!-- Prevent zoom and scrolling on touch devices -->
    <meta name="format-detection" content="telephone=no">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* Kiosk-specific styles for touch optimization */
        .kiosk-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .kiosk-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .kiosk-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .kiosk-footer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
            z-index: 10;
        }

        /* Touch-optimized buttons */
        .kiosk-btn {
            min-height: 60px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            padding: 1rem 2rem;
            margin: 0.5rem 0;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .kiosk-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .kiosk-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .kiosk-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .kiosk-btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            border: 2px solid #667eea;
        }

        .kiosk-btn-success {
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: white;
        }

        .kiosk-btn-danger {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            color: white;
        }

        /* High contrast mode */
        .high-contrast .kiosk-container {
            background: #000;
            color: #fff;
        }

        .high-contrast .kiosk-header,
        .high-contrast .kiosk-footer {
            background: #000;
            border-color: #fff;
        }

        .high-contrast .kiosk-btn-primary {
            background: #fff;
            color: #000;
            border: 2px solid #fff;
        }

        .high-contrast .kiosk-btn-secondary {
            background: #000;
            color: #fff;
            border: 2px solid #fff;
        }

        /* Card styles */
        .kiosk-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .high-contrast .kiosk-card {
            background: #000;
            border: 2px solid #fff;
        }

        /* Form elements */
        .kiosk-input {
            height: 60px;
            font-size: 1.2rem;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .kiosk-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }

        .high-contrast .kiosk-input {
            background: #000;
            color: #fff;
            border-color: #fff;
        }

        .high-contrast .kiosk-input:focus {
            border-color: #ffff00;
            box-shadow: 0 0 0 3px rgba(255, 255, 0, 0.5);
        }

        /* Loading spinner */
        .kiosk-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Voice guidance */
        .voice-guidance {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.1rem;
            max-width: 300px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .voice-guidance.show {
            opacity: 1;
        }

        /* Progress indicator */
        .kiosk-progress {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }

        .progress-step {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.2);
            margin: 0 1rem;
            position: relative;
            font-size: 1.2rem;
        }

        .progress-step.active {
            background: #fff;
            color: #667eea;
        }

        .progress-step.completed {
            background: #4ade80;
            color: white;
        }

        .progress-step::after {
            content: '';
            position: absolute;
            right: -2rem;
            width: 2rem;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            top: 50%;
            transform: translateY(-50%);
        }

        .progress-step:last-child::after {
            display: none;
        }

        .progress-step.completed::after {
            background: #4ade80;
        }

        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Emergency button */
        .emergency-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            font-size: 2rem;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
            z-index: 1000;
            cursor: pointer;
        }

        .emergency-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(239, 68, 68, 0.6);
        }

        /* Timeout warning */
        .timeout-warning {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(239, 68, 68, 0.95);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            z-index: 2000;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            display: none;
        }
    </style>

    <!-- Font Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/ui-consistency.css') }}">

    <!-- Global Font Styling -->
    <style>
        body, * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
        }
    </style>

    <title>@yield('title', 'Kiosk System | MedCura AI')</title>
    @stack('styles')
</head>
<body class="{{ session('high_contrast') ? 'high-contrast' : '' }}">
    <!-- Voice Guidance -->
    <div id="voiceGuidance" class="voice-guidance" aria-live="polite" aria-atomic="true">
        <i class="fas fa-volume-up me-2"></i>
        <span id="voiceText"></span>
    </div>

    <!-- Emergency Button -->
    <button class="emergency-btn" onclick="callEmergency()" aria-label="Emergency Call">
        <i class="fas fa-exclamation-triangle"></i>
    </button>

    <!-- Timeout Warning -->
    <div id="timeoutWarning" class="timeout-warning">
        <i class="fas fa-clock display-4 mb-3"></i>
        <h2>Session Timeout</h2>
        <p>Your session will expire in <span id="timeoutCountdown">30</span> seconds</p>
        <button class="kiosk-btn kiosk-btn-primary mt-3" onclick="extendSession()">
            Continue Session
        </button>
    </div>

    <div class="kiosk-container">
        <!-- Header -->
        <div class="kiosk-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('demos/medical/images/logo-medical.jpeg') }}"
                         alt="MedCura AI"
                         style="height: 50px; margin-right: 1rem;">
                    <div>
                        <h1 class="h3 mb-0 fw-bold">MedCura AI Kiosk</h1>
                        <small class="text-muted">Touch to continue</small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2" onclick="toggleContrast()" aria-label="Toggle High Contrast">
                        <i class="fas fa-adjust"></i>
                    </button>
                    <button class="btn btn-outline-secondary" onclick="toggleVoice()" aria-label="Toggle Voice Guidance">
                        <i class="fas fa-volume-up"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="kiosk-content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="kiosk-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <small>Session: <span id="sessionTime">00:00:00</span></small>
                </div>
                <div>
                    <button class="btn btn-outline-danger btn-sm" onclick="endSession()">
                        <i class="fas fa-sign-out-alt me-1"></i>End Session
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let sessionStartTime = new Date();
        let sessionTimer;
        let timeoutTimer;
        let timeoutCountdown = 30;
        let voiceEnabled = {{ session('voice_enabled', 'true') ? 'true' : 'false' }};
        let highContrast = {{ session('high_contrast', 'false') ? 'true' : 'false' }};

        // Initialize kiosk session
        document.addEventListener('DOMContentLoaded', function() {
            startSessionTimer();
            setupAccessibility();
            speakText('Welcome to MedCura AI Kiosk. Please touch the screen to begin.');
        });

        // Session timer
        function startSessionTimer() {
            sessionTimer = setInterval(() => {
                const now = new Date();
                const diff = now - sessionStartTime;
                const hours = Math.floor(diff / 3600000);
                const minutes = Math.floor((diff % 3600000) / 60000);
                const seconds = Math.floor((diff % 60000) / 1000);

                document.getElementById('sessionTime').textContent =
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                // Show timeout warning after 25 minutes
                if (diff > 25 * 60 * 1000 && !timeoutTimer) {
                    showTimeoutWarning();
                }
            }, 1000);
        }

        // Timeout warning
        function showTimeoutWarning() {
            const warning = document.getElementById('timeoutWarning');
            warning.style.display = 'block';
            timeoutCountdown = 30;

            timeoutTimer = setInterval(() => {
                document.getElementById('timeoutCountdown').textContent = timeoutCountdown;
                timeoutCountdown--;

                if (timeoutCountdown < 0) {
                    endSession();
                }
            }, 1000);

            speakText('Your session will expire soon. Please touch continue to extend your session.');
        }

        function extendSession() {
            document.getElementById('timeoutWarning').style.display = 'none';
            clearInterval(timeoutTimer);
            timeoutTimer = null;
            sessionStartTime = new Date();
            speakText('Session extended. Please continue.');
        }

        // Voice guidance
        function speakText(text) {
            if (!voiceEnabled) return;

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.8;
            utterance.pitch = 1;
            utterance.volume = 0.8;

            // Show voice guidance
            const voiceGuidance = document.getElementById('voiceGuidance');
            document.getElementById('voiceText').textContent = text;
            voiceGuidance.classList.add('show');

            setTimeout(() => {
                voiceGuidance.classList.remove('show');
            }, 5000);

            window.speechSynthesis.speak(utterance);
        }

        function toggleVoice() {
            voiceEnabled = !voiceEnabled;
            if (voiceEnabled) {
                speakText('Voice guidance enabled');
            } else {
                showVoiceGuidance('Voice guidance disabled');
            }

            // Save preference
            fetch('/kiosk/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ voice_enabled: voiceEnabled })
            });
        }

        function toggleContrast() {
            highContrast = !highContrast;
            document.body.classList.toggle('high-contrast', highContrast);

            if (highContrast) {
                speakText('High contrast mode enabled');
            } else {
                speakText('High contrast mode disabled');
            }

            // Save preference
            fetch('/kiosk/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ high_contrast: highContrast })
            });
        }

        function showVoiceGuidance(text) {
            const voiceGuidance = document.getElementById('voiceGuidance');
            document.getElementById('voiceText').textContent = text;
            voiceGuidance.classList.add('show');

            setTimeout(() => {
                voiceGuidance.classList.remove('show');
            }, 3000);
        }

        // Emergency call
        function callEmergency() {
            if (confirm('Are you sure you want to call emergency services?')) {
                speakText('Emergency services are being contacted. Please stay calm.');
                // In a real implementation, this would call emergency services
                alert('Emergency services contacted. Help is on the way.');
            }
        }

        // End session
        function endSession() {
            if (confirm('Are you sure you want to end this session?')) {
                clearInterval(sessionTimer);
                clearInterval(timeoutTimer);
                speakText('Session ended. Thank you for using MedCura AI Kiosk.');

                // Redirect to home or logout
                window.location.href = '/kiosk/session/end';
            }
        }

        // Setup accessibility
        function setupAccessibility() {
            // Add keyboard navigation for buttons
            const buttons = document.querySelectorAll('.kiosk-btn, button, .btn');
            buttons.forEach(btn => {
                btn.setAttribute('tabindex', '0');
                btn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });

            // Add focus styles
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            document.addEventListener('mousedown', function() {
                document.body.classList.remove('keyboard-navigation');
            });
        }

        // Touch feedback
        document.addEventListener('touchstart', function(e) {
            const target = e.target;
            if (target.classList.contains('kiosk-btn') || target.closest('.kiosk-btn')) {
                target.style.transform = 'scale(0.98)';
            }
        });

        document.addEventListener('touchend', function(e) {
            const target = e.target;
            if (target.classList.contains('kiosk-btn') || target.closest('.kiosk-btn')) {
                setTimeout(() => {
                    target.style.transform = '';
                }, 150);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
