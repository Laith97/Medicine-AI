/**
 * Enhanced Unified Notification System v2
 * Handles sound, toast, dropdown updates, and real-time updates
 * Fixed for Bootstrap dropdown and real-time Laravel notifications
 */
class UnifiedNotificationSystemV2 {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.notifications = [];
        this.sound = null;
        this.echoChannel = null;

        // Advanced Network Error Handling Properties
        this.networkConfig = {
            maxRetries: 5,
            baseDelay: 1000, // 1 second
            maxDelay: 30000, // 30 seconds
            backoffMultiplier: 2,
            timeout: 10000, // 10 seconds
            circuitBreakerThreshold: 5,
            circuitBreakerTimeout: 60000, // 1 minute
            healthCheckInterval: 30000 // 30 seconds
        };

        // Circuit Breaker State
        this.circuitBreaker = {
            failures: 0,
            state: 'CLOSED', // CLOSED, OPEN, HALF_OPEN
            lastFailureTime: null,
            nextAttemptTime: null
        };

        // Connection Health Monitoring
        this.connectionHealth = {
            isHealthy: true,
            lastHealthCheck: null,
            consecutiveFailures: 0,
            totalRequests: 0,
            successfulRequests: 0,
            averageResponseTime: 0,
            responseTimes: []
        };

        // Retry Queues
        this.retryQueue = new Map();
        this.activeRequests = new Set();

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    // ===== ADVANCED NETWORK ERROR HANDLING METHODS =====

    /**
     * Calculate exponential backoff delay with jitter
     * @param {number} attempt - Current attempt number (0-based)
     * @param {number} baseDelay - Base delay in milliseconds
     * @param {number} maxDelay - Maximum delay in milliseconds
     * @param {number} multiplier - Backoff multiplier
     * @returns {number} Delay in milliseconds
     */
    calculateBackoffDelay(attempt, baseDelay = this.networkConfig.baseDelay, maxDelay = this.networkConfig.maxDelay, multiplier = this.networkConfig.backoffMultiplier) {
        const exponentialDelay = baseDelay * Math.pow(multiplier, attempt);
        const delayWithJitter = exponentialDelay * (0.5 + Math.random() * 0.5); // Add 50% jitter
        return Math.min(delayWithJitter, maxDelay);
    }

    /**
     * Circuit Breaker implementation for Pusher connections
     * @returns {boolean} Whether the circuit breaker allows the request
     */
    canProceedWithCircuitBreaker() {
        const now = Date.now();

        switch (this.circuitBreaker.state) {
            case 'CLOSED':
                return true;

            case 'OPEN':
                if (now >= this.circuitBreaker.nextAttemptTime) {
                    this.circuitBreaker.state = 'HALF_OPEN';
                    console.log('🔄 Circuit breaker moving to HALF_OPEN state');
                    return true;
                }
                console.log('🚫 Circuit breaker is OPEN, blocking request');
                return false;

            case 'HALF_OPEN':
                return true;

            default:
                return true;
        }
    }

    /**
     * Record a successful request in the circuit breaker
     */
    recordSuccess() {
        if (this.circuitBreaker.state === 'HALF_OPEN') {
            this.circuitBreaker.state = 'CLOSED';
            this.circuitBreaker.failures = 0;
            console.log('✅ Circuit breaker reset to CLOSED state');
        }
    }

    /**
     * Record a failed request in the circuit breaker
     */
    recordFailure() {
        this.circuitBreaker.failures++;
        this.circuitBreaker.lastFailureTime = Date.now();

        if (this.circuitBreaker.failures >= this.networkConfig.circuitBreakerThreshold) {
            this.circuitBreaker.state = 'OPEN';
            this.circuitBreaker.nextAttemptTime = Date.now() + this.networkConfig.circuitBreakerTimeout;
            console.log(`🚫 Circuit breaker opened after ${this.circuitBreaker.failures} failures`);
        }
    }

    /**
     * Enhanced fetch with timeout, retry, and circuit breaker
     * @param {string} url - Request URL
     * @param {object} options - Fetch options
     * @param {string} requestId - Unique identifier for the request
     * @returns {Promise<Response>} Fetch response
     */
    async enhancedFetch(url, options = {}, requestId = null) {
        const startTime = Date.now();
        const reqId = requestId || `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

        // Check circuit breaker
        if (!this.canProceedWithCircuitBreaker()) {
            throw new Error('Circuit breaker is OPEN - request blocked');
        }

        // Prevent duplicate requests
        if (this.activeRequests.has(reqId)) {
            console.log(`⚠️ Duplicate request blocked: ${reqId}`);
            throw new Error('Duplicate request in progress');
        }

        this.activeRequests.add(reqId);
        this.connectionHealth.totalRequests++;

        try {
            const response = await this.fetchWithTimeoutAndRetry(url, options, reqId, startTime);
            this.recordSuccess();
            this.updateConnectionHealth(startTime, true);
            return response;
        } catch (error) {
            this.recordFailure();
            this.updateConnectionHealth(startTime, false);
            throw error;
        } finally {
            this.activeRequests.delete(reqId);
        }
    }

    /**
     * Fetch with timeout and retry logic
     * @param {string} url - Request URL
     * @param {object} options - Fetch options
     * @param {string} requestId - Request identifier
     * @param {number} startTime - Request start time
     * @returns {Promise<Response>} Fetch response
     */
    async fetchWithTimeoutAndRetry(url, options, requestId, startTime) {
        let lastError;

        for (let attempt = 0; attempt <= this.networkConfig.maxRetries; attempt++) {
            try {
                console.log(`🌐 Attempt ${attempt + 1}/${this.networkConfig.maxRetries + 1} for ${requestId}: ${url}`);

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), this.networkConfig.timeout);

                const response = await fetch(url, {
                    ...options,
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                // Check if response is successful
                if (response.ok) {
                    return response;
                }

                // Handle server errors that should be retried
                if (response.status >= 500 && attempt < this.networkConfig.maxRetries) {
                    console.warn(`⚠️ Server error ${response.status} for ${requestId}, will retry`);
                    lastError = new Error(`HTTP ${response.status}: ${response.statusText}`);
                } else {
                    // Client errors or final attempt - don't retry
                    return response;
                }

            } catch (error) {
                lastError = error;

                // Don't retry on client errors or if this is the last attempt
                if (error.name === 'AbortError' || attempt >= this.networkConfig.maxRetries) {
                    throw error;
                }

                console.warn(`⚠️ Network error for ${requestId}, will retry:`, error.message);
            }

            // Wait before retry (except on last attempt)
            if (attempt < this.networkConfig.maxRetries) {
                const delay = this.calculateBackoffDelay(attempt);
                console.log(`⏳ Waiting ${Math.round(delay)}ms before retry ${attempt + 1} for ${requestId}`);
                await this.delay(delay);
            }
        }

        throw lastError;
    }

    /**
     * Update connection health metrics
     * @param {number} startTime - Request start time
     * @param {boolean} success - Whether the request was successful
     */
    updateConnectionHealth(startTime, success) {
        const responseTime = Date.now() - startTime;

        // Update response times (keep last 10)
        this.connectionHealth.responseTimes.push(responseTime);
        if (this.connectionHealth.responseTimes.length > 10) {
            this.connectionHealth.responseTimes.shift();
        }

        // Calculate average response time
        this.connectionHealth.averageResponseTime =
            this.connectionHealth.responseTimes.reduce((a, b) => a + b, 0) /
            this.connectionHealth.responseTimes.length;

        if (success) {
            this.connectionHealth.successfulRequests++;
            this.connectionHealth.consecutiveFailures = 0;
        } else {
            this.connectionHealth.consecutiveFailures++;
        }

        // Update health status
        const successRate = this.connectionHealth.successfulRequests / this.connectionHealth.totalRequests;
        const isHealthy = successRate > 0.8 && this.connectionHealth.consecutiveFailures < 3;

        if (this.connectionHealth.isHealthy !== isHealthy) {
            this.connectionHealth.isHealthy = isHealthy;
            console.log(`🏥 Connection health changed: ${isHealthy ? 'HEALTHY' : 'UNHEALTHY'}`);
        }

        this.connectionHealth.lastHealthCheck = Date.now();
    }

    /**
     * Start connection health monitoring
     */
    startHealthMonitoring() {
        if (this.healthMonitorInterval) {
            clearInterval(this.healthMonitorInterval);
        }

        this.healthMonitorInterval = setInterval(() => {
            this.performHealthCheck();
        }, this.networkConfig.healthCheckInterval);

        console.log('🏥 Connection health monitoring started');
    }

    /**
     * Perform a health check
     */
    async performHealthCheck() {
        try {
            const startTime = Date.now();
            const response = await fetch('/api/health', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: AbortSignal.timeout(5000) // 5 second timeout for health checks
            });

            const responseTime = Date.now() - startTime;
            const isHealthy = response.ok;

            console.log(`🏥 Health check: ${isHealthy ? 'PASS' : 'FAIL'} (${responseTime}ms)`);

            this.updateConnectionHealth(startTime, isHealthy);

        } catch (error) {
            console.warn('🏥 Health check failed:', error.message);
            this.updateConnectionHealth(Date.now(), false);
        }
    }

    /**
     * Utility method for delays
     * @param {number} ms - Milliseconds to delay
     * @returns {Promise} Promise that resolves after the delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Enhanced Pusher connection with circuit breaker and retry
     */
    async setupEnhancedEchoConnection() {
        if (!this.canProceedWithCircuitBreaker()) {
            console.log('🚫 Skipping Echo setup - circuit breaker is OPEN');
            return false;
        }

        let attempt = 0;
        while (attempt <= this.networkConfig.maxRetries) {
            try {
                console.log(`📡 Echo connection attempt ${attempt + 1}/${this.networkConfig.maxRetries + 1}`);

                if (typeof window.Echo === 'undefined' || !window.Echo.connector) {
                    throw new Error('Echo or connector not available');
                }

                const pusher = window.Echo.connector.pusher;

                // Wait for connection
                await this.waitForPusherConnection(pusher);

                // Setup enhanced error handling
                this.setupPusherErrorHandling(pusher);

                this.recordSuccess();
                console.log('✅ Enhanced Echo connection established');
                return true;

            } catch (error) {
                console.error(`❌ Echo connection attempt ${attempt + 1} failed:`, error.message);
                this.recordFailure();

                if (attempt >= this.networkConfig.maxRetries) {
                    throw error;
                }

                const delay = this.calculateBackoffDelay(attempt);
                console.log(`⏳ Waiting ${Math.round(delay)}ms before Echo retry`);
                await this.delay(delay);
                attempt++;
            }
        }

        return false;
    }

    /**
     * Wait for Pusher connection with timeout
     * @param {object} pusher - Pusher instance
     * @returns {Promise} Promise that resolves when connected
     */
    waitForPusherConnection(pusher) {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('Pusher connection timeout'));
            }, this.networkConfig.timeout);

            const checkConnection = () => {
                if (pusher.connection.state === 'connected') {
                    clearTimeout(timeout);
                    resolve();
                } else if (pusher.connection.state === 'failed' || pusher.connection.state === 'disconnected') {
                    clearTimeout(timeout);
                    reject(new Error(`Pusher connection failed: ${pusher.connection.state}`));
                } else {
                    setTimeout(checkConnection, 100);
                }
            };

            checkConnection();
        });
    }

    /**
     * Setup enhanced Pusher error handling
     * @param {object} pusher - Pusher instance
     */
    setupPusherErrorHandling(pusher) {
        // Remove existing listeners to prevent duplicates
        pusher.connection.unbind('error');
        pusher.connection.unbind('disconnected');
        pusher.connection.unbind('failed');

        pusher.connection.bind('error', (error) => {
            console.error('❌ Pusher connection error:', error);
            this.recordFailure();
            this.handleConnectionError('pusher_error', error);
        });

        pusher.connection.bind('disconnected', () => {
            console.warn('🔌 Pusher disconnected');
            this.handleConnectionError('pusher_disconnected');
        });

        pusher.connection.bind('failed', () => {
            console.error('💥 Pusher connection failed');
            this.recordFailure();
            this.handleConnectionError('pusher_failed');
        });

        // Auto-reconnect with backoff
        pusher.connection.bind('connected', () => {
            console.log('🟢 Pusher reconnected successfully');
            this.recordSuccess();
        });
    }

    /**
     * Handle connection errors with retry logic
     * @param {string} errorType - Type of connection error
     * @param {object} error - Error object
     */
    async handleConnectionError(errorType, error = null) {
        console.log(`🚨 Handling connection error: ${errorType}`);

        // Don't retry if circuit breaker is open
        if (!this.canProceedWithCircuitBreaker()) {
            console.log('🚫 Not retrying - circuit breaker is OPEN');
            return;
        }

        // Schedule reconnection with exponential backoff
        const retryId = `reconnect_${Date.now()}`;
        const retryFunction = async () => {
            try {
                console.log(`🔄 Retrying connection after ${errorType}`);
                await this.setupEnhancedEchoConnection();
                this.retryQueue.delete(retryId);
            } catch (retryError) {
                console.error(`❌ Connection retry failed:`, retryError.message);

                // Schedule another retry if we haven't exceeded max attempts
                const currentAttempt = this.retryQueue.get(retryId)?.attempt || 0;
                if (currentAttempt < this.networkConfig.maxRetries) {
                    const delay = this.calculateBackoffDelay(currentAttempt);
                    this.retryQueue.set(retryId, {
                        attempt: currentAttempt + 1,
                        timeoutId: setTimeout(retryFunction, delay)
                    });
                } else {
                    this.retryQueue.delete(retryId);
                    console.error(`💥 Max retries exceeded for ${errorType}`);
                }
            }
        };

        const delay = this.calculateBackoffDelay(0);
        this.retryQueue.set(retryId, {
            attempt: 1,
            timeoutId: setTimeout(retryFunction, delay)
        });

        console.log(`⏳ Scheduled reconnection retry in ${Math.round(delay)}ms`);
    }

    init() {
        if (this.isInitialized) {
            console.log('⚠️ Notification system already initialized');
            return;
        }

        console.log('🚀 Initializing Enhanced Unified Notification System v2...');

        // Get user ID from meta tag
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.userId = userIdMeta.getAttribute('content');
        }

        if (!this.userId) {
            console.warn('⚠️ User ID not found, notification system disabled');
            return;
        }

        // Get settings from meta tags and localStorage
        this.soundEnabled = localStorage.getItem('notification-sound-enabled') !== 'false';
        this.toastEnabled = localStorage.getItem('notification-toast-enabled') !== 'false';
        this.volume = 0.3; // Default volume

        console.log('⚙️ Settings:', { soundEnabled: this.soundEnabled, toastEnabled: this.toastEnabled });

        // Initialize sound system
        this.initializeSound();

        // Setup event listeners
        this.setupEventListeners();

        // Load initial data
        this.loadInitialData();

        // Load user volume setting
        this.loadUserVolumeSetting();

        // Setup enhanced Echo connection with error handling
        this.setupEnhancedEchoConnection().catch(error => {
            console.error('❌ Failed to setup enhanced Echo connection:', error);
        });

        // Start connection health monitoring
        this.startHealthMonitoring();

        this.isInitialized = true;
        console.log('✅ Enhanced Unified Notification System v2 initialized for user:', this.userId);
    }

    initializeSound() {
        try {
            // Use the existing NotificationSound if available
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                this.sound = window.notificationSound;
                console.log('🔊 Using existing NotificationSound instance');
            } else {
                console.log('🔊 Creating fallback sound system');
                this.createFallbackSound();
            }
        } catch (error) {
            console.error('❌ Failed to initialize sound:', error);
            this.createFallbackSound();
        }
    }

    createFallbackSound() {
        this.sound = {
            play: () => {
                if (!this.soundEnabled) {
                    console.log('🔇 Sound disabled');
                    return Promise.resolve();
                }

                // Ensure volume is within valid range
                const safeVolume = Math.max(0, Math.min(1, this.volume || 0.3));

                try {
                    const audio = new Audio('/sounds/notification.mp3');

                    // Set volume with cross-browser compatibility
                    if (typeof audio.volume !== 'undefined') {
                        audio.volume = safeVolume;
                    }

                    // Add error handling for various browser issues
                    audio.addEventListener('error', (e) => {
                        console.warn('⚠️ Audio error:', e);
                        this.tryAlternativeSound(safeVolume);
                    });

                    return audio.play().catch((error) => {
                        console.warn('⚠️ Could not play notification sound:', error);
                        // Try alternative sound as fallback
                        return this.tryAlternativeSound(safeVolume);
                    });
                } catch (error) {
                    console.warn('⚠️ Could not create audio element:', error);
                    return this.tryAlternativeSound(safeVolume);
                }
            },
            setEnabled: (enabled) => {
                this.soundEnabled = enabled;
            }
        };
    }

    tryAlternativeSound(volume) {
        try {
            console.log('🔄 Trying alternative notification sound...');
            // Create a simple beep sound using Web Audio API if available
            if (window.AudioContext || window.webkitAudioContext) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                const audioContext = new AudioContext();

                // Create oscillator for beep sound
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                // Configure beep
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);

                gainNode.gain.setValueAtTime(volume * 0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);

                return Promise.resolve();
            } else {
                // Fallback: try the base64 encoded beep
                const beep = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmEcBzWY1/LNfS');
                if (typeof beep.volume !== 'undefined') {
                    beep.volume = volume;
                }
                return beep.play().catch(() => {
                    console.log('📢 Could not play any sound');
                    return Promise.resolve();
                });
            }
        } catch (e) {
            console.log('📢 Could not play any sound');
            return Promise.resolve();
        }
    }

    waitForEcho() {
        const checkEcho = () => {
            if (typeof window.Echo !== 'undefined' && window.Echo.connector) {
                console.log('📡 Echo ready, setting up enhanced real-time listeners');
                this.setupEchoListener();
            } else {
                console.log('⏳ Waiting for Echo...');
                setTimeout(checkEcho, 500);
            }
        };
        checkEcho();
    }

    setupEchoListener() {
        try {
            console.log('📡 Setting up enhanced Echo listeners for user:', this.userId);
            const channelName = `App.User.${this.userId}`;
            const channel = window.Echo.private(channelName);

            // Store channel reference for debugging
            this.echoChannel = channel;

            console.log('📡 Subscribing to channel:', channelName);

            // PRIMARY METHOD: Laravel's standard notification broadcasts
            channel.notification((notification) => {
                console.log('🔔 [PRIMARY] Laravel notification broadcast:', notification);
                this.handleNewNotification(notification);
            });

            // SECONDARY METHODS: Various event listeners to catch different broadcast formats

            // Listen for BroadcastNotificationCreated events
            channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                console.log('🔔 [SECONDARY] BroadcastNotificationCreated event:', data);
                this.handleNewNotification(data);
            });

            // Listen for specific notification classes
            channel.listen('App\\Notifications\\AppointmentBookedNotification', (data) => {
                console.log('🔔 [SECONDARY] AppointmentBookedNotification:', data);
                this.handleNewNotification(data);
            });

            // Listen for any notification-related events
            channel.listen('.notification', (data) => {
                console.log('🔔 [SECONDARY] Generic .notification event:', data);
                this.handleNewNotification(data);
            });

            // Channel status handlers
            channel.subscribed(() => {
                console.log(`✅ Successfully subscribed to channel: ${channelName}`);
                console.log('📡 Channel ready for real-time notifications');

                // Test connection after subscription
                setTimeout(() => {
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        const state = window.Echo.connector.pusher.connection.state;
                        console.log('🏓 Echo connection state:', state);
                        if (state === 'connected') {
                            console.log('🎉 Real-time notifications ready!');
                        }
                    }
                }, 1000);
            });

            channel.error((error) => {
                console.error('❌ Echo channel error:', error);
            });

            // DEBUG: Monitor raw Pusher events if available
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;
                console.log('🔌 Pusher connection state:', pusher.connection.state);

                // Listen to all events for our user channel
                pusher.bind_global((eventName, data) => {
                    if (eventName && eventName.includes && eventName.includes(`private-${channelName}`)) {
                        console.log('📨 [RAW PUSHER] Event for our channel:', eventName, data);
                    }
                });
            }

        } catch (error) {
            console.error('❌ Failed to setup Echo listener:', error);
        }
    }

    handleNewNotification(notification) {
        console.log('🔔 Processing new notification:', notification);
        console.log('📊 Notification keys:', Object.keys(notification || {}));
        console.log('📊 Notification type:', typeof notification);

        // Handle different notification structures from Laravel
        let normalizedNotification;

        // Method 1: Laravel BroadcastMessage format (direct from toBroadcast method)
        if (notification.id && notification.type && (notification.title || notification.message || notification.body)) {
            console.log('📝 Detected Laravel BroadcastMessage format');
            normalizedNotification = {
                id: notification.id,
                type: notification.type,
                title: notification.title || 'New Notification',
                message: notification.message || notification.body || 'You have a new notification',
                data: notification.data || {},
                read_at: null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 2: Wrapped notification (from BroadcastNotificationCreated events)
        else if (notification.notification && typeof notification.notification === 'object') {
            console.log('📝 Detected wrapped notification format');
            const wrappedNotification = notification.notification;
            normalizedNotification = {
                id: wrappedNotification.id || 'notification-' + Date.now(),
                type: wrappedNotification.type || 'notification',
                title: wrappedNotification.title || wrappedNotification.data?.title || 'New Notification',
                message: wrappedNotification.message || wrappedNotification.data?.message || wrappedNotification.body || 'You have a new notification',
                data: wrappedNotification.data || {},
                read_at: null,
                created_at: wrappedNotification.created_at || new Date().toISOString()
            };
        }
        // Method 3: Laravel database notification format (with nested data)
        else if (notification.data && typeof notification.data === 'object') {
            console.log('📝 Detected Laravel database notification format');
            normalizedNotification = {
                id: notification.id,
                type: notification.type || notification.data.type || 'notification',
                title: notification.data.title || 'New Notification',
                message: notification.data.message || notification.data.body || 'You have a new notification',
                data: notification.data,
                read_at: notification.read_at || null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 4: Direct notification format (simple structure)
        else if (notification.title || notification.message || notification.body) {
            console.log('📝 Detected direct notification format');
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: notification.type || 'notification',
                title: notification.title || 'New Notification',
                message: notification.message || notification.body || 'You have a new notification',
                data: notification.data || {},
                read_at: null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 5: Fallback for any other format
        else {
            console.log('📝 Using fallback format processing');
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: notification.type || 'notification',
                title: 'New Notification',
                message: JSON.stringify(notification).substring(0, 100) + '...',
                data: notification,
                read_at: null,
                created_at: new Date().toISOString()
            };
        }

        console.log('📝 Normalized notification:', normalizedNotification);

        // Add to notifications array (avoid duplicates)
        const existingIndex = this.notifications.findIndex(n => n.id === normalizedNotification.id);
        if (existingIndex === -1) {
            this.notifications.unshift(normalizedNotification);
            this.unreadCount += 1;
        } else {
            console.log('⚠️ Duplicate notification ignored:', normalizedNotification.id);
            return;
        }

        // Update UI immediately
        this.updateUnreadCountDisplay();
        this.updateNotificationDropdown();

        // Play sound if enabled
        if (this.soundEnabled && this.sound) {
            console.log('🔊 Playing notification sound');
            try {
                this.sound.play().then(() => {
                    console.log('✅ Sound played successfully');
                }).catch(error => {
                    console.error('❌ Failed to play sound:', error);
                });
            } catch (error) {
                console.error('❌ Sound error:', error);
            }
        } else {
            console.log('🔇 Sound disabled or not available');
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            console.log('📋 Showing toast notification');
            try {
                this.showToastNotification(normalizedNotification);
                console.log('✅ Toast shown successfully');
            } catch (error) {
                console.error('❌ Failed to show toast:', error);
            }
        } else {
            console.log('📋 Toast disabled');
        }

        // Dispatch custom event for other components
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: normalizedNotification
        }));

        console.log('✅ Notification processed successfully');
    }

    async loadUserVolumeSetting() {
        try {
            console.log('🔊 Loading user volume setting...');

            // Try to get volume from a dedicated endpoint or from user settings
            const response = await this.enhancedFetch('/api/user/settings', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            }, 'load_volume_settings');

            if (response.ok) {
                const settings = await response.json();
                if (settings && settings.notification_volume !== undefined) {
                    this.volume = parseFloat(settings.notification_volume);
                    console.log('✅ User volume loaded:', this.volume);
                }
            } else {
                console.log('⚠️ Could not load user volume setting, using default');
            }
        } catch (error) {
            console.error('❌ Failed to load user volume setting:', error);
        }
    }

    async loadInitialData() {
        try {
            console.log('📥 Loading initial notification data...');

            // Try the existing web route first with enhanced error handling
            let response = await this.enhancedFetch('/notifications/dropdown', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            }, 'load_initial_data_web');

            // If that fails, try the API route
            if (!response.ok) {
                console.log('🔄 Web route failed, trying API route...');
                response = await this.enhancedFetch('/api/notifications', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                }, 'load_initial_data_api');
            }

            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.notifications || [];
                this.updateUnreadCountDisplay();
                this.updateNotificationDropdown();
                console.log('✅ Initial data loaded:', { unread: this.unreadCount, total: this.notifications.length });
            } else {
                console.warn('⚠️ Failed to load initial data, response status:', response.status);
            }
        } catch (error) {
            console.error('❌ Failed to load initial data:', error);
            // Initialize with empty data
            this.unreadCount = 0;
            this.notifications = [];
            this.updateUnreadCountDisplay();
            this.updateNotificationDropdown();
        }
    }

    updateUnreadCountDisplay() {
        // Update all notification count badges
        const badges = document.querySelectorAll('.notification-count, #notification-count, [data-unread-count]');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'block';
                badge.classList.remove('d-none');
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
                badge.classList.add('d-none');
            }
        });
        console.log('🔢 Updated unread count display:', this.unreadCount);
    }

    updateNotificationDropdown() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) {
            console.warn('⚠️ Notifications dropdown not found');
            return;
        }

        const notificationList = dropdown.querySelector('.notification-list, #notification-list');
        if (!notificationList) {
            console.warn('⚠️ Notification list not found in dropdown');
            return;
        }

        if (this.notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                    <small>No notifications</small>
                </div>
            `;
        } else {
            notificationList.innerHTML = this.notifications.slice(0, 10).map(notification => `
                <div class="dropdown-item notification-item ${notification.read_at ? 'read' : 'unread'}"
                     data-notification-id="${notification.id}"
                     style="cursor: pointer; border-left: 3px solid ${notification.read_at ? 'transparent' : '#007bff'};">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 32px; height: 32px;">
                                <i class="bi bi-${this.getNotificationIcon(notification.type)} text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${notification.title}</div>
                            <div class="text-muted small">${notification.message}</div>
                            <div class="text-muted small">${this.formatTime(notification.created_at)}</div>
                        </div>
                        ${!notification.read_at ? '<div class="bg-primary rounded-circle ms-2" style="width: 8px; height: 8px;"></div>' : ''}
                    </div>
                </div>
            `).join('');
        }

        console.log('📋 Updated notification dropdown with', this.notifications.length, 'notifications');
    }

    showToastNotification(notification) {
        // Remove any existing toast
        const existingToast = document.querySelector('.notification-toast-container');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast container
        const toastContainer = document.createElement('div');
        toastContainer.className = 'notification-toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;

        // Create toast
        const toast = document.createElement('div');
        toast.className = 'notification-toast bg-white shadow-lg border rounded-3 p-3';
        toast.style.cssText = `
            max-width: 350px;
            pointer-events: auto;
            transform: translateX(400px);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #007bff !important;
        `;

        toast.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="bi bi-${this.getNotificationIcon(notification.type)} text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">${notification.title}</div>
                    <div class="text-muted small">${notification.message}</div>
                </div>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="this.closest('.notification-toast-container').remove()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;

        // Add click handler
        if (notification.data?.link) {
            toast.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    window.location.href = notification.data.link;
                }
            });
        }

        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toastContainer.parentNode) {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (toastContainer.parentNode) {
                        toastContainer.parentNode.removeChild(toastContainer);
                    }
                }, 300);
            }
        }, 5000);
    }

    setupEventListeners() {
        // Don't interfere with Bootstrap dropdown - just add our custom handlers

        // Mark as read functionality
        document.addEventListener('click', async (e) => {
            const notificationItem = e.target.closest('.notification-item[data-notification-id]');
            if (notificationItem && !notificationItem.classList.contains('read')) {
                const notificationId = notificationItem.dataset.notificationId;
                await this.markAsRead(notificationId);
            }
        });

        // Mark all as read button
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.mark-all-read-btn')) {
                await this.markAllAsRead();
            }
        });

        // View all notifications button
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-all-btn')) {
                window.location.href = '/notifications';
            }
        });

        console.log('✅ Event listeners setup complete');
    }

    async markAsRead(notificationId) {
        try {
            // Try the existing web route first with enhanced error handling
            let response = await this.enhancedFetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }, `mark_read_web_${notificationId}`);

            // If that fails, try the API route
            if (!response.ok) {
                console.log(`🔄 Web route failed for ${notificationId}, trying API route...`);
                response = await this.enhancedFetch(`/api/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, `mark_read_api_${notificationId}`);
            }

            if (response.ok) {
                // Update local state
                const notification = this.notifications.find(n => n.id == notificationId);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateUnreadCountDisplay();
                    this.updateNotificationDropdown();
                    console.log('✅ Notification marked as read:', notificationId);
                }
            } else {
                console.warn('⚠️ Failed to mark notification as read, status:', response.status);
            }
        } catch (error) {
            console.error('❌ Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await this.enhancedFetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }, 'mark_all_read');

            if (response.ok) {
                // Update local state
                this.notifications.forEach(notification => {
                    if (!notification.read_at) {
                        notification.read_at = new Date().toISOString();
                    }
                });
                this.unreadCount = 0;
                this.updateUnreadCountDisplay();
                this.updateNotificationDropdown();
                console.log('✅ All notifications marked as read');
            }
        } catch (error) {
            console.error('❌ Failed to mark all notifications as read:', error);
        }
    }

    getNotificationIcon(type) {
        const icons = {
            'appointment_booked': 'calendar-check',
            'appointment': 'calendar-check',
            'diagnosis': 'file-medical',
            'message': 'chat-dots',
            'system': 'gear',
            'warning': 'exclamation-triangle',
            'error': 'exclamation-circle',
            'default': 'bell'
        };
        return icons[type] || icons.default;
    }

    formatTime(timestamp) {
        try {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                return Math.floor(diff / 60000) + ' minutes ago';
            } else if (diff < 86400000) { // Less than 1 day
                return Math.floor(diff / 3600000) + ' hours ago';
            } else if (diff < 604800000) { // Less than 1 week
                return Math.floor(diff / 86400000) + ' days ago';
            } else {
                return date.toLocaleDateString();
            }
        } catch (error) {
            return 'Recently';
        }
    }

    // Public methods for external use
    enableSound(enabled = true) {
        this.soundEnabled = enabled;
        if (this.sound && typeof this.sound.setEnabled === 'function') {
            this.sound.setEnabled(enabled);
        }
        localStorage.setItem('notification-sound-enabled', enabled);
        console.log('🔊 Sound', enabled ? 'enabled' : 'disabled');
    }

    enableToast(enabled = true) {
        this.toastEnabled = enabled;
        localStorage.setItem('notification-toast-enabled', enabled);
        console.log('📋 Toast', enabled ? 'enabled' : 'disabled');
    }

    testNotification() {
        const testNotification = {
            id: 'test-' + Date.now(),
            type: 'appointment_booked',
            title: 'Test Notification',
            message: 'This is a test notification to verify the system is working correctly!',
            data: {},
            created_at: new Date().toISOString()
        };

        this.handleNewNotification(testNotification);
        console.log('🧪 Test notification sent');
    }

    // Debug method
    getSystemStatus() {
        const circuitBreakerStatus = {
            state: this.circuitBreaker.state,
            failures: this.circuitBreaker.failures,
            lastFailureTime: this.circuitBreaker.lastFailureTime,
            nextAttemptTime: this.circuitBreaker.nextAttemptTime
        };

        const connectionHealthStatus = {
            isHealthy: this.connectionHealth.isHealthy,
            lastHealthCheck: this.connectionHealth.lastHealthCheck,
            consecutiveFailures: this.connectionHealth.consecutiveFailures,
            totalRequests: this.connectionHealth.totalRequests,
            successfulRequests: this.connectionHealth.successfulRequests,
            averageResponseTime: Math.round(this.connectionHealth.averageResponseTime),
            successRate: this.connectionHealth.totalRequests > 0 ?
                Math.round((this.connectionHealth.successfulRequests / this.connectionHealth.totalRequests) * 100) / 100 : 0
        };

        return {
            initialized: this.isInitialized,
            userId: this.userId,
            soundEnabled: this.soundEnabled,
            toastEnabled: this.toastEnabled,
            unreadCount: this.unreadCount,
            totalNotifications: this.notifications.length,
            echoConnected: !!this.echoChannel,
            soundAvailable: !!this.sound,

            // Advanced Network Error Handling Status
            networkConfig: this.networkConfig,
            circuitBreaker: circuitBreakerStatus,
            connectionHealth: connectionHealthStatus,
            activeRequests: this.activeRequests.size,
            pendingRetries: this.retryQueue.size,
            healthMonitoringActive: !!this.healthMonitorInterval
        };
    }
}

// Initialize the enhanced unified system
window.UnifiedNotificationSystemV2 = UnifiedNotificationSystemV2;

// Auto-initialize for authenticated users
if (document.querySelector('meta[name="user-id"]')) {
    window.unifiedNotifications = new UnifiedNotificationSystemV2();

    // Also provide backward compatibility
    window.testNotifications = () => window.unifiedNotifications?.testNotification();
    window.toggleNotificationSound = (enabled) => window.unifiedNotifications?.enableSound(enabled);
    window.toggleNotificationToast = (enabled) => window.unifiedNotifications?.enableToast(enabled);
}
