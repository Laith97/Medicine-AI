// Simple connection test for debugging notification issues


// Test function to check Echo and Pusher connection
const testConnection = () => {
    

    // Check if Echo is available
    if (typeof window.Echo === 'undefined') {
        ;
        return false;
    }

    

    // Check if connector is available
    if (!window.Echo.connector) {
        ;
        return false;
    }

    

    // Check if Pusher is available
    if (!window.Echo.connector.pusher) {
        ;
        return false;
    }

    

    // Check connection state
    const pusher = window.Echo.connector.pusher;
    const state = pusher.connection.state;

    

    if (state === 'connected') {
        
        return true;
    } else {
        `);

        // Try to connect
        
        pusher.connection.connect();

        // Wait a bit and check again
        setTimeout(() => {
            const newState = pusher.connection.state;
            

            if (newState === 'connected') {
                
            } else {
                `);
            }
        }, 2000);

        return false;
    }
};

// Test user channel subscription
const testUserChannel = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId) {
        ;
        return false;
    }

    

    try {
        const channel = window.Echo.private(`App.User.${userId}`);

        return new Promise((resolve) => {
            channel.subscribed(() => {
                
                resolve(true);
            });

            channel.error((error) => {
                ;
                resolve(false);
            });

            // Timeout after 5 seconds
            setTimeout(() => {
                ;
                resolve(false);
            }, 5000);
        });

    } catch (error) {
        ;
        return false;
    }
};

// Test notification listening
const testNotificationListening = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId) {
        ;
        return false;
    }

    

    try {
        const channel = window.Echo.private(`App.User.${userId}`);

        // Set up a temporary listener for test notifications
        const testListener = (notification) => {
            
            return true;
        };

        channel.notification(testListener);

        // Remove the listener after a short time
        setTimeout(() => {
            channel.stopListening('notification', testListener);
        }, 10000);

        
        return true;

    } catch (error) {
        ;
        return false;
    }
};

// Run all tests
const runAllTests = async () => {
    

    const results = {
        echo_available: false,
        connector_available: false,
        pusher_available: false,
        pusher_connected: false,
        user_channel: false,
        notification_listening: false
    };

    // Test basic connection
    results.echo_available = typeof window.Echo !== 'undefined';
    results.connector_available = results.echo_available && !!window.Echo.connector;
    results.pusher_available = results.connector_available && !!window.Echo.connector.pusher;

    if (results.pusher_available) {
        results.pusher_connected = window.Echo.connector.pusher.connection.state === 'connected';
    }

    // Test user channel
    if (results.pusher_connected) {
        results.user_channel = await testUserChannel();
    }

    // Test notification listening
    if (results.user_channel) {
        results.notification_listening = testNotificationListening();
    }

    // Display results
    
    
    
    
    
    
    

    // Determine overall status
    const allPassed = Object.values(results).every(result => result === true);

    if (allPassed) {
        
    } else {
        
    }

    return results;
};

// Auto-run tests when script loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runAllTests);
} else {
    runAllTests();
}

// Also provide a global function to manually run tests
window.runNotificationTests = runAllTests;
