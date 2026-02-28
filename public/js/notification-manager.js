// Notification Manager Class
class NotificationManager {
    constructor(userId) {
        this.userId = userId;
        this.notifications = [];
        this.unreadCount = 0;
        this.init();
    }

    init() {
        
        this.loadNotifications();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Handle dropdown toggle - only prevent default if not using Bootstrap dropdown
        const notificationBell = document.querySelector('.notification-bell');
        if (notificationBell) {
            // Check if this bell uses Bootstrap dropdown
            const hasBootstrapToggle = notificationBell.hasAttribute('data-bs-toggle') &&
                                     notificationBell.getAttribute('data-bs-toggle') === 'dropdown';

            if (!hasBootstrapToggle) {
                // Fallback for non-Bootstrap dropdowns
                notificationBell.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.loadNotifications();
                });
            }
            // For Bootstrap dropdowns, let Bootstrap handle the toggle
            // Notifications will be loaded via the 'shown.bs.dropdown' event in master.blade.php

            // Add keyboard support for notification bell
            notificationBell.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    notificationBell.click();
                }
            });
        }

        // Handle mark all read button
        const markAllReadBtn = document.querySelector('.mark-all-read-btn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });

            // Add keyboard support
            markAllReadBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.markAllAsRead();
                }
            });
        }

        // Handle view all button
        const viewAllBtn = document.querySelector('.view-all-btn');
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', () => {
                window.location.href = '/notifications';
            });

            // Add keyboard support
            viewAllBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.location.href = '/notifications';
                }
            });
        }

        // Add keyboard navigation for notification items
        this.setupKeyboardNavigation();

        // Add focus management
        this.setupFocusManagement();
    }

    async loadNotifications() {
        try {
            
            

            const response = await fetch('/api/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            

            // Check if response is HTML (error page)
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                const errorText = await response.text();
                

                if (errorText.includes('login') || errorText.includes('authentication')) {
                    throw new Error('Authentication required. Please log in.');
                } else {
                    throw new Error('Server returned an error page. Please try again.');
                }
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            

            this.notifications = data.notifications || [];
            this.unreadCount = data.unread_count || 0;

            this.renderNotifications();
            this.updateNotificationBadge();
        } catch (error) {
            ;

            // Provide more specific error messages
            if (error.message.includes('Authentication required')) {
                this.showNotificationError('Please log in to view notifications');
            } else if (error.message.includes('Network Error')) {
                this.showNotificationError('Network error. Please check your connection');
            } else {
                this.showNotificationError('Failed to load notifications');
            }
        }
    }

    renderNotifications() {
        const notificationList = document.getElementById('notification-list');
        if (!notificationList) return;

        if (this.notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash display-6 d-block mb-2"></i>
                    <small>No notifications</small>
                </div>
            `;
            return;
        }

        let html = '';
        this.notifications.forEach(notification => {
            const time = this.formatTime(notification.created_at);
            const icon = this.getNotificationIcon(notification.type);
            const color = this.getNotificationColor(notification.type);

            html += `
                <div class="notification-item ${notification.read_at ? 'read' : 'unread'}"
                     data-id="${notification.id}"
                     data-href="${notification.data?.link || ''}"
                     role="listitem"
                     tabindex="0"
                     aria-label="${notification.data?.title || 'Notification'}, ${notification.data?.message || 'You have a new notification'}, ${time}${!notification.read_at ? ', New notification' : ''}"
                     aria-describedby="notification-${notification.id}-details">
                    <div class="d-flex align-items-start gap-3 p-3 border-bottom">
                        <div class="notification-icon" style="background: ${this.getNotificationBgColor(notification.type)};" aria-hidden="true">
                            <i class="bi ${icon} text-${color}" aria-hidden="true"></i>
                        </div>
                        <div class="flex-grow-1" id="notification-${notification.id}-details">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 small">${notification.data?.title || 'Notification'}</h6>
                                <small class="text-muted" aria-label="Received ${time}">${time}</small>
                            </div>
                            <p class="mb-0 small text-muted">${notification.data?.message || 'You have a new notification'}</p>
                            ${notification.data?.link ? `
                                <div class="mt-2">
                                    <a href="${notification.data.link}" class="btn btn-sm btn-outline-primary" aria-label="${notification.data?.link_text || 'View Details'}">
                                        ${notification.data?.link_text || 'View Details'}
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        notificationList.innerHTML = html;

        // Add click handlers to mark as read
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.id;
                this.markAsRead(notificationId);
            });
        });
    }

    async markAsRead(notificationId) {
        try {
            
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                // Update local state
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }

                // Update UI
                const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                }

                this.updateNotificationBadge();
            }
        } catch (error) {
            ;
        }
    }

    async markAllAsRead() {
        try {
            
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                // Update local state
                this.notifications.forEach(notification => {
                    notification.read_at = new Date().toISOString();
                });
                this.unreadCount = 0;

                // Update UI
                this.renderNotifications();
                this.updateNotificationBadge();
            }
        } catch (error) {
            ;
        }
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-count');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    setupKeyboardNavigation() {
        // Add keyboard navigation for notification dropdown
        document.addEventListener('keydown', (e) => {
            const dropdown = document.querySelector('.notifications-dropdown .dropdown-menu.show');
            if (!dropdown) return;

            const notificationItems = dropdown.querySelectorAll('.notification-item[tabindex="0"]');
            if (notificationItems.length === 0) return;

            const focusedItem = document.activeElement;
            let currentIndex = Array.from(notificationItems).indexOf(focusedItem);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex < notificationItems.length - 1 ? currentIndex + 1 : 0;
                    notificationItems[nextIndex].focus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : notificationItems.length - 1;
                    notificationItems[prevIndex].focus();
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    if (focusedItem && focusedItem.classList.contains('notification-item')) {
                        this.handleNotificationClick(focusedItem);
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    // Close dropdown and return focus to bell
                    const bell = document.querySelector('.notification-bell');
                    if (bell) {
                        bell.focus();
                        // Close dropdown using Bootstrap
                        const dropdownInstance = bootstrap.Dropdown.getInstance(bell);
                        if (dropdownInstance) {
                            dropdownInstance.hide();
                        }
                    }
                    break;
            }
        });
    }

    setupFocusManagement() {
        // Focus trap for dropdown when opened
        const dropdown = document.querySelector('.notifications-dropdown');
        if (dropdown) {
            dropdown.addEventListener('shown.bs.dropdown', () => {
                // Focus first notification item or mark all read button
                setTimeout(() => {
                    const firstItem = dropdown.querySelector('.notification-item[tabindex="0"]');
                    const markAllBtn = dropdown.querySelector('.mark-all-read-btn');

                    if (firstItem) {
                        firstItem.focus();
                    } else if (markAllBtn) {
                        markAllBtn.focus();
                    }
                }, 100);
            });

            dropdown.addEventListener('hidden.bs.dropdown', () => {
                // Return focus to notification bell
                const bell = document.querySelector('.notification-bell');
                if (bell) {
                    bell.focus();
                }
            });
        }

        // Handle focus for notification items
        document.addEventListener('focusin', (e) => {
            if (e.target.classList.contains('notification-item')) {
                e.target.style.outline = '2px solid #007bff';
                e.target.style.outlineOffset = '2px';
            }
        });

        document.addEventListener('focusout', (e) => {
            if (e.target.classList.contains('notification-item')) {
                e.target.style.outline = '';
                e.target.style.outlineOffset = '';
            }
        });
    }

    handleNotificationClick(notificationItem) {
        const notificationId = notificationItem.dataset.id;
        const href = notificationItem.dataset.href;

        if (notificationId && !notificationItem.classList.contains('read')) {
            this.markAsRead(notificationId);
        }

        if (href) {
            window.location.href = href;
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;

        return date.toLocaleDateString();
    }

    getNotificationIcon(type) {
        const iconMap = {
            'App\\Notifications\\DiagnosisSubmittedNotification': 'bi-file-medical',
            'App\\Notifications\\ReviewSubmittedNotification': 'bi-eye',
            'App\\Notifications\\VoiceTranscriptionCompletedNotification': 'bi-mic',
            'App\\Notifications\\TestNotification': 'bi-bell',
            'App\\Notifications\\SystemAlertNotification': 'bi-exclamation-triangle',
            'appointment': 'bi-calendar-check',
            'diagnosis': 'bi-file-medical',
            'review': 'bi-eye',
            'system': 'bi-gear',
            'default': 'bi-bell'
        };

        for (const [key, icon] of Object.entries(iconMap)) {
            if (type.includes(key)) {
                return icon;
            }
        }

        return iconMap.default;
    }

    getNotificationColor(type) {
        const colorMap = {
            'App\\Notifications\\DiagnosisSubmittedNotification': 'primary',
            'App\\Notifications\\ReviewSubmittedNotification': 'success',
            'App\\Notifications\\VoiceTranscriptionCompletedNotification': 'info',
            'App\\Notifications\\TestNotification': 'primary',
            'App\\Notifications\\SystemAlertNotification': 'warning',
            'appointment': 'primary',
            'diagnosis': 'primary',
            'review': 'success',
            'system': 'warning',
            'default': 'primary'
        };

        for (const [key, color] of Object.entries(colorMap)) {
            if (type.includes(key)) {
                return color;
            }
        }

        return colorMap.default;
    }

    getNotificationBgColor(type) {
        const colorMap = {
            'App\\Notifications\\DiagnosisSubmittedNotification': 'rgba(59, 130, 246, 0.1)',
            'App\\Notifications\\ReviewSubmittedNotification': 'rgba(34, 197, 94, 0.1)',
            'App\\Notifications\\VoiceTranscriptionCompletedNotification': 'rgba(59, 130, 246, 0.1)',
            'App\\Notifications\\TestNotification': 'rgba(59, 130, 246, 0.1)',
            'App\\Notifications\\SystemAlertNotification': 'rgba(251, 191, 36, 0.1)',
            'appointment': 'rgba(59, 130, 246, 0.1)',
            'diagnosis': 'rgba(59, 130, 246, 0.1)',
            'review': 'rgba(34, 197, 94, 0.1)',
            'system': 'rgba(251, 191, 36, 0.1)',
            'default': 'rgba(59, 130, 246, 0.1)'
        };

        for (const [key, color] of Object.entries(colorMap)) {
            if (type.includes(key)) {
                return color;
            }
        }

        return colorMap.default;
    }

    showNotificationError(message = 'Error loading notifications') {
        const notificationList = document.getElementById('notification-list');
        if (notificationList) {
            notificationList.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-exclamation-triangle display-6 d-block mb-2"></i>
                    <small>${message}</small>
                </div>
            `;
        }
    }

    addNotification(notification) {
        
        this.notifications.unshift(notification);
        if (!notification.read_at) {
            this.unreadCount++;
        }
        this.renderNotifications();
        this.updateNotificationBadge();
    }

    deleteNotification(notificationId) {
        
        const index = this.notifications.findIndex(n => n.id === notificationId);
        if (index !== -1) {
            if (!this.notifications[index].read_at) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }
            this.notifications.splice(index, 1);
            this.renderNotifications();
            this.updateNotificationBadge();
        }
    }

    updateNotificationReadStatus(notificationId) {
        
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && !notification.read_at) {
            notification.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.renderNotifications();
            this.updateNotificationBadge();
        }
    }

    showNotificationToast(notification) {
        
        // Implementation for toast notifications
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon" style="background: ${this.getNotificationBgColor(notification.type)};">
                    <i class="bi ${this.getNotificationIcon(notification.type)} text-${this.getNotificationColor(notification.type)}"></i>
                </div>
                <div class="toast-message">
                    <div class="toast-title">${notification.data?.title || 'Notification'}</div>
                    <div class="toast-text">${notification.data?.message || 'You have a new notification'}</div>
                </div>
            </div>
        `;

        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Hide toast after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 5000);
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-count');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    

    // Check if notification manager already exists
    if (!window.notificationManager) {
        // Get user ID from meta tag or fallback
        const userIdElement = document.querySelector('meta[name="user-id"]');
        const userId = userIdElement ? userIdElement.getAttribute('content') : null;

        if (userId) {
            window.notificationManager = new NotificationManager(userId);
            
        } else {
            ;
        }
    } else {
        
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}
