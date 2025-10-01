/**
 * Appointment Notification Debug Tool
 * Specifically designed to capture and process Laravel AppointmentBookedNotification
 */
window.appointmentNotificationDebug = {
    isActive: false,
    capturedNotifications: [],

    start() {
        if (this.isActive) {
            
            return;
        }

        
        
        this.isActive = true;
        this.capturedNotifications = [];

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) {
            ;
            return;
        }

        
        

        // Method 1: Enhanced unified notification handler
        this.setupUnifiedNotificationHandler();

        // Method 2: Direct Laravel Echo listener
        this.setupDirectEchoListener(userId);

        // Method 3: Raw Pusher event monitoring
        this.setupRawPusherMonitoring(userId);

        
        
    },

    setupUnifiedNotificationHandler() {
        

        if (!window.unifiedNotifications) {
            ;
            return;
        }

        // Store original handler
        const originalHandler = window.unifiedNotifications.handleNewNotification;

        // Override with debug version
        window.unifiedNotifications.handleNewNotification = (notification) => {
            

            // Log the structure
            this.analyzeNotificationStructure(notification, 'unified-handler');

            // Store for analysis
            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'unified-handler',
                data: notification
            });

            // Call original handler
            return originalHandler.call(window.unifiedNotifications, notification);
        };
    },

    setupDirectEchoListener(userId) {
        

        if (!window.Echo) {
            ;
            return;
        }

        const channelName = `App.User.${userId}`;
        const channel = window.Echo.private(channelName);

        

        // Laravel's notification method
        channel.notification((notification) => {
            this.analyzeNotificationStructure(notification, 'echo-notification');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'echo-notification',
                data: notification
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                
                window.unifiedNotifications.handleNewNotification(notification);
            }
        });

        // BroadcastNotificationCreated event
        channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
            
            this.analyzeNotificationStructure(data, 'broadcast-notification-created');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'broadcast-notification-created',
                data: data
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                
                window.unifiedNotifications.handleNewNotification(data);
            }
        });

        // Specific AppointmentBookedNotification class
        channel.listen('App\\Notifications\\AppointmentBookedNotification', (data) => {
            
            this.analyzeNotificationStructure(data, 'appointment-booked-class');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'appointment-booked-class',
                data: data
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                
                window.unifiedNotifications.handleNewNotification(data);
            }
        });

        channel.subscribed(() => {
            
        });

        channel.error((error) => {
            ;
        });
    },

    setupRawPusherMonitoring(userId) {
        

        if (!window.Echo?.connector?.pusher) {
            ;
            return;
        }

        const pusher = window.Echo.connector.pusher;
        const expectedChannel = `private-App.User.${userId}`;

        

        pusher.bind_global((eventName, data) => {
            // Only log events that might be appointment-related
            if (this.isAppointmentRelated(eventName, data) || eventName.includes(expectedChannel)) {
                

                this.capturedNotifications.push({
                    timestamp: new Date().toISOString(),
                    source: 'raw-pusher',
                    eventName: eventName,
                    data: data
                });

                // Try to extract notification from Pusher data format
                let notificationData = data;

                // Handle different Pusher data structures
                if (data?.notification) {
                    notificationData = data.notification;
                } else if (data?.data) {
                    notificationData = data.data;
                }

                

                this.analyzeNotificationStructure(notificationData, 'raw-pusher-extracted');

                // Process through unified system if it looks like a notification
                if (window.unifiedNotifications && this.looksLikeNotification(notificationData)) {
                    
                    window.unifiedNotifications.handleNewNotification(notificationData);
                }
            }
        });
    },

    isAppointmentRelated(eventName, data) {
        const appointmentKeywords = [
            'appointment', 'Appointment',
            'AppointmentBooked', 'appointment_booked',
            'notification', 'Notification',
            'BroadcastNotification'
        ];

        // Check event name
        if (appointmentKeywords.some(keyword => eventName.includes(keyword))) {
            return true;
        }

        // Check data content
        if (data && appointmentKeywords.some(keyword => JSON.stringify(data).includes(keyword))) {
            return true;
        }

        return false;
    },

    looksLikeNotification(data) {
        if (!data || typeof data !== 'object') return false;

        // Check for common notification fields
        return data.id || data.title || data.message || data.type || data.body;
    },

    analyzeNotificationStructure(notification, source) {
        if (!notification) {
            return;
        }

        
        

        if (notification.data && typeof notification.data === 'object') {
        }

        // Check if it matches AppointmentBookedNotification structure
        const hasAppointmentData = notification.appointment_id ||
                                 (notification.data && notification.data.appointment_id) ||
                                 notification.type === 'appointment_booked';

        if (hasAppointmentData) {
            
        }
    },

    stop() {
        this.isActive = false;
        
        
    },

    getCapturedNotifications() {
        return this.capturedNotifications;
    },

    getAppointmentNotifications() {
        return this.capturedNotifications.filter(n =>
            n.data?.type === 'appointment_booked' ||
            n.data?.data?.appointment_id ||
            n.eventName?.includes('Appointment')
        );
    },

    clear() {
        this.capturedNotifications = [];
        
    },

    showSummary() {
        const total = this.capturedNotifications.length;
        const bySource = {};

        this.capturedNotifications.forEach(n => {
            bySource[n.source] = (bySource[n.source] || 0) + 1;
        });

        
        
        
        Object.keys(bySource).forEach(source => {
            
        });

        const appointmentNotifications = this.getAppointmentNotifications();
        

        if (appointmentNotifications.length === 0 && total > 0) {
            
            
        } else if (appointmentNotifications.length > 0) {
            
        } else {
            
        }

        return {
            total,
            bySource,
            appointmentNotifications: appointmentNotifications.length
        };
    }
};

// Add to console
