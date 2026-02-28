// Enhanced Echo Debug Logger
(function() {
    'use strict';

    

    function waitForEcho() {
        if (typeof window.Echo === 'undefined') {
            
            setTimeout(waitForEcho, 500);
            return;
        }

        
        startEnhancedDebugging();
    }

    function startEnhancedDebugging() {
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

        if (!userId) {
            ;
            return;
        }

        

        // Debug connection state
        const pusher = window.Echo.connector?.pusher;
        if (pusher) {
            
            

            // Log all connection events
            pusher.connection.bind_global((eventName, data) => {
                
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
            

            try {
                const channel = window.Echo.private(channelName);

                // Multiple event listeners
                channel.notification((notification) => {
                    

                    // Manually trigger sound and UI updates
                    if (window.notificationSound) {
                        
                        window.notificationSound.play();
                    }

                    // Manually trigger notification system
                    if (window.notificationSystem) {
                        
                        window.notificationSystem.handleNewNotification(notification);
                    }
                });

                channel.listen('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                    
                });

                channel.subscribed(() => {
                    
                });

                channel.error((error) => {
                    ;
                });

            } catch (error) {
                ;
            }
        });

        // Log all Pusher events globally
        if (window.Echo.connector?.pusher) {
            window.Echo.connector.pusher.bind_global((eventName, data) => {
                if (eventName.includes('pusher:') || eventName.includes('connection')) return;
                
            });
        }

    }

    // Start when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForEcho);
    } else {
        waitForEcho();
    }
})();
