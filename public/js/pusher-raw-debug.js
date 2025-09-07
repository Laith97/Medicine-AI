/**
 * Raw Pusher Event Debugger
 * Captures ALL Pusher events to see exactly what's being sent
 */
window.pusherRawDebug = {
    isActive: false,
    capturedEvents: [],

    start() {
        if (this.isActive) {
            console.log('🔍 Pusher debug already active');
            return;
        }

        console.log('🔍 Starting RAW Pusher event debugging...');
        this.isActive = true;
        this.capturedEvents = [];

        if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) {
            console.error('❌ Pusher not available');
            return;
        }

        const pusher = window.Echo.connector.pusher;
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        console.log('📡 Monitoring Pusher connection for user:', userId);
        console.log('📡 Connection state:', pusher.connection.state);

        // Monitor ALL events globally
        pusher.bind_global((eventName, data) => {
            const event = {
                timestamp: new Date().toISOString(),
                eventName,
                data,
                channel: data?.channel || 'unknown'
            };

            this.capturedEvents.push(event);

            console.log('🔍 [RAW PUSHER EVENT]', eventName, data);

            // Check if this might be for our user
            if (userId && (
                eventName.includes(userId) ||
                (data && JSON.stringify(data).includes(userId)) ||
                eventName.includes('private-App.User') ||
                eventName.includes('notification') ||
                eventName.includes('Notification')
            )) {
                console.log('🎯 [POTENTIAL USER EVENT]', eventName, data);

                // Try to process this through our notification system
                if (window.unifiedNotifications && typeof window.unifiedNotifications.handleNewNotification === 'function') {
                    console.log('🔄 Attempting to process through unified notifications...');
                    try {
                        window.unifiedNotifications.handleNewNotification(data);
                    } catch (error) {
                        console.error('❌ Failed to process event:', error);
                    }
                }
            }
        });

        // Monitor connection state changes
        pusher.connection.bind('state_change', (states) => {
            console.log('📡 Connection state changed:', states.previous, '->', states.current);
        });

        // Monitor subscription events
        pusher.connection.bind('message', (event) => {
            console.log('📨 Raw connection message:', event);
        });

        console.log('✅ Raw Pusher debugging started. Events will be logged to console.');
        console.log('📋 Use pusherRawDebug.getEvents() to see captured events');
        console.log('📋 Use pusherRawDebug.stop() to stop debugging');
    },

    stop() {
        if (!this.isActive) {
            console.log('🔍 Pusher debug not active');
            return;
        }

        console.log('🛑 Stopping Pusher debug...');
        this.isActive = false;

        // Note: We can't easily unbind global events in Pusher, so they'll keep logging
        // but we'll stop our processing

        console.log(`📊 Captured ${this.capturedEvents.length} events total`);
    },

    getEvents() {
        return this.capturedEvents;
    },

    getEventsByPattern(pattern) {
        return this.capturedEvents.filter(event =>
            event.eventName.includes(pattern) ||
            JSON.stringify(event.data).includes(pattern)
        );
    },

    getUserEvents() {
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (!userId) return [];

        return this.capturedEvents.filter(event =>
            event.eventName.includes(userId) ||
            JSON.stringify(event.data).includes(userId) ||
            event.eventName.includes('private-App.User') ||
            event.eventName.includes('notification') ||
            event.eventName.includes('Notification')
        );
    },

    clear() {
        this.capturedEvents = [];
        console.log('🗑️ Cleared captured events');
    }
};

// Auto-start if in debug mode
if (document.querySelector('meta[name="app-debug"]') && window.location.search.includes('pusher-debug')) {
    window.pusherRawDebug.start();
}
