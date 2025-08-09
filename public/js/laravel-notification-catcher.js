/**
 * Laravel Notification Catcher
 * Comprehensive listener for Laravel broadcast notifications
 * Handles multiple broadcast formats and channel types
 */
window.laravelNotificationCatcher = {
    isInitialized: false,
    channels: [],

    init() {
        if (this.isInitialized) {
            console.log('⚠️ Laravel notification catcher already initialized');
            return;
        }

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) {
            console.error('❌ User ID not found, cannot initialize notification catcher');
            return;
        }

        console.log('📡 Initializing Laravel Notification Catcher for user:', userId);

        this.waitForEcho(() => {
            this.setupAllListeners(userId);
            this.isInitialized = true;
        });
    },

    waitForEcho(callback) {
        const checkEcho = () => {
            if (typeof window.Echo !== 'undefined' && window.Echo.connector && window.Echo.connector.pusher) {
                console.log('📡 Echo ready for notification catcher');
                callback();
            } else {
                console.log('⏳ Waiting for Echo...');
                setTimeout(checkEcho, 500);
            }
        };
        checkEcho();
    },

    setupAllListeners(userId) {
        console.log('🎯 Setting up comprehensive notification listeners...');

        // Method 1: Standard private user channel
        this.setupPrivateUserChannel(userId);

        // Method 2: Public channel (if any)
        this.setupPublicChannels(userId);

        // Method 3: Presence channels (if any)
        this.setupPresenceChannels(userId);

        // Method 4: Raw Pusher monitoring
        this.setupRawPusherMonitoring(userId);
    },

    setupPrivateUserChannel(userId) {
        console.log('🔒 Setting up private user channel listeners...');

        const channelNames = [
            `App.User.${userId}`,
            `App.Models.User.${userId}`,
            `user.${userId}`,
            `private-user.${userId}`
        ];

        channelNames.forEach(channelName => {
            try {
                console.log(`📡 Subscribing to channel: ${channelName}`);
                const channel = window.Echo.private(channelName);
                this.channels.push({ name: channelName, channel, type: 'private' });

                // Laravel's standard notification method
                channel.notification((notification) => {
                    console.log(`🔔 [${channelName}] notification() received:`, notification);
                    this.processNotification(notification, channelName, 'notification()');
                });

                // Listen for various notification event types
                const eventTypes = [
                    'Illuminate\\Notifications\\Events\\BroadcastNotificationCreated',
                    'App\\Events\\AppointmentBooked',
                    'App\\Events\\NotificationSent',
                    'notification',
                    'appointment',
                    '.notification',
                    '.appointment',
                    'AppointmentBookedNotification',
                    'App\\Notifications\\AppointmentBookedNotification'
                ];

                eventTypes.forEach(eventType => {
                    channel.listen(eventType, (data) => {
                        console.log(`🔔 [${channelName}] Event "${eventType}":`, data);
                        this.processNotification(data, channelName, eventType);
                    });
                });

                // Catch-all listener (if available)
                if (typeof channel.listenForWhisper === 'function') {
                    channel.listenForWhisper('notification', (data) => {
                        console.log(`🔔 [${channelName}] Whisper notification:`, data);
                        this.processNotification(data, channelName, 'whisper');
                    });
                }

                channel.subscribed(() => {
                    console.log(`✅ Successfully subscribed to ${channelName}`);
                });

                channel.error((error) => {
                    console.error(`❌ Error on channel ${channelName}:`, error);
                });

            } catch (error) {
                console.error(`❌ Failed to setup channel ${channelName}:`, error);
            }
        });
    },

    setupPublicChannels(userId) {
        console.log('📢 Setting up public channel listeners...');

        const publicChannelNames = [
            'notifications',
            'appointments',
            `doctor.${userId}`,
            'global-notifications'
        ];

        publicChannelNames.forEach(channelName => {
            try {
                console.log(`📡 Subscribing to public channel: ${channelName}`);
                const channel = window.Echo.channel(channelName);
                this.channels.push({ name: channelName, channel, type: 'public' });

                // Listen for various events
                const eventTypes = [
                    'notification',
                    'appointment-booked',
                    'NotificationSent',
                    'AppointmentBooked'
                ];

                eventTypes.forEach(eventType => {
                    channel.listen(eventType, (data) => {
                        console.log(`🔔 [PUBLIC:${channelName}] Event "${eventType}":`, data);
                        // Check if this notification is for our user
                        if (this.isNotificationForUser(data, userId)) {
                            this.processNotification(data, channelName, eventType);
                        }
                    });
                });

            } catch (error) {
                console.error(`❌ Failed to setup public channel ${channelName}:`, error);
            }
        });
    },

    setupPresenceChannels(userId) {
        console.log('👥 Setting up presence channel listeners...');

        // Presence channels might be used for doctor-specific notifications
        const presenceChannelNames = [
            `doctors`,
            `online-doctors`,
            `doctor-room.${userId}`
        ];

        presenceChannelNames.forEach(channelName => {
            try {
                console.log(`📡 Subscribing to presence channel: ${channelName}`);
                const channel = window.Echo.join(channelName);
                this.channels.push({ name: channelName, channel, type: 'presence' });

                channel.listen('notification', (data) => {
                    console.log(`🔔 [PRESENCE:${channelName}] Notification:`, data);
                    if (this.isNotificationForUser(data, userId)) {
                        this.processNotification(data, channelName, 'presence-notification');
                    }
                });

            } catch (error) {
                console.warn(`⚠️ Failed to setup presence channel ${channelName}:`, error);
            }
        });
    },

    setupRawPusherMonitoring(userId) {
        console.log('🔍 Setting up raw Pusher monitoring...');

        if (!window.Echo.connector || !window.Echo.connector.pusher) {
            console.warn('⚠️ Pusher not available for raw monitoring');
            return;
        }

        const pusher = window.Echo.connector.pusher;

        // Monitor all events that might contain our user ID
        pusher.bind_global((eventName, data) => {
            // Check if this event is related to our user
            if (this.isEventForUser(eventName, data, userId)) {
                console.log(`🎯 [RAW PUSHER] Potential user event: ${eventName}`, data);

                // Try to extract notification data from various formats
                let notificationData = data;

                // Handle Laravel Broadcast notification format
                if (data && data.notification) {
                    notificationData = data.notification;
                } else if (data && data.data) {
                    notificationData = data.data;
                }

                this.processNotification(notificationData, 'raw-pusher', eventName);
            }
        });
    },

    isEventForUser(eventName, data, userId) {
        // Check if event name contains user ID
        if (eventName.includes(userId)) return true;

        // Check if event name is notification-related
        if (eventName.includes('notification') || eventName.includes('Notification')) return true;

        // Check if event name is appointment-related
        if (eventName.includes('appointment') || eventName.includes('Appointment')) return true;

        // Check if data contains user ID
        if (data && JSON.stringify(data).includes(userId)) return true;

        // Check for private channel events
        if (eventName.includes('private-App.User')) return true;

        return false;
    },

    isNotificationForUser(data, userId) {
        if (!data) return false;

        // Check various possible user ID fields
        const userFields = ['user_id', 'userId', 'to_user_id', 'doctor_id', 'doctorId'];
        for (const field of userFields) {
            if (data[field] == userId) return true;
            if (data.data && data.data[field] == userId) return true;
        }

        // Check if data contains user ID anywhere
        return JSON.stringify(data).includes(userId);
    },

    processNotification(data, channelName, method) {
        console.log(`🔄 Processing notification from ${channelName} via ${method}:`, data);

        // Send to unified notifications system if available
        if (window.unifiedNotifications && typeof window.unifiedNotifications.handleNewNotification === 'function') {
            try {
                window.unifiedNotifications.handleNewNotification(data);
                console.log(`✅ Notification processed successfully via unified system`);
            } catch (error) {
                console.error(`❌ Failed to process via unified system:`, error);
            }
        } else {
            console.warn('⚠️ Unified notification system not available');
        }

        // Dispatch custom event for other listeners
        document.dispatchEvent(new CustomEvent('laravelNotificationReceived', {
            detail: { data, channelName, method }
        }));
    },

    getChannels() {
        return this.channels;
    },

    getActiveChannels() {
        return this.channels.filter(ch => ch.channel);
    }
};

// Auto-initialize for authenticated users
if (document.querySelector('meta[name="user-id"]')) {
    window.laravelNotificationCatcher.init();
}
