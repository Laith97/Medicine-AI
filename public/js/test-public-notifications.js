// Test with public notification channel (no auth required)
(function() {
    'use strict';

    console.log('🧪 Testing public notification channel...');

    function testPublicNotifications() {
        if (typeof window.Echo === 'undefined') {
            console.log('⏳ Waiting for Echo...');
            setTimeout(testPublicNotifications, 500);
            return;
        }

        console.log('📡 Setting up public notification test...');

        // Listen to public notification channel
        window.Echo.channel('notification-updates')
            .listen('NotificationBroadcast', (data) => {
                console.log('🎉 PUBLIC NOTIFICATION RECEIVED:', data);

                // Play sound
                if (window.notificationSound) {
                    console.log('🔊 Playing notification sound');
                    window.notificationSound.play();
                }

                // Update UI
                if (window.notificationSystem) {
                    console.log('📢 Updating notification system');
                    window.notificationSystem.handleNewNotification(data);
                }
            });

        // Also try general broadcast events
        window.Echo.channel('notification-updates')
            .listen('.notification.created', (data) => {
                console.log('🎉 NOTIFICATION CREATED EVENT:', data);
            });

        console.log('✅ Public notification listeners set up');

        // Add test button
        setTimeout(() => {
            if (!document.getElementById('test-public-notifications')) {
                const button = document.createElement('button');
                button.id = 'test-public-notifications';
                button.textContent = '🌍 Test Public Channel';
                button.style.cssText = `
                    position: fixed;
                    bottom: 70px;
                    left: 20px;
                    z-index: 10000;
                    background: #28a745;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: bold;
                `;

                button.onclick = () => {
                    console.log('🌍 Testing public channel manually...');

                    // Simulate a notification
                    const testData = {
                        id: 'public-test-' + Date.now(),
                        type: 'test',
                        title: 'Public Test Notification',
                        message: 'Testing public channel notifications',
                        body: 'This is a public channel test',
                    };

                    if (window.notificationSound) {
                        window.notificationSound.play();
                    }

                    if (window.notificationSystem) {
                        window.notificationSystem.handleNewNotification(testData);
                    }
                };

                document.body.appendChild(button);
            }
        }, 2000);
    }

    // Start when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', testPublicNotifications);
    } else {
        testPublicNotifications();
    }
})();
