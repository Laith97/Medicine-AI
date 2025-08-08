// Notification System JavaScript
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.socket = null;
        this.init();
    }

    init() {
        this.loadUnreadCount();
        this.setupEventListeners();
        this.setupRealtimeUpdates();
        this.loadNotifications();
    }

    // Load unread notification count
    async loadUnreadCount() {
        try {
            const response = await fetch('/notifications/unread-count');
            const data = await response.json();
            this.updateUnreadCount(data.count);
        } catch (error) {
            console.error('Error loading unread count:', error);
        }
    }

    // Load notifications for dropdown
    async loadNotifications() {
        try {
            console.log('Loading notifications...');
            const response = await fetch('/notifications/dropdown', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Notifications loaded successfully:', data);
            this.notifications = data.notifications;
            this.updateDropdown(data.notifications, data.unread_count);
        } catch (error) {
            console.error('Error loading notifications:', error);
            // Don't fail silently - show user-friendly message
            this.updateDropdown([], 0);
        }
    }

    // Setup event listeners
    setupEventListeners() {
        // Notification bell click
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-bell')) {
                e.preventDefault();
                this.toggleDropdown();
            } else if (!e.target.closest('.notifications-dropdown')) {
                this.closeDropdown();
            }
        });

        // Mark as read buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.mark-read-btn')) {
                e.preventDefault();
                const notificationId = e.target.closest('.mark-read-btn').dataset.id;
                this.markAsRead(notificationId);
            }
        });

        // Mark all as read button
        document.addEventListener('click', (e) => {
            if (e.target.closest('.mark-all-read-btn')) {
                e.preventDefault();
                this.markAllAsRead();
            }
        });

        // Delete notification buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-notification-btn')) {
                e.preventDefault();
                const notificationId = e.target.closest('.delete-notification-btn').dataset.id;
                this.deleteNotification(notificationId);
            }
        });

        // View all notifications link
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-all-notifications')) {
                e.preventDefault();
                window.location.href = e.target.closest('.view-all-notifications').href;
            }
        });
    }

    // Setup real-time updates with Laravel Echo
    setupRealtimeUpdates() {
        if (typeof Echo !== 'undefined') {
            // Listen for new notifications
            Echo.private(`App.User.${userId}`)
                .notification((notification) => {
                    this.addNotification(notification);
                    this.updateUnreadCount(this.unreadCount + 1);
                    this.showNotificationToast(notification);
                });

            // Listen for notification read status updates
            Echo.channel('notification-updates')
                .listen('NotificationRead', (event) => {
                    this.updateNotificationReadStatus(event.notificationId);
                });
        }
    }

    // Add new notification to the list
    addNotification(notification) {
        this.notifications.unshift(notification);
        if (notification.read_at === null) {
            this.unreadCount++;
        }
        this.updateDropdown(this.notifications, this.unreadCount);
    }

    // Update notification read status
    updateNotificationReadStatus(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && notification.read_at === null) {
            notification.read_at = new Date();
            this.unreadCount--;
            this.updateDropdown(this.notifications, this.unreadCount);
        }
    }

    // Mark notification as read
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const notification = this.notifications.find(n => n.id == notificationId);
                if (notification && notification.read_at === null) {
                    notification.read_at = new Date();
                    this.unreadCount--;
                    this.updateDropdown(this.notifications, this.unreadCount);
                    this.updateUnreadCount(this.unreadCount);
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Mark all notifications as read
    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                this.notifications.forEach(notification => {
                    notification.read_at = new Date();
                });
                this.unreadCount = 0;
                this.updateDropdown(this.notifications, 0);
                this.updateUnreadCount(0);
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    // Delete notification
    async deleteNotification(notificationId) {
        try {
            const response = await fetch(`/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const index = this.notifications.findIndex(n => n.id == notificationId);
                if (index > -1) {
                    if (this.notifications[index].read_at === null) {
                        this.unreadCount--;
                    }
                    this.notifications.splice(index, 1);
                    this.updateDropdown(this.notifications, this.unreadCount);
                    this.updateUnreadCount(this.unreadCount);
                }
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }

    // Update unread count display
    updateUnreadCount(count) {
        this.unreadCount = count;
        const countElements = document.querySelectorAll('.notification-count');
        countElements.forEach(element => {
            element.textContent = count > 0 ? count : '';
            element.style.display = count > 0 ? 'block' : 'none';
        });
    }

    // Toggle notification dropdown
    toggleDropdown() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    }

    // Close notification dropdown
    closeDropdown() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }

    // Update dropdown HTML
    updateDropdown(notifications, unreadCount) {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) return;

        const dropdownContent = dropdown.querySelector('.notification-list');
        if (!dropdownContent) return;

        // Update unread count badge
        const countBadge = dropdown.querySelector('.notification-count');
        if (countBadge) {
            countBadge.textContent = unreadCount > 0 ? unreadCount : '';
            countBadge.style.display = unreadCount > 0 ? 'block' : 'none';
        }

        // Update notifications list
        if (notifications.length === 0) {
            dropdownContent.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                    <p class="mb-0">No notifications</p>
                </div>
            `;
        } else {
            dropdownContent.innerHTML = notifications.map(notification => `
                <div class="notification-item ${notification.read_at ? 'read' : 'unread'}" data-id="${notification.id}">
                    <div class="notification-icon">
                        <i class="${this.getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                    </div>
                    <div class="notification-actions">
                        ${notification.read_at ? '' : `<button class="btn btn-sm btn-link mark-read-btn" data-id="${notification.id}">
                            <i class="fas fa-check"></i>
                        </button>`}
                        <button class="btn btn-sm btn-link delete-notification-btn" data-id="${notification.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Add action buttons if there are notifications
        if (notifications.length > 0) {
            dropdownContent.innerHTML += `
                <div class="dropdown-divider"></div>
                <div class="dropdown-footer">
                    <button class="btn btn-sm btn-outline-secondary mark-all-read-btn">
                        <i class="fas fa-check-double me-1"></i>
                        Mark all as read
                    </button>
                    <a href="/notifications" class="btn btn-sm btn-primary view-all-notifications">
                        <i class="fas fa-list me-1"></i>
                        View all notifications
                    </a>
                </div>
            `;
        }
    }

    // Get notification icon based on type
    getNotificationIcon(type) {
        const icons = {
            'appointment': 'fas fa-calendar-check text-primary',
            'diagnosis': 'fas fa-file-medical text-success',
            'review': 'fas fa-star text-warning',
            'voice_assistant': 'fas fa-microphone text-info',
            'system': 'fas fa-exclamation-triangle text-danger',
            'default': 'fas fa-bell text-secondary'
        };
        return icons[type] || icons.default;
    }

    // Format time for notifications
    formatTime(timestamp) {
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
    }

    // Show notification toast
    showNotificationToast(notification) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="toast-message">
                    <div class="toast-title">${notification.title}</div>
                    <div class="toast-text">${notification.message}</div>
                </div>
                <div class="toast-actions">
                    <button class="btn btn-sm btn-primary view-toast-btn" data-id="${notification.id}">
                        View
                    </button>
                    <button class="btn btn-sm btn-secondary dismiss-toast-btn">
                        Dismiss
                    </button>
                </div>
            </div>
        `;

        // Add to page
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Auto dismiss after 5 seconds
        const dismissTimer = setTimeout(() => {
            this.dismissToast(toast);
        }, 5000);

        // Setup event listeners
        toast.querySelector('.dismiss-toast-btn').addEventListener('click', () => {
            clearTimeout(dismissTimer);
            this.dismissToast(toast);
        });

        toast.querySelector('.view-toast-btn').addEventListener('click', () => {
            clearTimeout(dismissTimer);
            this.dismissToast(toast);
            if (notification.data && notification.data.link) {
                window.location.href = notification.data.link;
            }
        });
    }

    // Dismiss toast
    dismissToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Delay initialization to ensure page is fully loaded and avoid conflicts
    setTimeout(() => {
        try {
            // Only initialize if we're on a page that needs notifications
            if (document.querySelector('.notification-bell') || document.querySelector('#notification-count')) {
                console.log('Initializing NotificationManager...');

                // Check if userId is available (for real-time notifications)
                const userId = document.querySelector('meta[name="user-id"]')?.content;
                if (userId) {
                    window.notificationManager = new NotificationManager();
                } else {
                    // Fallback for non-authenticated users
                    window.notificationManager = new NotificationManager();
                }

                console.log('NotificationManager initialized successfully');
            } else {
                console.log('No notification elements found, skipping NotificationManager initialization');
            }
        } catch (error) {
            console.error('Error initializing NotificationManager:', error);
        }
    }, 1000); // 1 second delay
});

// Global functions for external use
window.notifications = {
    markAsRead: function(notificationId) {
        if (window.notificationManager) {
            window.notificationManager.markAsRead(notificationId);
        }
    },
    markAllAsRead: function() {
        if (window.notificationManager) {
            window.notificationManager.markAllAsRead();
        }
    },
    deleteNotification: function(notificationId) {
        if (window.notificationManager) {
            window.notificationManager.deleteNotification(notificationId);
        }
    }
};
