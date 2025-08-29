// Simple test script to verify Pusher connection and notification broadcasting
import Pusher from 'pusher-js';

// Pusher configuration from .env
const PUSHER_APP_KEY = 'dd2dc532e8700af37cf8';
const PUSHER_CLUSTER = 'ap2';

// Test user ID (from the test notification we sent)
const TEST_USER_ID = 1;

console.log('🧪 Starting Pusher connection test...');
console.log(`📡 Connecting to Pusher with key: ${PUSHER_APP_KEY}, cluster: ${PUSHER_CLUSTER}`);

// Create Pusher instance
const pusher = new Pusher(PUSHER_APP_KEY, {
    cluster: PUSHER_CLUSTER,
    forceTLS: true,
    enabledTransports: ['ws', 'wss']
});

console.log('🔌 Pusher instance created');

// Listen for connection events
pusher.connection.bind('connected', () => {
    console.log('🟢 Connected to Pusher successfully!');
    console.log(`🔗 Socket ID: ${pusher.connection.socket_id}`);

    // Subscribe to the user's private channel
    const channelName = `App.User.${TEST_USER_ID}`;
    console.log(`📻 Subscribing to channel: ${channelName}`);

    const channel = pusher.subscribe(channelName);

    channel.bind('pusher:subscription_succeeded', () => {
        console.log(`✅ Successfully subscribed to ${channelName}`);

        // Listen for notifications - try different event names
        channel.bind('notification', (data) => {
            console.log('🔔 NOTIFICATION RECEIVED (notification event)!');
            console.log('📦 Notification data:', JSON.stringify(data, null, 2));
        });

        channel.bind('TestNotification', (data) => {
            console.log('🔔 NOTIFICATION RECEIVED (TestNotification event)!');
            console.log('📦 Notification data:', JSON.stringify(data, null, 2));
        });

        channel.bind('App\\Notifications\\TestNotification', (data) => {
            console.log('🔔 NOTIFICATION RECEIVED (App\\Notifications\\TestNotification event)!');
            console.log('📦 Notification data:', JSON.stringify(data, null, 2));
        });

        // Also listen for any event
        channel.bind_global((event, data) => {
            console.log(`📡 GLOBAL EVENT RECEIVED: ${event}`);
            console.log('📦 Event data:', JSON.stringify(data, null, 2));
        });

        console.log('👂 Listening for notifications...');

        // Test sending another notification to verify real-time delivery
        console.log('🧪 Sending another test notification...');

        // Use fetch to send a test notification
        fetch('http://localhost:8000/notifications/test-debug', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('📤 Test notification sent:', data);

            // Wait for the notification to be processed and broadcast
            setTimeout(() => {
                console.log('⏰ Waiting for notification to be broadcast...');
            }, 2000);
        })
        .catch(error => {
            console.error('❌ Failed to send test notification:', error);
        });

    });

    channel.bind('pusher:subscription_error', (error) => {
        console.error(`❌ Failed to subscribe to ${channelName}:`, error);
    });

});

pusher.connection.bind('disconnected', () => {
    console.log('🔴 Disconnected from Pusher');
});

pusher.connection.bind('error', (error) => {
    console.error('❌ Pusher connection error:', error);
});

// Timeout after 30 seconds
setTimeout(() => {
    console.log('⏰ Test timeout reached');
    pusher.disconnect();
    process.exit(0);
}, 30000);

console.log('⏳ Connecting...');
