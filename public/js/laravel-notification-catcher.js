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
            return;
        }

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) {
            ;
            return;
        }

        this.waitForEcho(() => {
            this.setupAllListeners(userId);
            this.isInitialized = true;
        });
    },

    waitForEcho(callback) {
        const checkEcho = () => {
            if (typeof window.Echo !== 'undefined' && window.Echo.connector && window.Echo.connector.pusher) {
                
                callback();
            } else {
                setTimeout(checkEcho, 500);
            }
        };
        checkEcho();
    },

    setupAllListeners(userId) {
        

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
        

        const channelNames = [
            `App.User.${userId}`,
            `App.Models.User.${userId}`,
            `user.${userId}`,
            `private-user.${userId}`
        ];

        channelNames.forEach(channelName => {
            try {
                const channel = window.Echo.private(channelName);
                this.channels.push({ name: channelName, channel, type: 'private' });

                // Laravel's standard notification method
                channel.notification((notification) => {
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
                        
                        this.processNotification(data, channelName, eventType);
                    });
                });

                // Catch-all listener (if available)
                if (typeof channel.listenForWhisper === 'function') {
                    channel.listenForWhisper('notification', (data) => {
                        
                        this.processNotification(data, channelName, 'whisper');
                    });
                }

                channel.subscribed(() => {
                    
                });

                channel.error((error) => {
                    ;
                });

            } catch (error) {
                ;
            }
        });
    },

    setupPublicChannels(userId) {
        

        const publicChannelNames = [
            'notifications',
            'appointments',
            `doctor.${userId}`,
            'global-notifications'
        ];

        publicChannelNames.forEach(channelName => {
            try {
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
                        // Check if this notification is for our user
                        if (this.isNotificationForUser(data, userId)) {
                            this.processNotification(data, channelName, eventType);
                        }
                    });
                });

            } catch (error) {
                ;
            }
        });
    },

    setupPresenceChannels(userId) {

        // Presence channels might be used for doctor-specific notifications
        const presenceChannelNames = [
            `doctors`,
            `online-doctors`,
            `doctor-room.${userId}`
        ];

        presenceChannelNames.forEach(channelName => {
            try {
                const channel = window.Echo.join(channelName);
                this.channels.push({ name: channelName, channel, type: 'presence' });

                channel.listen('notification', (data) => {
                    if (this.isNotificationForUser(data, userId)) {
                        this.processNotification(data, channelName, 'presence-notification');
                    }
                });

            } catch (error) {
                ;
            }
        });
    },

    setupRawPusherMonitoring(userId) {

        if (!window.Echo.connector || !window.Echo.connector.pusher) {
            ;
            return;
        }

        const pusher = window.Echo.connector.pusher;

        // Monitor all events that might contain our user ID
        pusher.bind_global((eventName, data) => {
            // Check if this event is related to our user
            if (this.isEventForUser(eventName, data, userId)) {

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

        // Send to unified notifications system if available
        if (window.unifiedNotifications && typeof window.unifiedNotifications.handleNewNotification === 'function') {
            try {
                window.unifiedNotifications.handleNewNotification(data);
                
            } catch (error) {
                ;
            }
        } else {
            ;
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
