/**
 * Unified Notification System for Real-time Notifications
 * Handles sound, toast, dropdown updates, and real-time updates
 */
class UnifiedNotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.notifications = [];
        this.sound = null;

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        if (this.isInitialized) {
            
            return;
        }

        

        // Get user ID from meta tag
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.userId = userIdMeta.getAttribute('content');
        }

        if (!this.userId) {
            ;
            return;
        }

        // Get settings from meta tags
        this.soundEnabled = document.querySelector('meta[name="notification-sound-enabled"]')?.getAttribute('content') === 'true';
        this.toastEnabled = document.querySelector('meta[name="notification-toast-enabled"]')?.getAttribute('content') === 'true';

        // Initialize sound system
        this.initializeSound();

        // Setup event listeners
        this.setupEventListeners();

        // Load initial data
        this.loadInitialData();

        // Setup Echo when available
        this.waitForEcho();

        this.isInitialized = true;
        
    }

    initializeSound() {
        try {
            // Use the existing NotificationSound if available
            if (window.notificationSound) {
                this.sound = window.notificationSound;
                
            } else {
                ;
                this.createFallbackSound();
            }
        } catch (error) {
            ;
            this.createFallbackSound();
        }
    }

    createFallbackSound() {
        this.sound = {
            play: () => {
                try {
                    // Try to play notification sound from file
                    const audio = new Audio('/sounds/notification.mp3');
                    audio.volume = 0.3;
                    audio.play().catch(() => {
                        // Fallback to system beep
                        
                    });
                } catch (error) {
                    ;
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
                
                this.setupEchoListener();
            } else {
                setTimeout(checkEcho, 500);
            }
        };
        checkEcho();
    }

    setupEchoListener() {
        try {
            const channel = window.Echo.private(`App.User.${this.userId}`);

            // Listen for Laravel's standard notification broadcasts (most common)
            channel.notification((notification) => {
                this.handleNewNotification(notification);
            });

            // Listen for the specific Laravel broadcast notification event
            channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                
                this.handleNewNotification(data);
            });

            // Listen for custom appointment events
            channel.listen('App\\Events\\AppointmentBooked', (data) => {
                
                this.handleNewNotification({
                    id: 'appointment-' + Date.now(),
                    type: 'appointment_booked',
                    title: 'New Appointment Booked',
                    message: data.message || 'A new appointment has been booked',
                    data: data,
                    created_at: new Date().toISOString()
                });
            });

            // Generic event listener for any notification-type events
            channel.listen('.notification', (data) => {
                
                this.handleNewNotification(data);
            });

            channel.subscribed(() => {
                

                // Test the connection by sending a ping
                setTimeout(() => {
                    
                    // You can remove this in production
                }, 2000);
            });

            channel.error((error) => {
                ;
            });

        } catch (error) {
            ;
        }
    }

    handleNewNotification(notification) {
        

        // Handle different notification structures from Laravel
        let normalizedNotification;

        if (notification.data && typeof notification.data === 'object') {
            // Laravel database notification format
            normalizedNotification = {
                id: notification.id,
                type: notification.type || notification.data.type || 'notification',
                title: notification.data.title || 'New Notification',
                message: notification.data.message || notification.data.body || 'You have a new notification',
                data: notification.data,
                read_at: notification.read_at || null,
                created_at: notification.created_at || new Date().toISOString()
            };
        } else if (notification.title || notification.message) {
            // Direct notification format
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: notification.type || 'notification',
                title: notification.title || 'New Notification',
                message: notification.message || 'You have a new notification',
                data: notification.data || {},
                read_at: null,
                created_at: notification.created_at || new Date().toISOString()
            };
        } else {
            // Fallback for any other format
            normalizedNotification = {
                id: notification.id || 'notification-' + Date.now(),
                type: 'notification',
                title: 'New Notification',
                message: JSON.stringify(notification).substring(0, 100) + '...',
                data: notification,
                read_at: null,
                created_at: new Date().toISOString()
            };
        }

        

        // Add to notifications array (avoid duplicates)
        const existingIndex = this.notifications.findIndex(n => n.id === normalizedNotification.id);
        if (existingIndex === -1) {
            this.notifications.unshift(normalizedNotification);
            this.unreadCount += 1;
        } else {
            
            return;
        }

        // Update UI immediately
        this.updateUnreadCountDisplay();
        this.updateNotificationDropdown();

        // Play sound if enabled
        if (this.soundEnabled && this.sound) {
            
            try {
                this.sound.play();
                
            } catch (error) {
                ;
            }
        } else {
            
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            
            try {
                this.showToastNotification(normalizedNotification);
                
            } catch (error) {
                ;
            }
        } else {
            
        }

        // Dispatch custom event for other components
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: normalizedNotification
        }));

        
    }

    async loadInitialData() {
        try {
            

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
                
            } else {
                ;
            }
        } catch (error) {
            ;
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
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
            }
        });
        
    }

    updateNotificationDropdown() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) return;

        const notificationList = dropdown.querySelector('.notification-list, #notification-list');
        if (!notificationList) return;

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
                     data-notification-id="${notification.id}">
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
                        ${!notification.read_at ? '<div class="bg-primary rounded-circle" style="width: 8px; height: 8px;"></div>' : ''}
                    </div>
                </div>
            `).join('');
        }

        
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
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (toastContainer.parentNode) {
                    toastContainer.parentNode.removeChild(toastContainer);
                }
            }, 300);
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
                
            }
        } catch (error) {
            ;
        }
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
                    
                }
            } else {
                ;
            }
        } catch (error) {
            ;
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
    }

    enableToast(enabled = true) {
        this.toastEnabled = enabled;
        localStorage.setItem('notification-toast-enabled', enabled);
    }

    testNotification() {
        const testNotification = {
            id: 'test-' + Date.now(),
            type: 'appointment_booked',
            title: 'Test Notification',
            message: 'This is a test notification to verify the system is working correctly.',
            data: {},
            created_at: new Date().toISOString()
        };

        this.handleNewNotification(testNotification);
        
    }
}

// Initialize the unified system when script loads
window.UnifiedNotificationSystem = UnifiedNotificationSystem;

// Auto-initialize for authenticated users
if (document.querySelector('meta[name="user-id"]')) {
    window.unifiedNotifications = new UnifiedNotificationSystem();
}
