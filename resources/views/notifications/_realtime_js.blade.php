{{-- Real-time JavaScript for Laravel Echo --}}
@auth
<script>
    // User ID for private channels
    window.userId = {{ auth()->id() }};

    // Laravel Echo configuration
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Echo if available
        if (typeof Echo !== 'undefined') {
            Echo.private(`App.User.${window.userId}`)
                .notification((notification) => {
                    // Handle new notification
                    console.log('New notification received:', notification);

                    // Update notification manager if available
                    if (window.notificationManager) {
                        window.notificationManager.addNotification(notification);
                        window.notificationManager.showNotificationToast(notification);
                    }

                    // Play notification sound if enabled
                    if (window.notificationSoundEnabled) {
                        playNotificationSound();
                    }

                    // Update browser badge
                    if (window.notificationManager) {
                        window.notificationManager.updateNotificationBadge();
                    }
                });

            // Listen for notification read events
            Echo.channel('notification-updates')
                .listen('NotificationRead', (event) => {
                    console.log('Notification read:', event.notificationId);
                    if (window.notificationManager) {
                        window.notificationManager.updateNotificationReadStatus(event.notificationId);
                    }
                });

            // Listen for notification deleted events
            Echo.channel('notification-updates')
                .listen('NotificationDeleted', (event) => {
                    console.log('Notification deleted:', event.notificationId);
                    if (window.notificationManager) {
                        window.notificationManager.deleteNotification(event.notificationId);
                    }
                });
        }
    });

    // Play notification sound
    function playNotificationSound() {
        const audio = new Audio('/sounds/notification.mp3');
        audio.play().catch(e => console.log('Audio play failed:', e));
    }

    // Update browser badge using notification manager
    function updateBrowserBadge() {
        if (window.notificationManager) {
            window.notificationManager.updateNotificationBadge();
        }
    }

    // Request notification permission
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    // Show browser notification
    function showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: `notification-${notification.id}`,
                data: {
                    url: notification.data?.link || '/notifications'
                }
            });
        }
    }

    // Initialize browser notifications
    requestNotificationPermission();

    // Listen for visibility change to update badge
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page is visible, update badge
            updateBrowserBadge();
        }
    });

    // Handle browser notification clicks
    document.addEventListener('click', function(e) {
        if (e.target && e.target.tagName === 'NOTIFICATION') {
            const url = e.target.data?.url;
            if (url) {
                window.location.href = url;
            }
        }
    });
</script>
@endif
