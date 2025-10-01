// Comprehensive Pusher connection test


// Test function to check Pusher connection
const testPusherConnection = () => {
    if (typeof window.Echo === 'undefined') {
        setTimeout(testPusherConnection, 1000);
        return;
    }

    

    if (!window.Echo.connector || !window.Echo.connector.pusher) {
        ;
        return;
    }

    const pusher = window.Echo.connector.pusher;
    
    
    
    

    // Monitor connection events
    pusher.connection.unbind('connected'); // Remove existing listeners
    pusher.connection.unbind('disconnected');
    pusher.connection.unbind('error');

    pusher.connection.bind('connected', () => {
        
        

        // Test channel subscription
        testChannelSubscription();
    });

    pusher.connection.bind('disconnected', () => {
        
    });

    pusher.connection.bind('error', (error) => {
        ;
    });

    // Log current state
    
    

    // If already connected, run tests immediately
    if (pusher.connection.state === 'connected') {
        
        testChannelSubscription();
    }
};

// Test channel subscription
const testChannelSubscription = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (!userId) {
        ;
        return;
    }

    const testChannelName = `App.User.${userId}`;
    

    try {
        const testChannel = window.Echo.private(testChannelName);

        testChannel.subscribed(() => {
            

            // Test listening for events
            testChannel.listen('test-event', (data) => {
                
            });

            // Unsubscribe after testing
            setTimeout(() => {
                testChannel.unsubscribe();
                
            }, 5000);
        });

        testChannel.error((error) => {
            ;
        });

    } catch (error) {
        ;
    }
};

// Start the test
testPusherConnection();

// Auto-test after 10 seconds as fallback
setTimeout(() => {
    if (typeof window.Echo === 'undefined') {
        ;
    } else if (window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;
        if (pusher.connection.state !== 'connected') {
            ;
        }
    }
}, 10000);
