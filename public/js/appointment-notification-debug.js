/**
 * Appointment Notification Debug Tool
 * Specifically designed to capture and process Laravel AppointmentBookedNotification
 */
window.appointmentNotificationDebug = {
    isActive: false,
    capturedNotifications: [],

    start() {
        if (this.isActive) {
            console.log('🔍 Appointment notification debug already active');
            return;
        }

        console.log('🏥 Starting Appointment Notification Debugging...');
        console.log('📋 Looking for: AppointmentBookedNotification broadcasts');
        this.isActive = true;
        this.capturedNotifications = [];

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) {
            console.error('❌ User ID not found');
            return;
        }

        console.log('🎯 Monitoring for user:', userId);
        console.log('📡 Expected channel: App.User.' + userId);

        // Method 1: Enhanced unified notification handler
        this.setupUnifiedNotificationHandler();

        // Method 2: Direct Laravel Echo listener
        this.setupDirectEchoListener(userId);

        // Method 3: Raw Pusher event monitoring
        this.setupRawPusherMonitoring(userId);

        console.log('✅ Appointment notification debugging started');
        console.log('📝 Now book an appointment from patient account and watch the console!');
    },

    setupUnifiedNotificationHandler() {
        console.log('🔄 Setting up unified notification handler override...');

        if (!window.unifiedNotifications) {
            console.warn('⚠️ Unified notifications not available');
            return;
        }

        // Store original handler
        const originalHandler = window.unifiedNotifications.handleNewNotification;

        // Override with debug version
        window.unifiedNotifications.handleNewNotification = (notification) => {
            console.log('🔔 [UNIFIED HANDLER] Notification received:', notification);

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
        console.log('📡 Setting up direct Echo listener...');

        if (!window.Echo) {
            console.error('❌ Echo not available');
            return;
        }

        const channelName = `App.User.${userId}`;
        const channel = window.Echo.private(channelName);

        console.log('📡 Listening on channel:', channelName);

        // Laravel's notification method
        channel.notification((notification) => {
            console.log('🔔 [ECHO DIRECT] .notification() received:', notification);
            this.analyzeNotificationStructure(notification, 'echo-notification');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'echo-notification',
                data: notification
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                console.log('🔄 Processing through unified system...');
                window.unifiedNotifications.handleNewNotification(notification);
            }
        });

        // BroadcastNotificationCreated event
        channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
            console.log('🔔 [ECHO DIRECT] BroadcastNotificationCreated:', data);
            this.analyzeNotificationStructure(data, 'broadcast-notification-created');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'broadcast-notification-created',
                data: data
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                console.log('🔄 Processing through unified system...');
                window.unifiedNotifications.handleNewNotification(data);
            }
        });

        // Specific AppointmentBookedNotification class
        channel.listen('App\\Notifications\\AppointmentBookedNotification', (data) => {
            console.log('🔔 [ECHO DIRECT] AppointmentBookedNotification class:', data);
            this.analyzeNotificationStructure(data, 'appointment-booked-class');

            this.capturedNotifications.push({
                timestamp: new Date().toISOString(),
                source: 'appointment-booked-class',
                data: data
            });

            // Process through unified system
            if (window.unifiedNotifications) {
                console.log('🔄 Processing through unified system...');
                window.unifiedNotifications.handleNewNotification(data);
            }
        });

        channel.subscribed(() => {
            console.log('✅ Direct Echo listener subscribed to:', channelName);
        });

        channel.error((error) => {
            console.error('❌ Direct Echo listener error:', error);
        });
    },

    setupRawPusherMonitoring(userId) {
        console.log('🔍 Setting up raw Pusher monitoring...');

        if (!window.Echo?.connector?.pusher) {
            console.warn('⚠️ Pusher not available');
            return;
        }

        const pusher = window.Echo.connector.pusher;
        const expectedChannel = `private-App.User.${userId}`;

        console.log('📡 Monitoring for channel:', expectedChannel);

        pusher.bind_global((eventName, data) => {
            // Only log events that might be appointment-related
            if (this.isAppointmentRelated(eventName, data) || eventName.includes(expectedChannel)) {
                console.log('📨 [RAW PUSHER] Event:', eventName, 'Data:', data);

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

                console.log('🔄 Extracted notification data:', notificationData);

                this.analyzeNotificationStructure(notificationData, 'raw-pusher-extracted');

                // Process through unified system if it looks like a notification
                if (window.unifiedNotifications && this.looksLikeNotification(notificationData)) {
                    console.log('🔄 Processing extracted data through unified system...');
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
            console.log(`📋 [${source.toUpperCase()}] No notification data`);
            return;
        }

        console.log(`📋 [${source.toUpperCase()}] Notification structure analysis:`);
        console.log('  • Type:', typeof notification);
        console.log('  • Keys:', Object.keys(notification));
        console.log('  • Has ID:', !!notification.id);
        console.log('  • Has title:', !!notification.title);
        console.log('  • Has message:', !!notification.message);
        console.log('  • Has body:', !!notification.body);
        console.log('  • Has type:', !!notification.type);
        console.log('  • Has data:', !!notification.data);

        if (notification.data && typeof notification.data === 'object') {
            console.log('  • Data keys:', Object.keys(notification.data));
        }

        // Check if it matches AppointmentBookedNotification structure
        const hasAppointmentData = notification.appointment_id ||
                                 (notification.data && notification.data.appointment_id) ||
                                 notification.type === 'appointment_booked';

        if (hasAppointmentData) {
            console.log('  🎯 MATCHES APPOINTMENT NOTIFICATION STRUCTURE!');
        }
    },

    stop() {
        this.isActive = false;
        console.log('🛑 Appointment notification debugging stopped');
        console.log(`📊 Captured ${this.capturedNotifications.length} notifications`);
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
        console.log('🗑️ Cleared captured notifications');
    },

    showSummary() {
        const total = this.capturedNotifications.length;
        const bySource = {};

        this.capturedNotifications.forEach(n => {
            bySource[n.source] = (bySource[n.source] || 0) + 1;
        });

        console.log('📊 APPOINTMENT NOTIFICATION DEBUG SUMMARY:');
        console.log(`  • Total notifications captured: ${total}`);
        console.log('  • By source:');
        Object.keys(bySource).forEach(source => {
            console.log(`    - ${source}: ${bySource[source]}`);
        });

        const appointmentNotifications = this.getAppointmentNotifications();
        console.log(`  • Appointment-specific: ${appointmentNotifications.length}`);

        if (appointmentNotifications.length === 0 && total > 0) {
            console.log('⚠️ No appointment notifications found, but other notifications were captured');
            console.log('💡 This suggests the notification system is working but appointment notifications may not be properly formatted');
        } else if (appointmentNotifications.length > 0) {
            console.log('✅ Appointment notifications were captured! Check if sound/toast worked.');
        } else {
            console.log('❌ No notifications captured at all. Check if notifications are being sent.');
        }

        return {
            total,
            bySource,
            appointmentNotifications: appointmentNotifications.length
        };
    }
};

// Add to console
console.log('🏥 Appointment Notification Debug Tool loaded!');
console.log('📋 Commands:');
console.log('  • appointmentNotificationDebug.start() - Start debugging');
console.log('  • appointmentNotificationDebug.showSummary() - Show results');
console.log('  • appointmentNotificationDebug.getCapturedNotifications() - Get all data');
