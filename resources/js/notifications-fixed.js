// Enhanced Real-time Notification System
// Fixes all the issues with multiple notification systems

class EnhancedNotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.echoReady = false;
        this.channel = null;

        // Initialize when both DOM and Echo are ready
        this.waitForReady();
    }

    waitForReady() {
        const checkReady = () => {
            const domReady = document.readyState === 'complete' || document.readyState === 'interactive';
            const echoExists = typeof window.Echo !== 'undefined';
            const echoConnected = echoExists && window.Echo.connector && window.Echo.connector.connection;
            const echoReady = echoConnected && window.Echo.connector.connection.state === 'connected';

            if (domReady && echoReady) {
                console.log('✅ DOM and Echo ready, initializing enhanced notifications');
                
                // Add a small delay to ensure the connection is fully established
                setTimeout(() => {
                    this.init();
                }, 500);
            } else {
                console.log('⏳ Waiting for DOM and Echo...', { 
                domReady, 
                echoExists, 
                echoConnected, 
                echoReady,
                connectionState: echoConnected ? window.Echo.connector.connection.state : 'N/A'
            });
                
                // If Echo is not ready but DOM is, check again in a bit
                if (domReady && !echoReady) {
                    console.log('⏳ DOM ready but Echo not ready, waiting...');
                    setTimeout(checkReady, 300);
                } 
                // If Echo is ready but DOM is not, wait a bit more
                else if (!domReady && echoReady) {
                    console.log('⏳ Echo ready but DOM not ready, waiting...');
                    setTimeout(checkReady, 300);
                }
                // If neither is ready, wait longer
                else {
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
            return;
        }

        console.log('🚀 Initializing Enhanced Notification System...');
        
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

    setupEchoListener() {
        console.log(`🚀 Setting up enhanced Echo listener for user ${this.userId}`);

        try {
            // 用户频道
            const userChannelName = `App.User.${this.userId}`;
            console.log(`📡 Connecting to user channel: ${userChannelName}`);

            // 简化频道订阅 - 直接订阅并监听事件
            this.userChannel = window.Echo.private(userChannelName);
            this.channel = this.userChannel; // Set the main channel reference
            
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
                    this.handleNewNotification(data, 'direct');
                });
                
                // QUATERNARY: Listen for all events on the channel for debugging
                this.userChannel.listen((eventName, data) => {
                    console.log('🔔 [QUATERNARY] Raw event received:', eventName, data);
                    if (eventName.includes('notification') || data?.type === 'notification') {
                        this.handleNewNotification(data, 'raw');
                    }
                    return false; // Explicitly return false to avoid async response issues
                });
                
                // Add a global Pusher listener to catch all events
                if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                    const pusher = window.Echo.connector.pusher;
                    
                    // Listen to all events on the Pusher instance
                    pusher.bind_global((eventName, data) => {
                        console.log('🔔 [GLOBAL] Pusher event received:', eventName, data);
                        
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
                        this.showSystemNotification('Pusher connected', 'success');
                    });
                    
                    pusher.connection.bind('disconnected', () => {
                        console.log('🔴 Pusher disconnected');
                        this.showSystemNotification('Pusher disconnected', 'error');
                    });
                    
                    pusher.connection.bind('error', (error) => {
                        console.error('❌ Pusher connection error:', error);
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

                        // Try to handle raw events too
                        if (eventName.includes('notification') || eventName.includes('Notification')) {
                            this.handleNewNotification(data, 'raw');
                        }
                    }
                });

                // Monitor connection state changes
                pusher.connection.bind('state_change', (states) => {
                    console.log('🔄 Pusher connection state changed:', states.previous, '->', states.current);
                });

                pusher.connection.bind('connected', () => {
                    console.log('🟢 Pusher connected successfully');
                });

                pusher.connection.bind('disconnected', () => {
                    console.log('🔴 Pusher disconnected');
                });

                pusher.connection.bind('error', (error) => {
                    console.error('❌ Pusher connection error:', error);
                });
            }

            // Channel status handlers
            this.channel.subscribed(() => {
                // Use the actual user channel name instead of undefined channelName
                const userChannelName = `App.User.${this.userId}`;
                console.log(`✅ Successfully subscribed to channel: ${userChannelName}`);
                this.echoReady = true;

                // Verify connection
                if (window.Echo.connector && window.Echo.connector.pusher) {
                    const connectionState = window.Echo.connector.pusher.connection.state;
                    console.log(`🏓 Final connection state: ${connectionState}`);

                    if (connectionState === 'connected') {
                        console.log('🎉 Real-time notifications are fully ready!');
                        this.showSystemNotification('Real-time notifications enabled', 'success');
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

        // Update UI
        this.updateUnreadCount(1);
        this.updateNotificationDropdown(normalizedNotification);

        // Play sound if enabled
        if (this.soundEnabled) {
            this.playNotificationSound();
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            this.showToastNotification(normalizedNotification);
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

        try {
            // First try to use the preloaded sound if available
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                // Reset the audio to the beginning before playing
                window.notificationSound.currentTime = 0;
                
                const playPromise = window.notificationSound.play();
                
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        console.log('✅ Preloaded sound played successfully');
                    }).catch(error => {
                        console.warn('⚠️ Preloaded sound play failed:', error);
                        this.playFallbackSound();
                    });
                } else {
                    console.log('⚠️ Play promise undefined, trying fallback');
                    this.playFallbackSound();
                }
            } else {
                console.log('⚠️ Notification sound not available, trying fallback');
                this.playFallbackSound();
            }
        } catch (error) {
            console.error('❌ Sound error:', error);
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
        
        const toast = document.createElement('div');
        toast.className = 'enhanced-notification-toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 16px;
            max-width: 350px;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        `;

        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1a202c;">
                        ${this.escapeHtml(notification.title)}
                    </h4>
                    <p style="margin: 0; font-size: 13px; color: #4a5568; line-height: 1.4;">
                        ${this.escapeHtml(notification.message)}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                        style="background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0; margin-left: 8px;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);

        console.log('📋 Toast notification displayed');
    }

    preloadNotificationSound() {
        console.log('🔊 Preloading notification sound');
        
        try {
            // Create a new Audio instance and preload it
            window.notificationSound = new Audio('/sounds/notification.mp3');
            window.notificationSound.volume = 0.3;
            
            // Set up error handling
            window.notificationSound.onerror = () => {
                console.error('❌ Error preloading notification sound');
            };
            
            // Try to load the sound
            window.notificationSound.load();
            
            // Also create a fallback sound
            window.notificationSoundFallback = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-alarm-digital-clock-beep-989.mp3');
            window.notificationSoundFallback.volume = 0.3;
            
            // Set up event listeners
            window.notificationSound.addEventListener('canplaythrough', () => {
                console.log('✅ Notification sound preloaded successfully');
            });
            
            window.notificationSound.addEventListener('error', (e) => {
                console.error('❌ Error preloading notification sound:', e);
            });
        } catch (error) {
            console.error('❌ Failed to preload notification sound:', error);
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
