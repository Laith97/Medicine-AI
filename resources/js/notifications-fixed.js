class EnhancedNotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.echoReady = false;
        this.channel = null;
        this.activeToasts = new Set(); // Track active toasts for mobile UI management

        // Initialize when both DOM and Echo are ready
        this.waitForReady();

        // Add window resize listener for responsive positioning
        this.setupResizeHandler();
    }

    setupResizeHandler() {
        // Handle window resize for responsive toast positioning
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.updateToastPositions();
            }, 250); // Debounce resize events
        });

        // Handle orientation change for mobile devices
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.updateToastPositions();
            }, 100); // Small delay for orientation change
        });
    }

    updateToastPositions() {
        // Update positions of all active toasts when screen size changes
        this.activeToasts.forEach(toast => {
            if (toast && toast.parentNode) {
                const isMobile = window.innerWidth <= 768;
                const isSmallMobile = window.innerWidth <= 576;

                let positionStyles = '';
                if (isSmallMobile) {
                    positionStyles = `
                        bottom: 20px;
                        left: 10px;
                        right: 10px;
                        max-width: calc(100vw - 20px);
                        width: calc(100vw - 20px);
                    `;
                } else if (isMobile) {
                    positionStyles = `
                        bottom: 20px;
                        left: 20px;
                        right: 20px;
                        max-width: calc(100vw - 40px);
                    `;
                } else {
                    positionStyles = `
                        top: 20px;
                        right: 20px;
                        max-width: 350px;
                    `;
                }

                // Apply new positioning
                toast.style.cssText = toast.style.cssText.replace(/position: fixed;[^}]+/, `position: fixed; ${positionStyles}`);
            }
        });
    }

    waitForReady() {
        console.log('🔍 [DEBUG] Starting waitForReady check...');

        const checkReady = () => {
            const domReady = document.readyState === 'complete' || document.readyState === 'interactive';
            const echoExists = typeof window.Echo !== 'undefined';
            const echoConnected = echoExists && window.Echo.connector && window.Echo.connector.connection;
            const echoReady = echoConnected && window.Echo.connector.connection.state === 'connected';

            console.log('🔍 [DEBUG] Ready check - DOM:', domReady, 'Echo Exists:', echoExists, 'Echo Connected:', echoConnected, 'Echo Ready:', echoReady);
            console.log('🔍 [DEBUG] Pusher State:', echoConnected ? window.Echo.connector.pusher.connection.state : 'N/A');

            if (domReady && echoReady) {
                console.log('✅ DOM and Echo ready, initializing enhanced notifications');

                // Add a small delay to ensure the connection is fully established
                setTimeout(() => {
                    console.log('🔍 [DEBUG] Calling init() after delay...');
                    this.init();
                }, 500);
            } else {

                // If Echo is not ready but DOM is, check again in a bit
                if (domReady && !echoReady) {
                    console.log('🔍 [DEBUG] DOM ready but Echo not ready, waiting...');
                    setTimeout(checkReady, 300);
                }
                // If Echo is ready but DOM is not, wait a bit more
                else if (!domReady && echoReady) {
                    console.log('🔍 [DEBUG] Echo ready but DOM not ready, waiting...');
                    setTimeout(checkReady, 300);
                }
                // If neither is ready, wait longer
                else {
                    console.log('🔍 [DEBUG] Neither DOM nor Echo ready, waiting longer...');
                    setTimeout(checkReady, 500);
                }
            }
        };

        // Start checking immediately
        checkReady();

        // Also set up a fallback initialization in case something goes wrong
        setTimeout(() => {
            if (!this.isInitialized) {
                console.warn('⚠️ Fallback initialization triggered');
                this.init();
            }
        }, 5000);
    }

    init() {
        // Check if already initialized
        if (this.isInitialized) {
            console.log('⚠️ Enhanced notification system already initialized');
            return;
        }

        // Check if global instance already exists
        if (window.enhancedNotificationSystem && window.enhancedNotificationSystem !== this) {
            console.log('⚠️ Notification system already initialized globally');
            console.log('🔍 [DEBUG] Multiple instances detected - potential conflict');
            return;
        }

        console.log('🚀 Initializing Enhanced Notification System...');
        console.log('🔍 [DEBUG] init() called - checking for other notification scripts');

        // Check for other notification scripts that might cause conflicts
        const conflictingScripts = [
            'window.laravelNotificationCatcher',
            'window.notificationDebugger',
            'window.appointmentNotificationDebug'
        ];

        conflictingScripts.forEach(scriptName => {
            if (window[scriptName]) {
                console.log(`🔍 [DEBUG] Conflicting script found: ${scriptName}`);
            }
        });

        try {
            // Get user ID from meta tag
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (userIdMeta) {
                this.userId = userIdMeta.getAttribute('content');
            }

            // Get user role from meta tag or window object
            this.userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || window.userRole || 'user';

            // Set window.userRole if it's not already set
            if (!window.userRole) {
                window.userRole = this.userRole;
            }

            if (!this.userId) {
                console.warn('⚠️ User ID not found, notifications disabled');
                return;
            }

            // Check if user is authenticated
            if (!document.querySelector('meta[name="csrf-token"]')) {
                console.warn('⚠️ User not authenticated, notifications disabled');
                return;
            }

            // Get settings from meta tags
            this.soundEnabled = document.querySelector('meta[name="notification-sound-enabled"]')?.getAttribute('content') !== 'false';
            this.toastEnabled = document.querySelector('meta[name="notification-toast-enabled"]')?.getAttribute('content') !== 'false';

            console.log('⚙️ Settings:', {
                userId: this.userId,
                soundEnabled: this.soundEnabled,
                toastEnabled: this.toastEnabled
            });

            // Setup Echo listener
            this.setupEchoListener();

            // Load initial unread count
            this.loadUnreadCount();

            // Register global instance
            window.enhancedNotificationSystem = this;

            // Preload notification sound
            this.preloadNotificationSound();

            this.isInitialized = true;
            console.log('✅ Enhanced Notification System initialized for user:', this.userId);
        } catch (error) {
            console.error('❌ Failed to initialize notification system:', error);

            // Try again after a delay
            setTimeout(() => {
                if (!this.isInitialized) {
                    console.log('🔄 Retrying initialization...');
                    this.init();
                }
            }, 3000);
        }
    }

    setupEchoListener() {
        console.log(`🚀 Setting up enhanced Echo listener for user ${this.userId}`);
        console.log('🔍 [DEBUG] setupEchoListener called');

        // DIAGNOSTIC: Check for conflicting scripts
        console.log('🔍 [DIAGNOSTIC] Checking for conflicting notification scripts...');
        const conflictingScripts = [
            'window.laravelNotificationCatcher',
            'window.notificationDebugger',
            'window.appointmentNotificationDebug',
            'window.unifiedNotifications'
        ];

        conflictingScripts.forEach(scriptName => {
            if (window[scriptName]) {
                console.log(`⚠️ CONFLICT DETECTED: ${scriptName} is already loaded`);
                console.log(`🔍 [DEBUG] ${scriptName} type:`, typeof window[scriptName]);
            } else {
                console.log(`✅ ${scriptName} not found`);
            }
        });

        try {
            // 用户频道
            const userChannelName = `App.User.${this.userId}`;
            console.log(`📡 Connecting to user channel: ${userChannelName}`);

            // 简化频道订阅 - 直接订阅并监听事件
            console.log('🔍 [DEBUG] Creating private channel:', userChannelName);
            console.log('🔍 [DEBUG] Echo object:', window.Echo);
            console.log('🔍 [DEBUG] Echo connector:', window.Echo?.connector);
            console.log('🔍 [DEBUG] Echo connection state:', window.Echo?.connector?.connection?.state);
            console.log('🔍 [DEBUG] Pusher config key:', window.Echo?.connector?.pusher?.config?.key);

            this.userChannel = window.Echo.private(userChannelName);
            this.channel = this.userChannel; // Set the main channel reference

            console.log('🔍 [DEBUG] Created userChannel:', this.userChannel);
            console.log('🔍 [DEBUG] userChannel type:', typeof this.userChannel);
            console.log('🔍 [DEBUG] userChannel constructor:', this.userChannel?.constructor?.name);

            if (this.userChannel) {
                console.log(`✅ Connected to user channel: ${userChannelName}`);

                // Verify the subscription
                this.userChannel.subscribed(() => {
                    console.log(`🔗 Successfully subscribed to ${userChannelName}`);
                    this.showSystemNotification(`Connected to ${userChannelName}`, 'success');
                });

                // Handle subscription error
                this.userChannel.error((error) => {
                    console.error(`❌ Error subscribing to ${userChannelName}:`, error);
                    this.showSystemNotification(`Connection error: ${error.message || 'Unknown error'}`, 'error');
                });

                // PRIMARY: Laravel's standard notification broadcasts
                this.userChannel.notification((notification) => {
                    console.log('🔔 [PRIMARY] Laravel notification broadcast:', notification);
                    console.log('🔍 [DEBUG] Notification listener called, returning false');
                    this.handleNewNotification(notification, 'notification');
                    this.showSystemNotification('New notification received', 'info');
                    return false; // Explicitly return false to avoid async response issues
                });

                // Add a direct listener for the notification event
                this.userChannel.listen('App\\Events\\NotificationSent', (data) => {
                    console.log('🔔 [DIRECT] NotificationSent event:', data);
                    this.handleNewNotification(data, 'direct');
                    this.showSystemNotification('Direct notification received', 'info');
                    return false; // Explicitly return false to avoid async response issues
                });

                // SECONDARY: BroadcastNotificationCreated events
                this.userChannel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                    console.log('🔔 [SECONDARY] BroadcastNotificationCreated:', data);
                    this.handleNewNotification(data, 'broadcast_event');
                    return false; // Explicitly return false to avoid async response issues
                });

                // TERTIARY: Generic notification events
                this.userChannel.listen('.notification', (data) => {
                    console.log('🔔 [TERTIARY] Generic notification event:', data);
                    this.handleNewNotification(data, 'generic');
                    return false; // Explicitly return false to avoid async response issues
                });

                // Add a direct listener for the notification event
                this.userChannel.listen('App\\Events\\NotificationSent', (data) => {
                    console.log('🔔 [DIRECT] NotificationSent event:', data);
                    console.log('🔍 [DEBUG] NotificationSent listener called, returning false');
                    this.handleNewNotification(data, 'direct');
                    return false; // Explicitly return false to avoid async response issues
                });

                // QUATERNARY: Listen for all events on the channel for debugging
                // Note: This approach may not work with all Echo versions, we'll use a more specific approach
                console.log('🔍 [DEBUG] Checking if listenAny is available on userChannel:', this.userChannel);
                console.log('🔍 [DEBUG] userChannel methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(this.userChannel)));

                // Check if listenAny method exists (for newer Echo versions)
                if (typeof this.userChannel.listenAny === 'function') {
                    console.log('✅ Using listenAny on channel');
                    this.userChannel.listenAny((eventName, data) => {
                        console.log('🔔 [QUATERNARY] Raw event received:', eventName, data);
                        if (eventName.includes('notification') || data?.type === 'notification') {
                            this.handleNewNotification(data, 'raw');
                        }
                        return false; // Explicitly return false to avoid async response issues
                    });
                } else {
                    console.log('⚠️ listenAny not available on channel, using alternative approach');
                    // Alternative: Use Pusher's bind_global if available
                    if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                        const pusher = window.Echo.connector.pusher;
                        pusher.bind_global((eventName, data) => {
                            console.log('🔔 [GLOBAL-ALT] Pusher event received:', eventName, data);
                            if (eventName.includes('notification') || eventName.includes('App.User.' + this.userId) || (data?.type === 'notification')) {
                                this.handleNewNotification(data, 'global_alt');
                            }
                            return false;
                        });
                    }
                }

                // Add a global Pusher listener to catch all events
                if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                    const pusher = window.Echo.connector.pusher;

                    // Listen to all events on the Pusher instance
                    pusher.bind_global((eventName, data) => {
                        console.log('🔔 [GLOBAL] Pusher event received:', eventName, data);
                        console.log('🔍 [DEBUG] Second global bind listener called, returning false');

                        // Check if this is a notification event
                        if (eventName.includes('notification') ||
                            eventName.includes('App.User.') ||
                            (data && (data.type === 'notification' || data.title || data.message))) {
                            console.log('🔔 [GLOBAL] Processing notification event');
                            this.handleNewNotification(data, 'global');
                            this.showSystemNotification('Global notification received', 'info');
                        }
                        return false; // Explicitly return false to avoid async response issues
                    });

                    // Listen for connection events
                    pusher.connection.bind('connected', () => {
                        console.log('🟢 Pusher connected');
                        console.log('🔍 [DEBUG] Pusher connected event fired');
                        this.showSystemNotification('Pusher connected', 'success');
                    });

                    pusher.connection.bind('disconnected', () => {
                        console.log('🔴 Pusher disconnected');
                        console.log('🔍 [DEBUG] Pusher disconnected event fired');
                        this.showSystemNotification('Pusher disconnected', 'error');
                    });

                    pusher.connection.bind('error', (error) => {
                        console.error('❌ Pusher connection error:', error);
                        console.log('🔍 [DEBUG] Pusher error event fired:', error);
                        this.showSystemNotification(`Pusher error: ${error.message || 'Unknown error'}`, 'error');
                    });
                }

                // QUINTERNARY: Listen for all events using global listener
                if (window.Echo.connector && window.Echo.connector.socket) {
                    window.Echo.connector.socket.on('event', (data) => {
                        console.log('🔔 [QUINTERNARY] Socket event received:', data);
                        if (data.channel === userChannelName && data.event && data.event.includes('notification')) {
                            this.handleNewNotification(data.event, 'socket');
                        }
                        return false; // Explicitly return false to avoid async response issues
                    });
                }

                // 监听频道错误
                this.userChannel.error((error) => {
                    console.error(`❌ Error on user channel: ${userChannelName}`, error);
                });
            } else {
                console.error(`❌ Failed to create user channel: ${userChannelName}`);
            }

            // If user is a doctor, also listen to doctor-specific channel
            if (window.userRole === 'doctor') {
                const doctorChannelName = `doctor.${this.userId}`;
                console.log(`👨‍⚕️ Doctor-specific channel created: ${doctorChannelName}`);

                // 简化医生频道订阅
                const doctorChannel = window.Echo.private(doctorChannelName);

                if (doctorChannel) {
                    console.log(`✅ Connected to doctor channel: ${doctorChannelName}`);

                    // Listen for notifications on the doctor channel
                    doctorChannel.notification((notification) => {
                        console.log('🔔 [DOCTOR] Doctor notification received:', notification);
                        this.handleNewNotification(notification, 'doctor_notification');
                    });

                    // Listen for appointment booked notifications
                    doctorChannel.listen('appointment-booked', (data) => {
                        console.log('🔔 [DOCTOR] Appointment booked notification:', data);
                        this.handleNewNotification(data, 'doctor_appointment');
                    });

                    // Listen for Laravel broadcast notification events
                    doctorChannel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                        console.log('🔔 [DOCTOR] Laravel broadcast notification:', data);
                        this.handleNewNotification(data, 'doctor_laravel_notification');
                    });

                    // 监听频道错误
                    doctorChannel.error((error) => {
                        console.error(`❌ Error on doctor channel: ${doctorChannelName}`, error);
                    });
                } else {
                    console.error(`❌ Failed to create doctor channel: ${doctorChannelName}`);
                }

                // 监听所有频道上的通知
                window.Echo.channel('doctor.' + this.userId)
                    .listen('.notification', (data) => {
                        console.log('🔔 [DOCTOR] Wildcard notification:', data);
                        this.handleNewNotification(data, 'doctor_wildcard');
                    })
                    .error((error) => {
                        console.error(`❌ Error on doctor wildcard channel:`, error);
                    });
            }

            // 监听所有用户频道上的通知
            try {
                const userWildcardChannel = window.Echo.channel('App.User.' + this.userId);

                if (userWildcardChannel) {
                    userWildcardChannel
                        .listen('.notification', (notification) => {
                            console.log('🔔 [USER] User notification received:', notification);
                            this.handleNewNotification(notification, 'user_notification');
                        })
                        .listen('appointment-booked', (data) => {
                            console.log('🔔 [USER] Appointment booked notification:', data);
                            this.handleNewNotification(data, 'user_appointment');
                        })
                        .error((error) => {
                            console.error('❌ Error on user wildcard channel:', error);
                        });
                } else {
                    console.error('❌ Failed to create user wildcard channel');
                }
            } catch (error) {
                console.error('❌ Error creating user wildcard channel:', error);
            }

            // DEBUG: Monitor all raw Pusher events for our channel
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;
                console.log('🔌 Pusher connection state:', pusher.connection.state);

                pusher.bind_global((eventName, data) => {
                    // Use the actual user channel name instead of undefined channelName
                    const userChannelName = `App.User.${this.userId}`;
                    if (eventName.includes(`private-${userChannelName}`) || eventName.includes(userChannelName)) {
                        console.log('🔍 [RAW] Pusher event for our channel:', eventName, data);
                        console.log('🔍 [DEBUG] Global bind listener called, returning false');

                        // Try to handle raw events too
                        if (eventName.includes('notification') || eventName.includes('Notification')) {
                            this.handleNewNotification(data, 'raw');
                        }
                        return false; // Explicitly return false to avoid async response issues
                    }
                });

                // Monitor connection state changes
                pusher.connection.bind('state_change', (states) => {
                    console.log('🔄 Pusher connection state changed:', states.previous, '->', states.current);
                    console.log('🔍 [DEBUG] State change event - potential async response window');
                });

                pusher.connection.bind('connected', () => {
                    console.log('🟢 Pusher connected successfully');
                    console.log('🔍 [DEBUG] Second connected event fired');
                });

                pusher.connection.bind('disconnected', () => {
                    console.log('🔴 Pusher disconnected');
                    console.log('🔍 [DEBUG] Second disconnected event fired');
                });

                pusher.connection.bind('error', (error) => {
                    console.error('❌ Pusher connection error:', error);
                    console.log('🔍 [DEBUG] Second error event fired:', error);
                });
            }

            // Channel status handlers
            this.channel.subscribed(() => {
                // Use the actual user channel name instead of undefined channelName
                const userChannelName = `App.User.${this.userId}`;
                console.log(`✅ Successfully subscribed to channel: ${userChannelName}`);
                console.log('🔍 [DEBUG] Channel subscribed callback called');
                this.echoReady = true;

                // Verify connection
                if (window.Echo.connector && window.Echo.connector.pusher) {
                    const connectionState = window.Echo.connector.pusher.connection.state;
                    console.log(`🏓 Final connection state: ${connectionState}`);
                    console.log('🔍 [DEBUG] Pusher connection state check');

                    if (connectionState === 'connected') {
                        console.log('🎉 Real-time notifications are fully ready!');
                        this.showSystemNotification('Real-time notifications enabled', 'success');
                    } else {
                        console.warn('⚠️ Pusher connection state is not connected:', connectionState);
                    }
                }
            });

            this.channel.error((error) => {
                console.error('❌ Echo channel error:', error);
                this.echoReady = false;

                // If it's an authentication error, try to reconnect
                if (error.type === 'AuthError' || error.status === 403) {
                    console.log('🔄 Authentication error detected, attempting to reconnect...');

                    // Reinitialize Echo with a delay
                    setTimeout(() => {
                        if (window.Echo) {
                            console.log('🔄 Reconnecting Echo...');
                            window.Echo.connector.connect();
                        }
                    }, 2000);
                }
            });

        } catch (error) {
            console.error('❌ Failed to setup enhanced Echo listener:', error);
        }
    }

    handleNewNotification(notification, source = 'unknown') {
        console.log(`🔔 Processing notification from ${source}:`, notification);
        console.log('🔍 [DEBUG] Notification handling started');

        // Normalize notification data
        const normalizedNotification = this.normalizeNotification(notification);
        console.log('📝 Normalized notification:', normalizedNotification);

        // Check if we've already processed this notification
        const notificationId = normalizedNotification.id || 'notification-' + Date.now();
        const existingNotification = document.querySelector(`[data-notification-id="${notificationId}"]`);

        if (existingNotification) {
            console.log('⚠️ Notification already processed, skipping duplicate');
            return;
        }

        console.log('🔍 [DEBUG] Notification is new, proceeding with UI updates');

        // Check if we're offline and store notification locally
        if (!navigator.onLine && window.offlineNotificationManager) {
            console.log('📴 Offline detected, storing notification locally');
            window.offlineNotificationManager.storeNotificationLocally(normalizedNotification);
        }

        // Update UI
        this.updateUnreadCount(1);
        this.updateNotificationDropdown(normalizedNotification);

        // Play sound if enabled
        if (this.soundEnabled) {
            console.log('🔍 [DEBUG] Sound is enabled, attempting to play notification sound');
            this.playNotificationSound();
        } else {
            console.log('🔍 [DEBUG] Sound is disabled, skipping sound playback');
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            console.log('🔍 [DEBUG] Toast is enabled, attempting to show notification toast');
            this.showToastNotification(normalizedNotification);
        } else {
            console.log('🔍 [DEBUG] Toast is disabled, skipping toast display');
        }

        // Dispatch custom events for compatibility
        document.dispatchEvent(new CustomEvent('enhancedNotificationReceived', {
            detail: normalizedNotification
        }));

        // Also dispatch the legacy event that the dropdown component expects
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: normalizedNotification
        }));

        console.log('✅ Notification processed successfully');
    }

    normalizeNotification(notification) {
        // Handle different notification structures from Laravel
        let normalized = {
            id: null,
            type: 'notification',
            title: 'New Notification',
            message: 'You have a new notification',
            data: {},
            read_at: null,
            created_at: new Date().toISOString()
        };

        if (notification) {
            // Direct properties
            normalized.id = notification.id || 'notification-' + Date.now();
            normalized.type = notification.type || normalized.type;

            // Ensure we have a valid notification ID
            if (!normalized.id || normalized.id === 'null') {
                normalized.id = 'notification-' + Date.now();
            }

            // Title and message extraction
            normalized.title = notification.title ||
                             notification.data?.title ||
                             normalized.title;

            normalized.message = notification.message ||
                               notification.body ||
                               notification.data?.message ||
                               notification.data?.body ||
                               normalized.message;

            // Data extraction
            normalized.data = notification.data || notification;
            normalized.read_at = notification.read_at || null;
            normalized.created_at = notification.created_at || normalized.created_at;

            // Handle wrapped notifications
            if (notification.notification && typeof notification.notification === 'object') {
                const wrapped = notification.notification;
                normalized.id = wrapped.id || normalized.id;
                normalized.type = wrapped.type || normalized.type;
                normalized.title = wrapped.title || wrapped.data?.title || normalized.title;
                normalized.message = wrapped.message || wrapped.body || wrapped.data?.message || normalized.message;
                normalized.data = wrapped.data || wrapped;
            }
        }

        return normalized;
    }

    async loadUnreadCount() {
        try {
            const response = await fetch('/api/notifications');
            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count || 0;
                this.updateUnreadCountDisplay();
                console.log('📊 Loaded unread count:', this.unreadCount);
            }
        } catch (error) {
            console.error('❌ Failed to load unread count:', error);
        }
    }

    updateUnreadCount(increment = 0) {
        this.unreadCount = Math.max(0, this.unreadCount + increment);
        this.updateUnreadCountDisplay();
    }

    updateUnreadCountDisplay() {
        // Update notification badge
        const badges = document.querySelectorAll('.notification-badge, [data-notification-badge]');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });

        // Update Alpine.js components
        if (window.notificationDropdownInstance) {
            window.notificationDropdownInstance.unreadCount = this.unreadCount;
        }
    }

    updateNotificationDropdown(notification) {
        // Update Alpine.js dropdown instance directly
        if (window.notificationDropdownInstance) {
            console.log('📋 Updating Alpine.js notification dropdown');
            try {
                // Use Alpine.js $nextTick to ensure proper reactivity
                window.notificationDropdownInstance.handleNewNotification(notification);
                console.log('✅ Alpine.js dropdown updated successfully');
            } catch (error) {
                console.error('❌ Failed to update Alpine.js dropdown:', error);
                // Fallback: manually update
                window.notificationDropdownInstance.notifications.unshift({
                    id: notification.id,
                    type: notification.type,
                    data: notification,
                    read_at: null,
                    created_at: notification.created_at,
                    title: notification.title,
                    message: notification.message
                });
                window.notificationDropdownInstance.unreadCount = this.unreadCount;
            }
        } else {
            console.warn('⚠️ Alpine.js notification dropdown instance not found');
        }

        // Also update any other notification lists in the DOM
        const notificationLists = document.querySelectorAll('.notification-list, [data-notification-list]');
        notificationLists.forEach(list => {
            const notificationElement = this.createNotificationElement(notification);
            if (list.firstChild) {
                list.insertBefore(notificationElement, list.firstChild);
            } else {
                list.appendChild(notificationElement);
            }
        });
    }

    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = 'notification-item border-b border-gray-200 p-3 hover:bg-gray-50 cursor-pointer';

        // Use a unique ID if none exists
        const notificationId = notification.id || 'notification-' + Date.now();
        element.dataset.notificationId = notificationId;

        element.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900">${this.escapeHtml(notification.title)}</div>
                    <div class="text-sm text-gray-600 mt-1">${this.escapeHtml(notification.message)}</div>
                    <div class="text-xs text-gray-400 mt-1">Just now</div>
                </div>
            </div>
        `;

        return element;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    playNotificationSound() {
        console.log('🔊 Playing notification sound');
        console.log('🔍 [DEBUG] Sound playback started');

        try {
            // First try to use the preloaded sound if available
            console.log('🔍 [DEBUG] Checking for preloaded sound...');
            console.log('🔍 [DEBUG] window.notificationSound exists:', !!window.notificationSound);
            console.log('🔍 [DEBUG] window.notificationSound type:', typeof window.notificationSound);
            console.log('🔍 [DEBUG] window.notificationSound has play method:', window.notificationSound && typeof window.notificationSound.play === 'function');

            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                // Reset the audio to the beginning before playing
                console.log('🔍 [DEBUG] Resetting audio to beginning...');
                window.notificationSound.currentTime = 0;

                const playPromise = window.notificationSound.play();
                console.log('🔍 [DEBUG] Play promise:', playPromise);

                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        console.log('✅ Preloaded sound played successfully');
                    }).catch(error => {
                        console.warn('⚠️ Preloaded sound play failed:', error);
                        console.log('🔍 [DEBUG] Error details:', error);
                        this.playFallbackSound();
                    });
                } else {
                    console.log('⚠️ Play promise undefined, trying fallback');
                    this.playFallbackSound();
                }
            } else {
                console.log('⚠️ Notification sound not available, trying fallback');
                console.log('🔍 [DEBUG] window.notificationSound:', window.notificationSound);
                this.playFallbackSound();
            }
        } catch (error) {
            console.error('❌ Sound error:', error);
            console.log('🔍 [DEBUG] Error details:', error);
            this.playFallbackSound();
        }
    }

    playFallbackSound() {
        console.log('🔊 Trying to play fallback notification sound');

        try {
            // Try multiple sound files
            const soundFiles = [
                '/sounds/notification.mp3',
                '/sounds/notification.ogg',
                '/sounds/notification.wav',
                'https://assets.mixkit.co/sfx/preview/mixkit-alarm-digital-clock-beep-989.mp3'
            ];

            let soundIndex = 0;

            const tryNextSound = () => {
                if (soundIndex >= soundFiles.length) {
                    console.error('❌ All sound files failed to play');
                    return;
                }

                const soundFile = soundFiles[soundIndex];
                console.log(`🔊 Trying sound file: ${soundFile}`);

                try {
                    const audio = new Audio(soundFile);
                    audio.volume = 0.3;

                    audio.oncanplaythrough = () => {
                        console.log(`✅ Sound file loaded: ${soundFile}`);

                        const playPromise = audio.play();

                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                console.log('✅ Fallback sound played successfully');
                            }).catch(error => {
                                console.error('❌ Fallback sound play failed:', error);
                                soundIndex++;
                                tryNextSound();
                            });
                        }
                    };

                    audio.onerror = () => {
                        console.error(`❌ Error loading sound file: ${soundFile}`);
                        soundIndex++;
                        tryNextSound();
                    };

                    audio.load();
                } catch (error) {
                    console.error('❌ Error creating audio:', error);
                    soundIndex++;
                    tryNextSound();
                }
            };

            tryNextSound();
        } catch (error) {
            console.error('❌ Fallback sound error:', error);
        }
    }

    showToastNotification(notification) {
        console.log('📋 Creating toast notification for:', notification);
        console.log('🔍 [DEBUG] Toast notification creation started');

        // Detect screen size for responsive positioning
        const isMobile = window.innerWidth <= 768;
        const isSmallMobile = window.innerWidth <= 576;

        // Calculate responsive positioning
        let positionStyles = '';
        if (isSmallMobile) {
            // Bottom positioning for very small screens
            positionStyles = `
                bottom: 20px;
                left: 10px;
                right: 10px;
                max-width: calc(100vw - 20px);
                width: calc(100vw - 20px);
            `;
        } else if (isMobile) {
            // Bottom positioning for mobile/tablet
            positionStyles = `
                bottom: 20px;
                left: 20px;
                right: 20px;
                max-width: calc(100vw - 40px);
            `;
        } else {
            // Desktop positioning (original)
            positionStyles = `
                top: 20px;
                right: 20px;
                max-width: 350px;
            `;
        }

        const toast = document.createElement('div');
        toast.className = 'enhanced-notification-toast';
        toast.style.cssText = `
            position: fixed;
            ${positionStyles}
            background: white;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: ${isSmallMobile ? '12px' : '16px'};
            z-index: 10000;
            transform: ${isMobile ? 'translateY(200px)' : 'translateX(400px)'};
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            font-size: ${isSmallMobile ? '13px' : '14px'};
            word-wrap: break-word;
            overflow-wrap: break-word;
        `;

        // Responsive icon and text sizes
        const iconSize = isSmallMobile ? '24px' : '32px';
        const iconInnerSize = isSmallMobile ? '14px' : '16px';
        const titleSize = isSmallMobile ? '13px' : '14px';
        const messageSize = isSmallMobile ? '12px' : '13px';
        const gapSize = isSmallMobile ? '8px' : '12px';

        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: ${gapSize};">
                <div style="width: ${iconSize}; height: ${iconSize}; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg style="width: ${iconInnerSize}; height: ${iconInnerSize}; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: ${titleSize}; font-weight: 600; color: #1a202c; word-break: break-word; overflow-wrap: break-word;">
                        ${this.escapeHtml(notification.title)}
                    </h4>
                    <p style="margin: 0; font-size: ${messageSize}; color: #4a5568; line-height: 1.4; word-break: break-word; overflow-wrap: break-word;">
                        ${this.escapeHtml(notification.message)}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                        style="background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0; margin-left: 8px; flex-shrink: 0;">
                    <svg style="width: ${iconInnerSize}; height: ${iconInnerSize};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

        console.log('🔍 [DEBUG] Toast element created, appending to body...');

        // Add toast to active toasts tracking
        this.activeToasts.add(toast);

        // Prevent mobile UI interference
        this.preventMobileUIInterference(toast, isMobile);

        document.body.appendChild(toast);
        console.log('🔍 [DEBUG] Toast element appended to body');

        // Animate in with responsive transform
        setTimeout(() => {
            console.log('🔍 [DEBUG] Animating toast in...');
            toast.classList.add('show');
            toast.style.transform = 'translateX(0) translateY(0)';
        }, 100);

        // Auto remove after 5 seconds with responsive animation
        const removeTimeout = setTimeout(() => {
            console.log('🔍 [DEBUG] Removing toast notification...');
            toast.classList.remove('show');
            if (isMobile) {
                toast.style.transform = 'translateY(200px)';
            } else {
                toast.style.transform = 'translateX(400px)';
            }
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                    this.activeToasts.delete(toast);
                }
            }, 300);
        }, 5000);

        // Store timeout reference for potential cleanup
        toast._removeTimeout = removeTimeout;

        console.log('📋 Toast notification displayed');
    }

    preloadNotificationSound() {
        console.log('🔊 Preloading notification sound');

        try {
            // Only try the available sound file to avoid 404 errors
            const soundFile = '/sounds/notification.mp3';
            console.log(`🔊 Trying to preload sound: ${soundFile}`);

            window.notificationSound = new Audio(soundFile);
            window.notificationSound.volume = 0.3;

            // Set up success handler
            window.notificationSound.addEventListener('canplaythrough', () => {
                console.log(`✅ Successfully preloaded sound: ${soundFile}`);
            });

            // Set up error handler
            window.notificationSound.addEventListener('error', (e) => {
                console.warn(`⚠️ Failed to preload sound: ${soundFile}`, e);
                console.log('💡 Notification will work without sound');
                window.notificationSound = null;
            });

            // Try to load the sound
            window.notificationSound.load();
        } catch (error) {
            console.error('❌ Failed to preload notification sound:', error);
            console.log('💡 Notification will work without sound');
            window.notificationSound = null;
        }
    }

    preventMobileUIInterference(toast, isMobile) {
        if (!isMobile) return;

        // Add mobile-specific classes and attributes
        toast.setAttribute('data-mobile-toast', 'true');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');

        // Ensure proper z-index for mobile
        const existingZIndex = parseInt(getComputedStyle(toast).zIndex) || 10000;
        if (existingZIndex < 1050) {
            toast.style.zIndex = '1050';
        }

        // Add viewport meta check for proper mobile scaling
        const viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) {
            const meta = document.createElement('meta');
            meta.name = 'viewport';
            meta.content = 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';
            document.head.appendChild(meta);
        }

        // Prevent body scroll when toast is active on very small screens
        if (window.innerWidth <= 576) {
            document.body.style.overflow = 'hidden';
            // Restore scroll after toast is removed
            const originalRemove = toast._removeTimeout;
            toast._removeTimeout = setTimeout(() => {
                clearTimeout(originalRemove);
                setTimeout(() => {
                    document.body.style.overflow = '';
                }, 300);
            }, 5000);
        }

        // Handle safe area insets for devices with notches
        if (CSS.supports('padding: max(0px)')) {
            const safeAreaLeft = 'max(16px, env(safe-area-inset-left))';
            const safeAreaRight = 'max(16px, env(safe-area-inset-right))';
            const safeAreaBottom = 'max(20px, env(safe-area-inset-bottom))';

            toast.style.paddingLeft = safeAreaLeft;
            toast.style.paddingRight = safeAreaRight;
            if (window.innerWidth <= 576) {
                toast.style.bottom = safeAreaBottom;
            }
        }
    }

    showSystemNotification(message, type = 'info') {
        this.showToastNotification({
            id: 'system-' + Date.now(),
            title: 'System',
            message: message,
            type: type
        });
    }

    // Handle offline notification sync
    async syncOfflineNotifications() {
        console.log('🔄 Syncing offline notifications');

        if (!window.offlineNotificationManager) {
            console.warn('⚠️ Offline notification manager not available');
            return;
        }

        try {
            const storedNotifications = await window.offlineNotificationManager.getStoredNotifications();
            const unsyncedNotifications = storedNotifications.filter(n => !n.synced);

            console.log(`📋 Found ${unsyncedNotifications.length} unsynced notifications`);

            for (const notification of unsyncedNotifications) {
                // Process each stored notification
                this.handleNewNotification(notification, 'offline-sync');

                // Mark as synced in offline storage
                if (window.offlineNotificationManager.markNotificationSynced) {
                    await window.offlineNotificationManager.markNotificationSynced(notification.id);
                }
            }

            if (unsyncedNotifications.length > 0) {
                this.showSystemNotification(`Synced ${unsyncedNotifications.length} offline notifications`, 'success');
            }

        } catch (error) {
            console.error('❌ Failed to sync offline notifications:', error);
        }
    }

    // Public methods for external use
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.updateUnreadCount(-1);
                return true;
            }
        } catch (error) {
            console.error('❌ Failed to mark notification as read:', error);
        }
        return false;
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.unreadCount = 0;
                this.updateUnreadCountDisplay();
                return true;
            }
        } catch (error) {
            console.error('❌ Failed to mark all notifications as read:', error);
        }
        return false;
    }
}

// Initialize the enhanced notification system
// Initialize only once when DOM is ready
if (!window.enhancedNotificationSystem) {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Initializing enhanced notification system on DOMContentLoaded');
        window.enhancedNotificationSystem = new EnhancedNotificationSystem();
    });

    // Also initialize if DOM is already loaded
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        console.log('🚀 Initializing enhanced notification system (DOM already ready)');
        window.enhancedNotificationSystem = new EnhancedNotificationSystem();
    }
}
