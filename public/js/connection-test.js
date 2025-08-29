// Simple connection test for debugging notification issues
console.log('🧪 Starting connection test...');

// Test function to check Echo and Pusher connection
const testConnection = () => {
    console.log('🔍 Testing connection status...');

    // Check if Echo is available
    if (typeof window.Echo === 'undefined') {
        console.error('❌ Echo is not available');
        return false;
    }

    console.log('✅ Echo is available');

    // Check if connector is available
    if (!window.Echo.connector) {
        console.error('❌ Echo connector is not available');
        return false;
    }

    console.log('✅ Echo connector is available');

    // Check if Pusher is available
    if (!window.Echo.connector.pusher) {
        console.error('❌ Pusher is not available');
        return false;
    }

    console.log('✅ Pusher is available');

    // Check connection state
    const pusher = window.Echo.connector.pusher;
    const state = pusher.connection.state;

    console.log(`📊 Pusher connection state: ${state}`);

    if (state === 'connected') {
        console.log('✅ Pusher is connected');
        return true;
    } else {
        console.warn(`⚠️ Pusher is not connected (state: ${state})`);

        // Try to connect
        console.log('🔄 Attempting to connect...');
        pusher.connection.connect();

        // Wait a bit and check again
        setTimeout(() => {
            const newState = pusher.connection.state;
            console.log(`📊 Connection attempt result: ${newState}`);

            if (newState === 'connected') {
                console.log('✅ Pusher connected after retry');
            } else {
                console.error(`❌ Pusher still not connected (state: ${newState})`);
            }
        }, 2000);

        return false;
    }
};

// Test user channel subscription
const testUserChannel = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId) {
        console.error('❌ User ID not found');
        return false;
    }

    console.log(`👤 Testing user channel for user: ${userId}`);

    try {
        const channel = window.Echo.private(`App.User.${userId}`);

        return new Promise((resolve) => {
            channel.subscribed(() => {
                console.log('✅ User channel subscription successful');
                resolve(true);
            });

            channel.error((error) => {
                console.error(`❌ User channel error: ${error}`);
                resolve(false);
            });

            // Timeout after 5 seconds
            setTimeout(() => {
                console.warn('⏰ User channel subscription timed out');
                resolve(false);
            }, 5000);
        });

    } catch (error) {
        console.error(`❌ Failed to create user channel: ${error}`);
        return false;
    }
};

// Test notification listening
const testNotificationListening = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId) {
        console.error('❌ User ID not found');
        return false;
    }

    console.log(`🔔 Testing notification listening for user: ${userId}`);

    try {
        const channel = window.Echo.private(`App.User.${userId}`);

        // Set up a temporary listener for test notifications
        const testListener = (notification) => {
            console.log('🎉 Test notification received:', notification);
            return true;
        };

        channel.notification(testListener);

        // Remove the listener after a short time
        setTimeout(() => {
            channel.stopListening('notification', testListener);
        }, 10000);

        console.log('✅ Notification listener set up');
        return true;

    } catch (error) {
        console.error(`❌ Failed to set up notification listener: ${error}`);
        return false;
    }
};

// Run all tests
const runAllTests = async () => {
    console.log('🚀 Starting all connection tests...');

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
    console.log('📊 Test Results:');
    console.log('Echo Available:', results.echo_available ? '✅' : '❌');
    console.log('Connector Available:', results.connector_available ? '✅' : '❌');
    console.log('Pusher Available:', results.pusher_available ? '✅' : '❌');
    console.log('Pusher Connected:', results.pusher_connected ? '✅' : '❌');
    console.log('User Channel:', results.user_channel ? '✅' : '❌');
    console.log('Notification Listening:', results.notification_listening ? '✅' : '❌');

    // Determine overall status
    const allPassed = Object.values(results).every(result => result === true);

    if (allPassed) {
        console.log('🎉 All tests passed! Notification system should be working.');
    } else {
        console.log('⚠️ Some tests failed. Check the individual results above.');
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
