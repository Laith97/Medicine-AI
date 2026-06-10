/**
 * Unified Real-time Notification System for MedcuraAI
 * Handles all notification types with professional toast design
 */

class UnifiedNotificationSystem {
    constructor() {
        // Prevent multiple initializations
        if (window.unifiedNotificationSystemInstance) {
            console.log('🔔 Unified Notification System already initialized');
            return window.unifiedNotificationSystemInstance;
        }

        this.userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        this.pusher = null;
        this.channel = null;
        this.toastContainer = null;
        this.soundEnabled = document.querySelector('meta[name="notification-sound-enabled"]')?.getAttribute('content') === 'true';
        this.initialized = false;

        // Track recently shown toasts to prevent duplicates (tracks title + message hash)
        this.recentToasts = new Map();
        this.toastDebounceMs = 1000; // Prevent duplicates within 1 second

        // Store instance globally to prevent duplicates
        window.unifiedNotificationSystemInstance = this;

        if (this.userId && !this.initialized) {
            this.initialize();
        }
    }

    initialize() {
        if (this.initialized) {
            console.log('🔔 Unified Notification System already initialized');
            return;
        }
        
        console.log('🔔 Initializing Unified Notification System for user:', this.userId);
        this.initialized = true;
        
        // Create Pusher instance
        this.pusher = new Pusher("57bd15962a354114cb5e", {
            cluster: "ap2",
            authEndpoint: "/broadcasting/auth",
            auth: {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            }
        });

        // Subscribe to user's private channel
        this.channel = this.pusher.subscribe(`private-App.User.${this.userId}`);
        
        // Setup event listeners
        this.setupChannelEvents();
        this.setupNotificationEvents();
        this.createToastContainer();
        this.addStyles();
    }

    setupChannelEvents() {
        this.channel.bind("pusher:subscription_succeeded", () => {
            console.log("✅ Unified Notification System Connected Successfully!");
            // Don't show toast on connect - it's annoying
        });

        this.channel.bind("pusher:subscription_error", (error) => {
            console.error("❌ Notification System Error:", error);
            // Don't show error toast on connection failure - it's handled silently
        });
    }

    setupNotificationEvents() {
        // Track processed event IDs to prevent duplicates
        this.processedEventIds = new Set();
        const maxEventHistory = 100;

        // All notification event types - must match broadcastAs() from ShouldBroadcast
        const eventTypes = [
            // Appointment events (broadcastAs names)
            'appointment-booked',
            'appointment-status-changed',
            'appointment-cancelled',
            'appointment-completed',
            // Waitlist events (broadcastAs names)
            'waitlist-slot-available',
            'waitlist-auto-booked',
            'waitlist-position-update',
            'waitlist-offer-expiring',
            'waitlist-expired',
            // Clinical events
            'diagnosis-submitted',
            'review-submitted',
            'hep-exercise-reminder',
            'hep-safety-alert',
            'hep-program-generated',
            // KPI events (broadcastAs is 'alert.triggered')
            'alert.triggered',
            // Clinical alert (broadcastAs is 'clinical.alert.triggered')
            'clinical.alert.triggered',
            // System alert (broadcastAs is 'system-alert')
            'system-alert',
            // Voice events
            'voice-transcription-completed',
            'voice-performance-issue',
            // Billing events
            'invoice-created',
            'invoice-due-soon',
            'invoice-overdue',
            'invoice-reminder',
            'monthly-invoice-created',
            // Task events
            'task-overdue',
            'task-reminder',
            'urgent-task-escalation',
            // Claims & Alerts
            'high-risk-claim',
            'underpayment-alert',
            // Eligibility
            'eligibility-check-failed',
            'eligibility-expiring',
            // Kiosk
            'kiosk-offline',
            'kiosk-session-timeout',
            // Account
            'account-restricted',
            'final-warning',
            'grace-period-reminder',
            'password-reset',
            // Test
            'test-notification',
            'test-event'
        ];

        eventTypes.forEach(eventType => {
            this.channel.bind(eventType, (data) => {
                // Deduplicate using BOTH event type AND appointment ID (data.id)
                // This allows the same appointment to trigger different event types (e.g., booked then cancelled)
                // while preventing the exact same event from being processed twice
                const appointmentId = data.id || data.data?.appointment_id || 'no-id';
                const dedupKey = `${eventType}-${appointmentId}`;

                if (this.processedEventIds.has(dedupKey)) {
                    console.log(`🚫 Preventing duplicate event: ${dedupKey}`);
                    return;
                }
                this.processedEventIds.add(dedupKey);

                // Clean up old event IDs
                if (this.processedEventIds.size > maxEventHistory) {
                    const iterator = this.processedEventIds.values();
                    this.processedEventIds.delete(iterator.next().value);
                }

                console.log(`📡 ${eventType.toUpperCase()} EVENT RECEIVED:`, data);
                this.handleNotification(eventType, data);
            });
        });

        // Generic notification event from WebSocketController
        this.channel.bind('NewNotification', (data) => {
            console.log('📡 NEW NOTIFICATION EVENT RECEIVED:', data);
            this.handleNotification('generic', data);
        });
    }

    handleNotification(eventType, data) {
        const notificationConfig = this.getNotificationConfig(eventType, data);

        // Show toast notification
        this.showToast(notificationConfig);

        // Update the Alpine component's dropdown if it exists
        // This will handle adding the notification and incrementing unreadCount
        this.syncAlpineDropdown(data, notificationConfig);

        // Play sound if enabled
        if (this.soundEnabled) {
            this.playNotificationSound();
        }
    }

    /**
     * Sync with Alpine notificationDropdown component
     * Properly formats the notification to match what Alpine expects
     */
    syncAlpineDropdown(data, config) {
        // Find the Alpine component instance
        const alpineComponent = window.notificationDropdownInstance;

        // Generate unique ID upfront so it's available for both branches
        const uniqueId = `${data.type || config.type || 'notification'}-${data.id || 'no-id'}-${Date.now()}`;

        if (alpineComponent && typeof alpineComponent.handleNewNotification === 'function') {
            // The data coming from WebSocket has .title, .message directly at top level
            // Use data.title/message (raw from WebSocket) not config.title/message (formatted for toast)
            const notification = {
                id: uniqueId,
                type: data.type || config.type || 'notification',
                data: data,
                title: data.title || config.title || 'Notification',
                message: data.message || config.message || data.body || ''
            };

            console.log('🔔 Syncing to Alpine dropdown:', notification);
            alpineComponent.handleNewNotification(notification);
        } else {
            console.log('🔔 Alpine dropdown instance not found:', window.notificationDropdownInstance);
        }

        // Also dispatch a custom event for other listeners
        window.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: {
                id: uniqueId,
                type: data.type || config.type || 'notification',
                data: data,
                config: config
            }
        }));
    }

    updateNotificationBadge() {
        const badges = document.querySelectorAll('.notification-badge, [data-notification-badge], #notification-count, .notification-count');
        badges.forEach(badge => {
            const currentCount = parseInt(badge.textContent) || 0;
            badge.textContent = currentCount + 1;
            badge.style.display = 'inline-flex';

            // Add pulse animation
            badge.classList.add('notification-pulse');
            setTimeout(() => badge.classList.remove('notification-pulse'), 1000);
        });

        // Also update Alpine component's unreadCount if it exists
        const alpineComponent = window.notificationDropdownInstance;
        if (alpineComponent && typeof alpineComponent.unreadCount !== 'undefined') {
            alpineComponent.unreadCount += 1;
        }
    }

    getNotificationConfig(eventType, data) {
        // Always prefer data.title/message from the notification payload
        // This ensures the toast shows the actual notification content
        const configs = {
            'appointment-booked': {
                title: data.title || "📅 New Appointment",
                message: data.message || data.body || "A new appointment has been booked",
                type: "info",
                icon: "fas fa-calendar-plus",
                color: "#3498db"
            },
            'appointment-status-changed': {
                title: data.title || "📋 Appointment Updated",
                message: data.message || "Appointment status has been changed",
                type: "warning",
                icon: "fas fa-calendar-check",
                color: "#f39c12"
            },
            'appointment-cancelled': {
                title: data.title || "❌ Appointment Cancelled",
                message: data.message || "An appointment has been cancelled",
                type: "error",
                icon: "fas fa-calendar-times",
                color: "#e74c3c"
            },
            'appointment-completed': {
                title: data.title || "✅ Appointment Completed",
                message: data.message || "An appointment has been completed",
                type: "success",
                icon: "fas fa-check-circle",
                color: "#27ae60"
            },
            'waitlist-slot-available': {
                title: data.title || "🎯 Slot Available",
                message: data.message || "A waitlist slot is now available",
                type: "success",
                icon: "fas fa-clock",
                color: "#27ae60"
            },
            'diagnosis-submitted': {
                title: data.title || "🩺 New Diagnosis",
                message: data.message || "A new diagnosis has been submitted",
                type: "info",
                icon: "fas fa-file-medical",
                color: "#9b59b6"
            },
            'review-submitted': {
                title: data.title || "⭐ New Review",
                message: data.message || "A new review has been submitted",
                type: "success",
                icon: "fas fa-star",
                color: "#f1c40f"
            },
            'payment-failed': {
                title: "💳 Payment Issue",
                message: data.message || "Payment processing failed",
                type: "error",
                icon: "fas fa-credit-card",
                color: "#e74c3c"
            },
            'system-alert': {
                title: "🔔 System Alert",
                message: data.message || "System notification",
                type: "warning",
                icon: "fas fa-exclamation-triangle",
                color: "#f39c12"
            },
            'voice-transcription-completed': {
                title: data.title || "🎤 Transcription Ready",
                message: data.message || "Voice transcription completed",
                type: "success",
                icon: "fas fa-microphone",
                color: "#27ae60"
            },
            'voice-performance-issue': {
                title: data.title || "⚠️ Voice Assistant Issue",
                message: data.message || "Voice assistant performance issue detected",
                type: "warning",
                icon: "fas fa-exclamation-triangle",
                color: "#f39c12"
            },
            'hep-exercise-reminder': {
                title: data.title || "🏃 HEP Exercise Reminder",
                message: data.message || "Time for your exercise",
                type: "info",
                icon: "fas fa-dumbbell",
                color: "#3498db"
            },
            'hep-safety-alert': {
                title: data.title || "🚨 HEP Safety Alert",
                message: data.message || "HEP safety alert triggered",
                type: "error",
                icon: "fas fa-exclamation-circle",
                color: "#e74c3c"
            },
            'waitlist-position-update': {
                title: data.title || "📍 Waitlist Position Update",
                message: data.message || "Your waitlist position has changed",
                type: "info",
                icon: "fas fa-list-ol",
                color: "#3498db"
            },
            'waitlist-offer-expiring': {
                title: data.title || "⏰ Waitlist Offer Expiring",
                message: data.message || "Your waitlist offer is about to expire",
                type: "warning",
                icon: "fas fa-clock",
                color: "#f39c12"
            },
            'waitlist-expired': {
                title: data.title || "❌ Waitlist Expired",
                message: data.message || "Your waitlist entry has expired",
                type: "error",
                icon: "fas fa-times-circle",
                color: "#e74c3c"
            },
            'waitlist-auto-booked': {
                title: data.title || "🎉 Appointment Auto-Booked",
                message: data.message || "Your waitlisted appointment has been automatically booked",
                type: "success",
                icon: "fas fa-magic",
                color: "#27ae60"
            },
            'high-risk-claim': {
                title: data.title || "🚨 High Risk Claim Alert",
                message: data.message || "A claim has been flagged with high denial risk",
                type: "error",
                icon: "fas fa-exclamation-triangle",
                color: "#e74c3c"
            },
            'underpayment-alert': {
                title: data.title || "💰 Underpayment Alert",
                message: data.message || "A claim has been flagged for underpayment",
                type: "warning",
                icon: "fas fa-dollar-sign",
                color: "#f39c12"
            },
            'urgent-task-escalation': {
                title: data.title || "🚨 Urgent Task Alert",
                message: data.message || "An urgent task requires immediate attention",
                type: "error",
                icon: "fas fa-exclamation-circle",
                color: "#e74c3c"
            },
            'task-overdue': {
                title: data.title || "⚠️ Task Overdue",
                message: data.message || "A task is now overdue",
                type: "warning",
                icon: "fas fa-tasks",
                color: "#f39c12"
            },
            'task-reminder': {
                title: data.title || "📋 Task Reminder",
                message: data.message || "You have a task due soon",
                type: "info",
                icon: "fas fa-bell",
                color: "#3498db"
            },
            'invoice-created': {
                title: data.title || "💳 Invoice Created",
                message: data.message || "A new invoice has been created",
                type: "info",
                icon: "fas fa-file-invoice-dollar",
                color: "#3498db"
            },
            'invoice-due-soon': {
                title: data.title || "⏰ Invoice Due Soon",
                message: data.message || "An invoice is due soon",
                type: "warning",
                icon: "fas fa-clock",
                color: "#f39c12"
            },
            'invoice-overdue': {
                title: data.title || "🚨 Invoice Overdue",
                message: data.message || "An invoice is overdue",
                type: "error",
                icon: "fas fa-exclamation-circle",
                color: "#e74c3c"
            },
            'invoice-reminder': {
                title: data.title || "📋 Invoice Reminder",
                message: data.message || "Reminder about an overdue invoice",
                type: "warning",
                icon: "fas fa-bell",
                color: "#f39c12"
            },
            'monthly-invoice-created': {
                title: data.title || "📅 Monthly Invoice",
                message: data.message || "Your monthly invoice is ready",
                type: "info",
                icon: "fas fa-calendar",
                color: "#3498db"
            },
            'hep-program-generated': {
                title: data.title || "🏥 HEP Program Generated",
                message: data.message || "A Home Exercise Program has been generated",
                type: "success",
                icon: "fas fa-heartbeat",
                color: "#27ae60"
            },
            'eligibility-check-failed': {
                title: data.title || "❌ Eligibility Check Failed",
                message: data.message || "Insurance eligibility check failed",
                type: "error",
                icon: "fas fa-shield-alt",
                color: "#e74c3c"
            },
            'eligibility-expiring': {
                title: data.title || "⚠️ Eligibility Expiring",
                message: data.message || "Insurance eligibility is expiring soon",
                type: "warning",
                icon: "fas fa-clock",
                color: "#f39c12"
            },
            'system-alert': {
                title: data.title || "🔔 System Alert",
                message: data.message || "System notification",
                type: "warning",
                icon: "fas fa-exclamation-triangle",
                color: "#f39c12"
            },
            'kiosk-offline': {
                title: data.title || "⚠️ Kiosk Offline",
                message: data.message || "A kiosk is offline",
                type: "warning",
                icon: "fas fa-desktop",
                color: "#f39c12"
            },
            'kiosk-session-timeout': {
                title: data.title || "⏰ Kiosk Session Timeout",
                message: data.message || "A kiosk session has timed out",
                type: "info",
                icon: "fas fa-clock",
                color: "#3498db"
            },
            'account-restricted': {
                title: data.title || "🔒 Account Restricted",
                message: data.message || "Your account has been restricted",
                type: "error",
                icon: "fas fa-lock",
                color: "#e74c3c"
            },
            'final-warning': {
                title: data.title || "🚨 Final Warning",
                message: data.message || "Final warning - account action required",
                type: "error",
                icon: "fas fa-exclamation-circle",
                color: "#e74c3c"
            },
            'grace-period-reminder': {
                title: data.title || "⏰ Grace Period Reminder",
                message: data.message || "Your subscription grace period is active",
                type: "warning",
                icon: "fas fa-clock",
                color: "#f39c12"
            },
            'password-reset': {
                title: data.title || "🔑 Password Reset",
                message: data.message || "Password reset request received",
                type: "info",
                icon: "fas fa-key",
                color: "#3498db"
            },
            'voice-performance-alert': {
                title: data.title || "⚠️ Voice Performance Alert",
                message: data.message || "Voice assistant performance issues detected",
                type: "warning",
                icon: "fas fa-microphone",
                color: "#f39c12"
            },
            'alert.triggered': {
                title: data.title || "📊 KPI Alert",
                message: data.message || "KPI alert triggered",
                type: "warning",
                icon: "fas fa-chart-line",
                color: "#f39c12"
            },
            'clinical.alert.triggered': {
                title: data.title || "🚨 Clinical Alert",
                message: data.message || "Clinical alert triggered",
                type: "error",
                icon: "fas fa-exclamation-triangle",
                color: "#e74c3c"
            },
            'generic': {
                title: data.title || "🔔 Notification",
                message: data.message || data.body || "You have a new notification",
                type: "info",
                icon: "fas fa-bell",
                color: "#3498db"
            }
        };

        return configs[eventType] || configs['generic'];
    }

    createToastContainer() {
        if (document.getElementById('unified-toast-container')) return;
        
        this.toastContainer = document.createElement('div');
        this.toastContainer.id = 'unified-toast-container';
        this.toastContainer.className = 'unified-toast-container';
        document.body.appendChild(this.toastContainer);
    }

    showToast({ title, message, type = 'info', icon, color, duration = 5000 }) {
        // Check for duplicate toasts using time-based dedup with title+message hash
        const toastHash = `${title}|${message}`;
        const now = Date.now();
        const lastShown = this.recentToasts.get(toastHash);

        // If same toast was shown within debounce period, skip it
        if (lastShown && (now - lastShown) < this.toastDebounceMs) {
            console.log('🚫 Preventing duplicate toast (debounce):', title);
            return;
        }

        // Mark this toast as shown
        this.recentToasts.set(toastHash, now);

        // Clean up old entries to prevent memory leaks (keep only last 30 seconds)
        if (this.recentToasts.size > 50) {
            const cutoff = now - 30000;
            for (const [key, timestamp] of this.recentToasts) {
                if (timestamp < cutoff) {
                    this.recentToasts.delete(key);
                }
            }
        }

        // Prevent duplicate toasts by checking existing DOM elements
        const existingToasts = document.querySelectorAll('.unified-toast');
        for (let toast of existingToasts) {
            const existingTitle = toast.querySelector('.unified-toast-title')?.textContent;
            const existingMessage = toast.querySelector('.unified-toast-message')?.textContent;
            if (existingTitle === title && existingMessage === message) {
                console.log('🚫 Preventing duplicate toast (DOM check):', title);
                return;
            }
        }

        const toast = document.createElement('div');
        toast.className = `unified-toast unified-toast-${type}`;
        
        const iconClass = icon || this.getDefaultIcon(type);
        const bgColor = color || this.getDefaultColor(type);
        
        toast.innerHTML = `
            <div class="unified-toast-content">
                <div class="unified-toast-icon" style="background: ${bgColor}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="unified-toast-text">
                    <div class="unified-toast-title">${title}</div>
                    <div class="unified-toast-message">${message}</div>
                </div>
                <button class="unified-toast-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="unified-toast-progress" style="background: ${bgColor}"></div>
        `;

        // Add click handlers
        const closeBtn = toast.querySelector('.unified-toast-close');
        closeBtn.addEventListener('click', () => this.removeToast(toast));
        
        toast.addEventListener('click', (e) => {
            if (e.target !== closeBtn && !closeBtn.contains(e.target)) {
                this.removeToast(toast);
            }
        });

        // Add to container with animation
        this.toastContainer.appendChild(toast);
        
        // Trigger entrance animation
        requestAnimationFrame(() => {
            toast.classList.add('unified-toast-show');
        });

        // Auto remove
        const progressBar = toast.querySelector('.unified-toast-progress');
        progressBar.style.animation = `unifiedToastProgress ${duration}ms linear`;
        
        setTimeout(() => {
            if (toast.parentNode) {
                this.removeToast(toast);
            }
        }, duration);
    }

    removeToast(toast) {
        toast.classList.add('unified-toast-hide');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    getDefaultIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    getDefaultColor(type) {
        const colors = {
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        return colors[type] || colors.info;
    }

    playNotificationSound() {
        try {
            // AudioContext may be suspended due to autoplay policy - need user interaction first
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                console.log('AudioContext not available');
                return;
            }

            const audioContext = new AudioContextClass();

            // If suspended, try to resume (will work if called after user interaction)
            if (audioContext.state === 'suspended') {
                // Create and resume context on user interaction basis
                // This may fail silently if no prior interaction
                audioContext.resume().then(() => {
                    this.playBeep(audioContext);
                }).catch(() => {
                    // Audio context can't be resumed without user gesture
                    console.log('AudioContext requires user interaction first');
                });
            } else {
                this.playBeep(audioContext);
            }
        } catch (error) {
            console.log('Audio notification failed:', error);
        }
    }

    playBeep(audioContext) {
        try {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);

            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (error) {
            console.log('Beep playback failed:', error);
        }
    }

    addStyles() {
        if (document.getElementById('unified-notification-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'unified-notification-styles';
        style.textContent = `
            .unified-toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 99999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-width: 400px;
                pointer-events: none;
            }

            .unified-toast {
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                border: 1px solid rgba(0, 0, 0, 0.08);
                overflow: hidden;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: auto;
                position: relative;
                backdrop-filter: blur(10px);
                min-width: 320px;
            }

            .unified-toast-show {
                transform: translateX(0);
                opacity: 1;
            }

            .unified-toast-hide {
                transform: translateX(100%);
                opacity: 0;
            }

            .unified-toast-content {
                display: flex;
                align-items: flex-start;
                padding: 16px;
                gap: 12px;
            }

            .unified-toast-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 16px;
                flex-shrink: 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .unified-toast-text {
                flex: 1;
                min-width: 0;
            }

            .unified-toast-title {
                font-weight: 600;
                font-size: 14px;
                color: #2c3e50;
                margin-bottom: 4px;
                line-height: 1.3;
            }

            .unified-toast-message {
                font-size: 13px;
                color: #7f8c8d;
                line-height: 1.4;
                word-wrap: break-word;
            }

            .unified-toast-close {
                background: none;
                border: none;
                color: #bdc3c7;
                cursor: pointer;
                padding: 4px;
                border-radius: 6px;
                transition: all 0.2s ease;
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
            }

            .unified-toast-close:hover {
                background: #ecf0f1;
                color: #7f8c8d;
            }

            .unified-toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                width: 100%;
                transform-origin: left;
            }

            @keyframes unifiedToastProgress {
                from { transform: scaleX(1); }
                to { transform: scaleX(0); }
            }

            .notification-pulse {
                animation: notificationPulse 0.6s ease-in-out;
            }

            @keyframes notificationPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }

            /* Responsive design */
            @media (max-width: 480px) {
                .unified-toast-container {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }

                .unified-toast {
                    min-width: auto;
                }

                .unified-toast-content {
                    padding: 14px;
                }

                .unified-toast-icon {
                    width: 36px;
                    height: 36px;
                    font-size: 14px;
                }

                .unified-toast-title {
                    font-size: 13px;
                }

                .unified-toast-message {
                    font-size: 12px;
                }
            }

            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .unified-toast {
                    background: #2c3e50;
                    border-color: rgba(255, 255, 255, 0.1);
                }

                .unified-toast-title {
                    color: #ecf0f1;
                }

                .unified-toast-message {
                    color: #bdc3c7;
                }

                .unified-toast-close {
                    color: #95a5a6;
                }

                .unified-toast-close:hover {
                    background: #34495e;
                    color: #ecf0f1;
                }
            }

            /* High contrast mode */
            @media (prefers-contrast: high) {
                .unified-toast {
                    border-width: 2px;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
                }

                .unified-toast-title {
                    font-weight: 700;
                }
            }

            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .unified-toast {
                    transition: opacity 0.2s ease;
                }

                .unified-toast-show {
                    transform: none;
                }

                .unified-toast-hide {
                    transform: none;
                }

                .notification-pulse {
                    animation: none;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Public methods for external use
    testNotification() {
        this.showToast({
            title: "🧪 Test Notification",
            message: "This is a test notification to verify the system is working",
            type: "info",
            duration: 4000
        });
    }

    destroy() {
        if (this.channel) {
            this.pusher.unsubscribe(`private-App.User.${this.userId}`);
        }
        if (this.pusher) {
            this.pusher.disconnect();
        }
        if (this.toastContainer) {
            this.toastContainer.remove();
        }
    }
}

// Initialize the system when DOM is ready - with duplicate prevention
document.addEventListener('DOMContentLoaded', () => {
    if (!window.unifiedNotificationSystemInstance) {
        window.unifiedNotificationSystem = new UnifiedNotificationSystem();
    } else {
        console.log('🔔 Using existing Unified Notification System instance');
        window.unifiedNotificationSystem = window.unifiedNotificationSystemInstance;
    }
});

// Make it globally available
window.UnifiedNotificationSystem = UnifiedNotificationSystem;