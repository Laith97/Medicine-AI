/**
 * Enhanced Unified Notification System v2
 * Handles sound, toast, dropdown updates, and real-time updates
 * Fixed for Bootstrap dropdown and real-time Laravel notifications
 */
class UnifiedNotificationSystemV2 {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.notifications = [];
        this.sound = null;
        this.echoChannel = null;

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        if (this.isInitialized) {
            console.log('⚠️ Notification system already initialized');
            return;
        }

        console.log('🚀 Initializing Enhanced Unified Notification System v2...');

        // Get user ID from meta tag
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.userId = userIdMeta.getAttribute('content');
        }

        if (!this.userId) {
            console.warn('⚠️ User ID not found, notification system disabled');
            return;
        }

        // Get settings from meta tags and localStorage
        this.soundEnabled = localStorage.getItem('notification-sound-enabled') !== 'false';
        this.toastEnabled = localStorage.getItem('notification-toast-enabled') !== 'false';

        console.log('⚙️ Settings:', { soundEnabled: this.soundEnabled, toastEnabled: this.toastEnabled });

        // Initialize sound system
        this.initializeSound();

        // Setup event listeners
        this.setupEventListeners();

        // Load initial data
        this.loadInitialData();

        // Setup Echo when available
        this.waitForEcho();

        this.isInitialized = true;
        console.log('✅ Enhanced Unified Notification System v2 initialized for user:', this.userId);
    }

    initializeSound() {
        try {
            // Use the existing NotificationSound if available
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                this.sound = window.notificationSound;
                console.log('🔊 Using existing NotificationSound instance');
            } else {
                console.log('🔊 Creating fallback sound system');
                this.createFallbackSound();
            }
        } catch (error) {
            console.error('❌ Failed to initialize sound:', error);
            this.createFallbackSound();
        }
    }

    createFallbackSound() {
        this.sound = {
            play: () => {
                if (!this.soundEnabled) {
                    console.log('🔇 Sound disabled');
                    return Promise.resolve();
                }

                try {
                    const audio = new Audio('/sounds/notification.mp3');
                    audio.volume = 0.3;
                    return audio.play().catch((error) => {
                        console.warn('⚠️ Could not play notification sound:', error);
                        // Try alternative sound
                        try {
                            const beep = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmEcBzWY1/LNfS');
                            beep.play();
                        } catch (e) {
                            console.log('📢 Could not play any sound');
                        }
                    });
                } catch (error) {
                    console.warn('⚠️ Could not create audio element');
                    return Promise.resolve();
                }
            },
            setEnabled: (enabled) => {
                this.soundEnabled = enabled;
            }
        };
    }

    waitForEcho() {
        const checkEcho = () => {
            if (typeof window.Echo !== 'undefined' && window.Echo.connector) {
                console.log('📡 Echo ready, setting up enhanced real-time listeners');
                this.setupEchoListener();
            } else {
                console.log('⏳ Waiting for Echo...');
                setTimeout(checkEcho, 500);
            }
        };
        checkEcho();
    }

    setupEchoListener() {
        try {
            console.log('📡 Setting up enhanced Echo listeners for user:', this.userId);
            const channelName = `App.User.${this.userId}`;
            const channel = window.Echo.private(channelName);

            // Store channel reference for debugging
            this.echoChannel = channel;

            console.log('📡 Subscribing to channel:', channelName);

            // PRIMARY METHOD: Laravel's standard notification broadcasts
            channel.notification((notification) => {
                console.log('🔔 [PRIMARY] Laravel notification broadcast:', notification);
                this.handleNewNotification(notification);
            });

            // SECONDARY METHODS: Various event listeners to catch different broadcast formats

            // Listen for BroadcastNotificationCreated events
            channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                console.log('🔔 [SECONDARY] BroadcastNotificationCreated event:', data);
                this.handleNewNotification(data);
            });

            // Listen for specific notification classes
            channel.listen('App\\Notifications\\AppointmentBookedNotification', (data) => {
                console.log('🔔 [SECONDARY] AppointmentBookedNotification:', data);
                this.handleNewNotification(data);
            });

            // Listen for any notification-related events
            channel.listen('.notification', (data) => {
                console.log('🔔 [SECONDARY] Generic .notification event:', data);
                this.handleNewNotification(data);
            });

            // Channel status handlers
            channel.subscribed(() => {
                console.log(`✅ Successfully subscribed to channel: ${channelName}`);
                console.log('📡 Channel ready for real-time notifications');

                // Test connection after subscription
                setTimeout(() => {
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        const state = window.Echo.connector.pusher.connection.state;
                        console.log('🏓 Echo connection state:', state);
                        if (state === 'connected') {
                            console.log('🎉 Real-time notifications ready!');
                        }
                    }
                }, 1000);
            });

            channel.error((error) => {
                console.error('❌ Echo channel error:', error);
            });

            // DEBUG: Monitor raw Pusher events if available
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;
                console.log('🔌 Pusher connection state:', pusher.connection.state);

                // Listen to all events for our user channel
                pusher.bind_global((eventName, data) => {
                    if (eventName && eventName.includes && eventName.includes(`private-${channelName}`)) {
                        console.log('📨 [RAW PUSHER] Event for our channel:', eventName, data);
                    }
                });
            }

        } catch (error) {
            console.error('❌ Failed to setup Echo listener:', error);
        }
    }

    handleNewNotification(notification) {
        console.log('🔔 Processing new notification:', notification);
        console.log('📊 Notification keys:', Object.keys(notification || {}));
        console.log('📊 Notification type:', typeof notification);

        // Handle different notification structures from Laravel
        let normalizedNotification;

        // Method 1: Laravel BroadcastMessage format (direct from toBroadcast method)
        if (notification.id && notification.type && (notification.title || notification.message || notification.body)) {
            console.log('📝 Detected Laravel BroadcastMessage format');
            normalizedNotification = {
                id: notification.id,
                type: notification.type,
                title: notification.title || 'New Notification',
                message: notification.message || notification.body || 'You have a new notification',
                data: notification.data || {},
                read_at: null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 2: Wrapped notification (from BroadcastNotificationCreated events)
        else if (notification.notification && typeof notification.notification === 'object') {
            console.log('📝 Detected wrapped notification format');
            const wrappedNotification = notification.notification;
            normalizedNotification = {
                id: wrappedNotification.id || 'notification-' + Date.now(),
                type: wrappedNotification.type || 'notification',
                title: wrappedNotification.title || wrappedNotification.data?.title || 'New Notification',
                message: wrappedNotification.message || wrappedNotification.data?.message || wrappedNotification.body || 'You have a new notification',
                data: wrappedNotification.data || {},
                read_at: null,
                created_at: wrappedNotification.created_at || new Date().toISOString()
            };
        }
        // Method 3: Laravel database notification format (with nested data)
        else if (notification.data && typeof notification.data === 'object') {
            console.log('📝 Detected Laravel database notification format');
            normalizedNotification = {
                id: notification.id,
                type: notification.type || notification.data.type || 'notification',
                title: notification.data.title || 'New Notification',
                message: notification.data.message || notification.data.body || 'You have a new notification',
                data: notification.data,
                read_at: notification.read_at || null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 4: Direct notification format (simple structure)
        else if (notification.title || notification.message || notification.body) {
            console.log('📝 Detected direct notification format');
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: notification.type || 'notification',
                title: notification.title || 'New Notification',
                message: notification.message || notification.body || 'You have a new notification',
                data: notification.data || {},
                read_at: null,
                created_at: notification.created_at || new Date().toISOString()
            };
        }
        // Method 5: Fallback for any other format
        else {
            console.log('📝 Using fallback format processing');
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: notification.type || 'notification',
                title: 'New Notification',
                message: JSON.stringify(notification).substring(0, 100) + '...',
                data: notification,
                read_at: null,
                created_at: new Date().toISOString()
            };
        }

        console.log('📝 Normalized notification:', normalizedNotification);

        // Add to notifications array (avoid duplicates)
        const existingIndex = this.notifications.findIndex(n => n.id === normalizedNotification.id);
        if (existingIndex === -1) {
            this.notifications.unshift(normalizedNotification);
            this.unreadCount += 1;
        } else {
            console.log('⚠️ Duplicate notification ignored:', normalizedNotification.id);
            return;
        }

        // Update UI immediately
        this.updateUnreadCountDisplay();
        this.updateNotificationDropdown();

        // Play sound if enabled
        if (this.soundEnabled && this.sound) {
            console.log('🔊 Playing notification sound');
            try {
                this.sound.play().then(() => {
                    console.log('✅ Sound played successfully');
                }).catch(error => {
                    console.error('❌ Failed to play sound:', error);
                });
            } catch (error) {
                console.error('❌ Sound error:', error);
            }
        } else {
            console.log('🔇 Sound disabled or not available');
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            console.log('📋 Showing toast notification');
            try {
                this.showToastNotification(normalizedNotification);
                console.log('✅ Toast shown successfully');
            } catch (error) {
                console.error('❌ Failed to show toast:', error);
            }
        } else {
            console.log('📋 Toast disabled');
        }

        // Dispatch custom event for other components
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: normalizedNotification
        }));

        console.log('✅ Notification processed successfully');
    }

    async loadInitialData() {
        try {
            console.log('📥 Loading initial notification data...');

            // Try the existing web route first
            let response = await fetch('/notifications/dropdown', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            // If that fails, try the API route
            if (!response.ok) {
                response = await fetch('/api/notifications', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
            }

            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.notifications || [];
                this.updateUnreadCountDisplay();
                this.updateNotificationDropdown();
                console.log('✅ Initial data loaded:', { unread: this.unreadCount, total: this.notifications.length });
            } else {
                console.warn('⚠️ Failed to load initial data, response status:', response.status);
            }
        } catch (error) {
            console.error('❌ Failed to load initial data:', error);
            // Initialize with empty data
            this.unreadCount = 0;
            this.notifications = [];
            this.updateUnreadCountDisplay();
            this.updateNotificationDropdown();
        }
    }

    updateUnreadCountDisplay() {
        // Update all notification count badges
        const badges = document.querySelectorAll('.notification-count, #notification-count, [data-unread-count]');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'block';
                badge.classList.remove('d-none');
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
                badge.classList.add('d-none');
            }
        });
        console.log('🔢 Updated unread count display:', this.unreadCount);
    }

    updateNotificationDropdown() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) {
            console.warn('⚠️ Notifications dropdown not found');
            return;
        }

        const notificationList = dropdown.querySelector('.notification-list, #notification-list');
        if (!notificationList) {
            console.warn('⚠️ Notification list not found in dropdown');
            return;
        }

        if (this.notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                    <small>No notifications</small>
                </div>
            `;
        } else {
            notificationList.innerHTML = this.notifications.slice(0, 10).map(notification => `
                <div class="dropdown-item notification-item ${notification.read_at ? 'read' : 'unread'}"
                     data-notification-id="${notification.id}"
                     style="cursor: pointer; border-left: 3px solid ${notification.read_at ? 'transparent' : '#007bff'};">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 32px; height: 32px;">
                                <i class="bi bi-${this.getNotificationIcon(notification.type)} text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${notification.title}</div>
                            <div class="text-muted small">${notification.message}</div>
                            <div class="text-muted small">${this.formatTime(notification.created_at)}</div>
                        </div>
                        ${!notification.read_at ? '<div class="bg-primary rounded-circle ms-2" style="width: 8px; height: 8px;"></div>' : ''}
                    </div>
                </div>
            `).join('');
        }

        console.log('📋 Updated notification dropdown with', this.notifications.length, 'notifications');
    }

    showToastNotification(notification) {
        // Remove any existing toast
        const existingToast = document.querySelector('.notification-toast-container');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast container
        const toastContainer = document.createElement('div');
        toastContainer.className = 'notification-toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;

        // Create toast
        const toast = document.createElement('div');
        toast.className = 'notification-toast bg-white shadow-lg border rounded-3 p-3';
        toast.style.cssText = `
            max-width: 350px;
            pointer-events: auto;
            transform: translateX(400px);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #007bff !important;
        `;

        toast.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="bi bi-${this.getNotificationIcon(notification.type)} text-white"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold mb-1">${notification.title}</div>
                    <div class="text-muted small">${notification.message}</div>
                </div>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="this.closest('.notification-toast-container').remove()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;

        // Add click handler
        if (notification.data?.link) {
            toast.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    window.location.href = notification.data.link;
                }
            });
        }

        toastContainer.appendChild(toast);
        document.body.appendChild(toastContainer);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toastContainer.parentNode) {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (toastContainer.parentNode) {
                        toastContainer.parentNode.removeChild(toastContainer);
                    }
                }, 300);
            }
        }, 5000);
    }

    setupEventListeners() {
        // Don't interfere with Bootstrap dropdown - just add our custom handlers

        // Mark as read functionality
        document.addEventListener('click', async (e) => {
            const notificationItem = e.target.closest('.notification-item[data-notification-id]');
            if (notificationItem && !notificationItem.classList.contains('read')) {
                const notificationId = notificationItem.dataset.notificationId;
                await this.markAsRead(notificationId);
            }
        });

        // Mark all as read button
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.mark-all-read-btn')) {
                await this.markAllAsRead();
            }
        });

        // View all notifications button
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-all-btn')) {
                window.location.href = '/notifications';
            }
        });

        console.log('✅ Event listeners setup complete');
    }

    async markAsRead(notificationId) {
        try {
            // Try the existing web route first
            let response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // If that fails, try the API route
            if (!response.ok) {
                response = await fetch(`/api/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }

            if (response.ok) {
                // Update local state
                const notification = this.notifications.find(n => n.id == notificationId);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.updateUnreadCountDisplay();
                    this.updateNotificationDropdown();
                    console.log('✅ Notification marked as read:', notificationId);
                }
            } else {
                console.warn('⚠️ Failed to mark notification as read, status:', response.status);
            }
        } catch (error) {
            console.error('❌ Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                // Update local state
                this.notifications.forEach(notification => {
                    if (!notification.read_at) {
                        notification.read_at = new Date().toISOString();
                    }
                });
                this.unreadCount = 0;
                this.updateUnreadCountDisplay();
                this.updateNotificationDropdown();
                console.log('✅ All notifications marked as read');
            }
        } catch (error) {
            console.error('❌ Failed to mark all notifications as read:', error);
        }
    }

    getNotificationIcon(type) {
        const icons = {
            'appointment_booked': 'calendar-check',
            'appointment': 'calendar-check',
            'diagnosis': 'file-medical',
            'message': 'chat-dots',
            'system': 'gear',
            'warning': 'exclamation-triangle',
            'error': 'exclamation-circle',
            'default': 'bell'
        };
        return icons[type] || icons.default;
    }

    formatTime(timestamp) {
        try {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) { // Less than 1 minute
                return 'Just now';
            } else if (diff < 3600000) { // Less than 1 hour
                return Math.floor(diff / 60000) + ' minutes ago';
            } else if (diff < 86400000) { // Less than 1 day
                return Math.floor(diff / 3600000) + ' hours ago';
            } else if (diff < 604800000) { // Less than 1 week
                return Math.floor(diff / 86400000) + ' days ago';
            } else {
                return date.toLocaleDateString();
            }
        } catch (error) {
            return 'Recently';
        }
    }

    // Public methods for external use
    enableSound(enabled = true) {
        this.soundEnabled = enabled;
        if (this.sound && typeof this.sound.setEnabled === 'function') {
            this.sound.setEnabled(enabled);
        }
        localStorage.setItem('notification-sound-enabled', enabled);
        console.log('🔊 Sound', enabled ? 'enabled' : 'disabled');
    }

    enableToast(enabled = true) {
        this.toastEnabled = enabled;
        localStorage.setItem('notification-toast-enabled', enabled);
        console.log('📋 Toast', enabled ? 'enabled' : 'disabled');
    }

    testNotification() {
        const testNotification = {
            id: 'test-' + Date.now(),
            type: 'appointment_booked',
            title: 'Test Notification',
            message: 'This is a test notification to verify the system is working correctly!',
            data: {},
            created_at: new Date().toISOString()
        };

        this.handleNewNotification(testNotification);
        console.log('🧪 Test notification sent');
    }

    // Debug method
    getSystemStatus() {
        return {
            initialized: this.isInitialized,
            userId: this.userId,
            soundEnabled: this.soundEnabled,
            toastEnabled: this.toastEnabled,
            unreadCount: this.unreadCount,
            totalNotifications: this.notifications.length,
            echoConnected: !!this.echoChannel,
            soundAvailable: !!this.sound
        };
    }
}

// Initialize the enhanced unified system
window.UnifiedNotificationSystemV2 = UnifiedNotificationSystemV2;

// Auto-initialize for authenticated users
if (document.querySelector('meta[name="user-id"]')) {
    window.unifiedNotifications = new UnifiedNotificationSystemV2();

    // Also provide backward compatibility
    window.testNotifications = () => window.unifiedNotifications?.testNotification();
    window.toggleNotificationSound = (enabled) => window.unifiedNotifications?.enableSound(enabled);
    window.toggleNotificationToast = (enabled) => window.unifiedNotifications?.enableToast(enabled);
}
