// Simple WebSocket connection test for Pusher
console.log('🧪 Starting WebSocket connection test...');

// Wait for Echo to be available
const testWebSocketConnection = () => {
    if (typeof window.Echo !== 'undefined') {
        console.log('✅ Echo is available');

        // Check Pusher connection
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;
            console.log('📡 Pusher object:', pusher);

            // Monitor connection state
            pusher.connection.bind('connected', () => {
                console.log('🟢 Pusher connected successfully!');
                console.log('🔗 Connection details:', {
                    state: pusher.connection.state,
                    socket_id: pusher.connection.socket_id,
                    key: pusher.config.key,
                    cluster: pusher.config.cluster
                });
            });

            pusher.connection.bind('disconnected', () => {
                console.log('🔴 Pusher disconnected');
            });

            pusher.connection.bind('error', (error) => {
                console.error('❌ Pusher connection error:', error);
            });

            // Log current state
            console.log('📊 Current Pusher state:', pusher.connection.state);

            // Try to create a test channel
            try {
                const testChannel = window.Echo.channel('test-channel');
                testChannel.subscribed(() => {
                    console.log('✅ Test channel subscription successful');
                    testChannel.unsubscribe();
                });

                testChannel.error((error) => {
                    console.error('❌ Test channel error:', error);
                });

            } catch (error) {
                console.error('❌ Failed to create test channel:', error);
            }

        } else {
            console.error('❌ Pusher connector not found');
        }
    } else {
        console.log('⏳ Waiting for Echo...');
        setTimeout(testWebSocketConnection, 1000);
    }
};

// Start the test
testWebSocketConnection();

// Auto-test after 5 seconds as fallback
setTimeout(() => {
    if (typeof window.Echo === 'undefined') {
        console.error('❌ Echo not available after 5 seconds');
    }
}, 5000);
