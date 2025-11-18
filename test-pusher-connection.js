// Simple test script to verify Pusher connection and notification broadcasting
import Pusher from 'pusher-js';

// Pusher configuration from .env
const PUSHER_APP_KEY = 'dd2dc532e8700af37cf8';
const PUSHER_CLUSTER = 'ap2';

// Test user ID (from the test notification we sent)
const TEST_USER_ID = 1;

// Create Pusher instance
const pusher = new Pusher(PUSHER_APP_KEY, {
    cluster: PUSHER_CLUSTER,
    forceTLS: true,
    enabledTransports: ['ws', 'wss']
});

// Listen for connection events
pusher.connection.bind('connected', () => {
    // Connected to Pusher successfully!

    // Subscribe to the user's private channel
    const channelName = `App.User.${TEST_USER_ID}`;
    const channel = pusher.subscribe(channelName);

    channel.bind('pusher:subscription_succeeded', () => {
        // Successfully subscribed to channel

        // Listen for notifications - try different event names
        channel.bind('notification', (data) => {
            // NOTIFICATION RECEIVED (notification event)!
            // Notification data: (data would be logged here)
        });

        channel.bind('TestNotification', (data) => {
            // NOTIFICATION RECEIVED (TestNotification event)!
            // Notification data: (data would be logged here)
        });

        channel.bind('App\\Notifications\\TestNotification', (data) => {
            // NOTIFICATION RECEIVED (App\\Notifications\\TestNotification event)!
            // Notification data: (data would be logged here)
        });

        // Also listen for any event
        channel.bind_global((event, data) => {
            // GLOBAL EVENT RECEIVED: (event would be logged here)
            // Event data: (data would be logged here)
        });

        // Listening for notifications...

        // Test sending another notification to verify real-time delivery
        // Sending another test notification...

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
            // Test notification sent: (data would be logged here)

            // Wait for the notification to be processed and broadcast
            setTimeout(() => {
                // Waiting for notification to be broadcast...
            }, 2000);
        })
        .catch(error => {
            // Failed to send test notification: (error would be logged here)
        });

    });

    channel.bind('pusher:subscription_error', (error) => {
        // Failed to subscribe to channel: (error would be logged here)
    });

});

pusher.connection.bind('disconnected', () => {
    // Disconnected from Pusher
});

pusher.connection.bind('error', (error) => {
    // Pusher connection error: (error would be logged here)
});

// Timeout after 30 seconds
setTimeout(() => {
    // Test timeout reached
    pusher.disconnect();
    process.exit(0);
}, 30000);

// Connecting...
