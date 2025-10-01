/**
 * Raw Pusher Event Debugger
 * Captures ALL Pusher events to see exactly what's being sent
 */
window.pusherRawDebug = {
    isActive: false,
    capturedEvents: [],

    start() {
        if (this.isActive) {
            
            return;
        }

        
        this.isActive = true;
        this.capturedEvents = [];

        if (!window.Echo || !window.Echo.connector || !window.Echo.connector.pusher) {
            ;
            return;
        }

        const pusher = window.Echo.connector.pusher;
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        
        

        // Monitor ALL events globally
        pusher.bind_global((eventName, data) => {
            const event = {
                timestamp: new Date().toISOString(),
                eventName,
                data,
                channel: data?.channel || 'unknown'
            };

            this.capturedEvents.push(event);

            

            // Check if this might be for our user
            if (userId && (
                eventName.includes(userId) ||
                (data && JSON.stringify(data).includes(userId)) ||
                eventName.includes('private-App.User') ||
                eventName.includes('notification') ||
                eventName.includes('Notification')
            )) {
                

                // Try to process this through our notification system
                if (window.unifiedNotifications && typeof window.unifiedNotifications.handleNewNotification === 'function') {
                    
                    try {
                        window.unifiedNotifications.handleNewNotification(data);
                    } catch (error) {
                        ;
                    }
                }
            }
        });

        // Monitor connection state changes
        pusher.connection.bind('state_change', (states) => {
            
        });

        // Monitor subscription events
        pusher.connection.bind('message', (event) => {
            
        });

        
    },

    stop() {
        if (!this.isActive) {
            
            return;
        }

        
        this.isActive = false;

        // Note: We can't easily unbind global events in Pusher, so they'll keep logging
        // but we'll stop our processing

        
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
        
    }
};

// Auto-start if in debug mode
if (document.querySelector('meta[name="app-debug"]') && window.location.search.includes('pusher-debug')) {
    window.pusherRawDebug.start();
}
