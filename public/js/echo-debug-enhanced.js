// Enhanced Echo Debug Logger
(function() {
    'use strict';

    console.log('🔍 Enhanced Echo Debug Logger loaded');

    function waitForEcho() {
        if (typeof window.Echo === 'undefined') {
            console.log('⏳ Waiting for Echo to load...');
            setTimeout(waitForEcho, 500);
            return;
        }

        console.log('✅ Echo detected, starting enhanced debugging');
        startEnhancedDebugging();
    }

    function startEnhancedDebugging() {
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        if (!userId) {
            console.error('❌ User ID not found in meta tag');
            return;
        }

        console.log(`🎯 Setting up enhanced debugging for user: ${userId}`);

        // Debug connection state
        const pusher = window.Echo.connector?.pusher;
        if (pusher) {
            console.log('📊 Pusher connection state:', pusher.connection.state);
            console.log('📊 Pusher socket ID:', pusher.connection.socket_id);

            // Log all connection events
            pusher.connection.bind_global((eventName, data) => {
                console.log(`🔗 Pusher connection event: ${eventName}`, data);
            });
        }

        // Try multiple channel formats
        const channelFormats = [
            `App.User.${userId}`,
            `private-App.User.${userId}`,
            `App\\User.${userId}`,
            `private-App\\User.${userId}`
        ];

        channelFormats.forEach(channelName => {
            console.log(`📡 Testing channel format: ${channelName}`);

            try {
                const channel = window.Echo.private(channelName);

                // Multiple event listeners
                channel.notification((notification) => {
                    console.log(`🎉 NOTIFICATION RECEIVED on ${channelName}:`, notification);

                    // Manually trigger sound and UI updates
                    if (window.notificationSound) {
                        console.log('🔊 Playing sound manually');
                        window.notificationSound.play();
                    }

                    // Manually trigger notification system
                    if (window.notificationSystem) {
                        console.log('📢 Triggering notification system manually');
                        window.notificationSystem.handleNewNotification(notification);
                    }
                });

                channel.listen('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                    console.log(`🎉 BROADCAST EVENT RECEIVED on ${channelName}:`, data);
                });

                channel.subscribed(() => {
                    console.log(`✅ Successfully subscribed to: ${channelName}`);
                });

                channel.error((error) => {
                    console.error(`❌ Channel ${channelName} error:`, error);
                });

            } catch (error) {
                console.error(`❌ Failed to subscribe to ${channelName}:`, error);
            }
        });

        // Log all Pusher events globally
        if (window.Echo.connector?.pusher) {
            window.Echo.connector.pusher.bind_global((eventName, data) => {
                if (eventName.includes('pusher:') || eventName.includes('connection')) return;
                console.log(`🌍 Global Pusher event: ${eventName}`, data);
            });
        }

        // Manual test button
        setTimeout(() => {
            if (!document.getElementById('manual-echo-test')) {
                const button = document.createElement('button');
                button.id = 'manual-echo-test';
                button.textContent = '🔧 Manual Echo Test';
                button.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    z-index: 10000;
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: bold;
                `;

                button.onclick = () => {
                    console.log('🔧 Running manual Echo test...');

                    // Test notification system directly
                    const testNotification = {
                        id: 'manual-test-' + Date.now(),
                        type: 'test',
                        title: 'Manual Test Notification',
                        message: 'This is a manual test',
                        body: 'This is a manual test notification',
                        data: { test: true }
                    };

                    if (window.notificationSystem) {
                        window.notificationSystem.handleNewNotification(testNotification);
                    }

                    if (window.notificationSound) {
                        window.notificationSound.play();
                    }
                };

                document.body.appendChild(button);
            }
        }, 2000);
    }

    // Start when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForEcho);
    } else {
        waitForEcho();
    }
})();
