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

        // Show skeleton loading state
        notificationList.innerHTML = `
            <div class="notif-skeleton" role="status" aria-live="polite">
                <div class="notif-skeleton-item">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line title"></div>
                        <div class="skeleton-line message"></div>
                        <div class="skeleton-line meta"></div>
                    </div>
                </div>
                <div class="notif-skeleton-item">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line title"></div>
                        <div class="skeleton-line message"></div>
                        <div class="skeleton-line meta"></div>
                    </div>
                </div>
                <div class="notif-skeleton-item">
                    <div class="skeleton-icon"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-line title"></div>
                        <div class="skeleton-line message"></div>
                        <div class="skeleton-line meta"></div>
                    </div>
                </div>
            </div>
        `;

        if (this.notifications.length === 0) {
            notificationList.innerHTML = `
                <div class="notif-empty">
                    <div class="notif-empty-icon">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h6 class="notif-empty-title">All caught up!</h6>
                    <p class="notif-empty-text">You have no notifications at the moment</p>
                </div>
            `;
            return;
        }

        let html = '';
        this.notifications.forEach(notification => {
            const date = new Date(notification.created_at);
            const timeAgo = this.formatTimeAgo(date);
            const isUnread = !notification.read_at;
            const type = notification.data?.type || '';
            
            let iconClass = 'default';
            let iconHtml = '<i class="bi bi-bell-fill"></i>';
            
            if (type.includes('Appointment')) {
                iconClass = 'info';
                iconHtml = '<i class="bi bi-calendar-check"></i>';
            } else if (type.includes('Task') || type.includes('Reminder')) {
                iconClass = 'warning';
                iconHtml = '<i class="bi bi-list-task"></i>';
            } else if (type.includes('Alert') || type.includes('Emergency') || type.includes('HighRisk')) {
                iconClass = 'danger';
                iconHtml = '<i class="bi bi-exclamation-triangle"></i>';
            } else if (type.includes('Success') || type.includes('Complete') || type.includes('AutoBooked')) {
                iconClass = 'success';
                iconHtml = '<i class="bi bi-check-circle"></i>';
            } else if (type.includes('Invoice') || type.includes('Payment') || type.includes('Underpayment')) {
                iconClass = 'warning';
                iconHtml = '<i class="bi bi-receipt"></i>';
            } else if (type.includes('Message') || type.includes('Chat')) {
                iconClass = 'info';
                iconHtml = '<i class="bi bi-chat-dots"></i>';
            }

            html += `
                <div class="notif-item \${isUnread ? 'unread' : ''}"
                     data-id="\${notification.id}"
                     data-link="\${notification.data?.link || ''}"
                     role="listitem"
                     tabindex="0"
                     aria-label="\${notification.data?.title || 'Notification'}, \${notification.data?.message || 'You have a new notification'}, \${timeAgo}\${!notification.read_at ? ', New notification' : ''}">
                    <div class="notif-icon-wrap \${iconClass}">
                        \${iconHtml}
                    </div>
                    <div class="notif-content">
                        <h6 class="notif-title">
                            \${isUnread ? '<span class="notif-dot"></span>' : ''}
                            \${this.escapeHtml(notification.data?.title || 'Notification')}
                        </h6>
                        <p class="notif-message">\${this.escapeHtml(notification.data?.message || 'You have a new notification')}</p>
                        <div class="notif-meta">
                            <span class="notif-time">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                \${timeAgo}
                            </span>
                        </div>
                    </div>
                    <button class="notif-mark-read"
                            title="Mark as read"
                            aria-label="Mark this notification as read"
                            onclick="event.stopPropagation(); this.closest('.notif-item').click();">
                        <i class="bi bi-check2" aria-hidden="true"></i>
                    </button>
                </div>
            `;
        });

        notificationList.innerHTML = html;

        // Add click handlers to mark as read
        document.querySelectorAll('.notif-item.unread').forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.id;
                this.markAsRead(notificationId);
            });
        });
    }

    formatTimeAgo(date) {
        if (!date) return 'Just now';

        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `\${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `\${Math.floor(seconds / 3600)}h ago`;
        if (seconds < 604800) return `\${Math.floor(seconds / 86400)}d ago`;

        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
        // Handle both formats: { data: { title, message } } and { title, message }
        const title = notification.data?.title || notification.title || 'Notification';
        const message = notification.data?.message || notification.message || 'You have a new notification';
        const type = notification.data?.type || notification.type || 'default';
        const link = notification.data?.link || notification.link || '';

        // Create toast elements
        const toast = document.createElement('div');
        toast.className = 'enhanced-notification-toast';

        const content = document.createElement('div');
        content.className = 'toast-content';

        const iconWrapper = document.createElement('div');
        iconWrapper.className = 'toast-icon';
        iconWrapper.style.background = this.getNotificationBgColor(type);

        const icon = document.createElement('i');
        icon.className = 'bi ' + this.getNotificationIcon(type) + ' text-' + this.getNotificationColor(type);

        const msgWrapper = document.createElement('div');
        msgWrapper.className = 'toast-message';

        const titleDiv = document.createElement('div');
        titleDiv.className = 'toast-title';
        titleDiv.textContent = title;

        const textDiv = document.createElement('div');
        textDiv.className = 'toast-text';
        textDiv.textContent = message;

        msgWrapper.appendChild(titleDiv);
        msgWrapper.appendChild(textDiv);
        iconWrapper.appendChild(icon);
        content.appendChild(iconWrapper);
        content.appendChild(msgWrapper);
        toast.appendChild(content);

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
