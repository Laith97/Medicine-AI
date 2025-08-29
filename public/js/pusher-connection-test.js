// Comprehensive Pusher connection test
console.log('🧪 Starting comprehensive Pusher connection test...');

// Test function to check Pusher connection
const testPusherConnection = () => {
    if (typeof window.Echo === 'undefined') {
        console.log('⏳ Waiting for Echo...');
        setTimeout(testPusherConnection, 1000);
        return;
    }

    console.log('✅ Echo is available');

    if (!window.Echo.connector || !window.Echo.connector.pusher) {
        console.error('❌ Pusher connector not found');
        return;
    }

    const pusher = window.Echo.connector.pusher;
    console.log('📡 Pusher object:', pusher);
    console.log('🔑 Pusher Key:', pusher.config.key);
    console.log('🌍 Pusher Cluster:', pusher.config.cluster);
    console.log('🔒 Force TLS:', pusher.config.forceTLS);

    // Monitor connection events
    pusher.connection.unbind('connected'); // Remove existing listeners
    pusher.connection.unbind('disconnected');
    pusher.connection.unbind('error');

    pusher.connection.bind('connected', () => {
        console.log('🟢 Pusher connected successfully!');
        console.log('🔗 Connection details:', {
            state: pusher.connection.state,
            socket_id: pusher.connection.socket_id,
            url: pusher.connection.url
        });

        // Test channel subscription
        testChannelSubscription();
    });

    pusher.connection.bind('disconnected', () => {
        console.log('🔴 Pusher disconnected');
    });

    pusher.connection.bind('error', (error) => {
        console.error('❌ Pusher connection error:', error);
    });

    // Log current state
    console.log('📊 Current Pusher state:', pusher.connection.state);
    console.log('🔗 Connection URL:', pusher.connection.url);

    // If already connected, run tests immediately
    if (pusher.connection.state === 'connected') {
        console.log('🟢 Pusher already connected!');
        testChannelSubscription();
    }
};

// Test channel subscription
const testChannelSubscription = () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (!userId) {
        console.error('❌ User ID not found');
        return;
    }

    const testChannelName = `App.User.${userId}`;
    console.log(`📡 Testing channel subscription: ${testChannelName}`);

    try {
        const testChannel = window.Echo.private(testChannelName);

        testChannel.subscribed(() => {
            console.log(`✅ Successfully subscribed to ${testChannelName}`);

            // Test listening for events
            testChannel.listen('test-event', (data) => {
                console.log('🔔 Test event received:', data);
            });

            // Unsubscribe after testing
            setTimeout(() => {
                testChannel.unsubscribe();
                console.log(`🔌 Unsubscribed from ${testChannelName}`);
            }, 5000);
        });

        testChannel.error((error) => {
            console.error(`❌ Error subscribing to ${testChannelName}:`, error);
        });

    } catch (error) {
        console.error('❌ Failed to create test channel:', error);
    }
};

// Start the test
testPusherConnection();

// Auto-test after 10 seconds as fallback
setTimeout(() => {
    if (typeof window.Echo === 'undefined') {
        console.error('❌ Echo not available after 10 seconds');
    } else if (window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;
        if (pusher.connection.state !== 'connected') {
            console.error('❌ Pusher not connected after 10 seconds. Current state:', pusher.connection.state);
        }
    }
}, 10000);
