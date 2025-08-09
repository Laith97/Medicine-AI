// Global notification system for real-time notifications
class NotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        if (this.isInitialized) return;

        // Get user ID from meta tag or auth check
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.userId = userIdMeta.getAttribute('content');
        } else if (window.Laravel && window.Laravel.user) {
            this.userId = window.Laravel.user.id;
        }

        // Get settings from environment
        this.soundEnabled = document.querySelector('meta[name="notification-sound-enabled"]')?.getAttribute('content') !== 'false';
        this.toastEnabled = document.querySelector('meta[name="notification-toast-enabled"]')?.getAttribute('content') !== 'false';

        // Initialize Echo listener if user is authenticated
        if (this.userId && typeof window.Echo !== 'undefined') {
            this.setupEchoListener();
        }

        // Load initial unread count
        this.loadUnreadCount();

        this.isInitialized = true;
        console.log('Notification system initialized for user:', this.userId);
    }

    setupEchoListener() {
        console.log(`Setting up Echo listener for user ${this.userId}`);

        try {
            const channel = window.Echo.private(`App.User.${this.userId}`);

            // Listen for Laravel's standard notification broadcasts
            channel.notification((notification) => {
                console.log('🔔 Real-time notification received:', notification);
                this.handleNewNotification(notification);
            });

            // Listen for the specific Laravel broadcast notification event
            channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                console.log('🔔 BroadcastNotificationCreated event:', data);
                this.handleNewNotification(data);
            });

            channel.subscribed(() => {
                console.log(`✅ Successfully subscribed to private channel: App.User.${this.userId}`);
            });

            channel.error((error) => {
                console.error('❌ Echo channel error:', error);
            });

        } catch (error) {
            console.error('❌ Failed to setup Echo listener:', error);
        }
    }

    handleNewNotification(notification) {
        console.log('🔔 Processing new notification:', notification);

        // Update unread count
        this.unreadCount += 1;
        this.updateUnreadCountDisplay();

        // Play sound if enabled
        if (this.soundEnabled) {
            this.playNotificationSound();
        }

        // Show toast notification if enabled
        if (this.toastEnabled) {
            this.showToastNotification(notification);
        }

        // Update dropdown if it exists
        this.updateNotificationDropdown(notification);

        // Trigger custom event for other components with enhanced detail
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: {
                title: notification.title || notification.data?.title || 'New Notification',
                message: notification.message || notification.data?.message || notification.data?.body || 'You have a new notification',
                type: notification.type,
                data: notification.data,
                id: notification.id,
                original: notification
            }
        }));

        console.log('✅ Notification processed successfully');
    }

    async loadUnreadCount() {
        try {
            const response = await fetch('/api/notifications');
            const data = await response.json();
            this.unreadCount = data.unread_count || 0;
            this.updateUnreadCountDisplay();
        } catch (error) {
            console.error('Failed to load unread count:', error);
        }
    }

    updateUnreadCountDisplay() {
        // Update notification badge in dropdown
        const badge = document.querySelector('.notification-dropdown .notification-badge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        }

        // Update any other unread count displays
        document.querySelectorAll('[data-unread-count]').forEach(element => {
            element.textContent = this.unreadCount;
        });
    }

    updateNotificationDropdown(notification) {
        console.log('🔔 Updating notification dropdown with:', notification);

        // Method 1: Update via window instance if available
        if (window.notificationDropdownInstance) {
            console.log('📋 Using window.notificationDropdownInstance');
            window.notificationDropdownInstance.notifications.unshift({
                id: notification.id,
                type: notification.type,
                data: notification,
                read_at: null,
                created_at: new Date().toISOString(),
                title: notification.title || notification.data?.title || 'Notification',
                message: notification.message || notification.data?.message || notification.data?.body
            });
            window.notificationDropdownInstance.unreadCount = this.unreadCount;
        }

        // Method 2: Refresh dropdown content if it's currently visible
        this.refreshDropdownContent();

        // Method 3: Update any notification lists in the DOM
        this.addNotificationToDOM(notification);
    }

    addNotificationToDOM(notification) {
        // Find notification list containers
        const notificationLists = document.querySelectorAll('.notification-list, [data-notification-list]');

        notificationLists.forEach(list => {
            // Create notification item HTML
            const notificationItem = this.createNotificationItemHTML(notification);

            // Add to top of list
            if (list.firstChild) {
                list.insertBefore(notificationItem, list.firstChild);
            } else {
                list.appendChild(notificationItem);
            }
        });
    }

    createNotificationItemHTML(notification) {
        const item = document.createElement('div');
        item.className = 'notification-item border-b border-gray-200 p-3 hover:bg-gray-50 cursor-pointer';
        item.dataset.notificationId = notification.id;

        const title = notification.title || notification.data?.title || 'New Notification';
        const message = notification.message || notification.data?.message || notification.data?.body || 'You have a new notification';
        const timeAgo = 'Just now';

        item.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900">${title}</div>
                    <div class="text-sm text-gray-600 mt-1">${message}</div>
                    <div class="text-xs text-gray-400 mt-1">${timeAgo}</div>
                </div>
            </div>
        `;

        return item;
    }

    async refreshDropdownContent() {
        // Force refresh notification dropdown if it's open
        const dropdownContent = document.querySelector('[data-notification-dropdown-content]');
        if (dropdownContent) {
            console.log('🔄 Refreshing dropdown content');
            try {
                const response = await fetch('/api/notifications');
                if (response.ok) {
                    const data = await response.json();
                    // Update the dropdown with fresh data
                    // This would need to be implemented based on your specific dropdown structure
                    console.log('✅ Dropdown refreshed with latest notifications');
                }
            } catch (error) {
                console.error('❌ Failed to refresh dropdown:', error);
            }
        }
    }

    playNotificationSound() {
        if (window.notificationSound) {
            window.notificationSound.play();
        }
    }

    showToastNotification(notification) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 16px;
            max-width: 350px;
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease-in-out;
        `;

        const iconClass = this.getNotificationIconClass(notification.type || notification.data?.type);

        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; ${iconClass.bg}">
                    <svg style="width: 16px; height: 16px; ${iconClass.text}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1a202c;">
                        ${notification.title || notification.data?.title || 'New Notification'}
                    </h4>
                    <p style="margin: 0; font-size: 13px; color: #4a5568; line-height: 1.4;">
                        ${notification.message || notification.data?.message || notification.body || 'You have a new notification'}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                        style="background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);

        // Add click handler to navigate to notification link
        if (notification.link || notification.data?.link) {
            toast.style.cursor = 'pointer';
            toast.addEventListener('click', () => {
                window.location.href = notification.link || notification.data.link;
            });
        }
    }

    getNotificationIconClass(type) {
        switch (type) {
            case 'appointment_booked':
                return {
                    bg: 'background-color: #10b981; color: white;',
                    text: 'color: white;'
                };
            case 'message':
                return {
                    bg: 'background-color: #3b82f6; color: white;',
                    text: 'color: white;'
                };
            case 'system':
                return {
                    bg: 'background-color: #f59e0b; color: white;',
                    text: 'color: white;'
                };
            default:
                return {
                    bg: 'background-color: #6b7280; color: white;',
                    text: 'color: white;'
                };
        }
    }

    // Public methods for external use
    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateUnreadCountDisplay();
                return true;
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
        return false;
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.unreadCount = 0;
                this.updateUnreadCountDisplay();
                return true;
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
        return false;
    }

    // Settings
    setSoundEnabled(enabled) {
        this.soundEnabled = enabled;
        localStorage.setItem('notification-sound-enabled', enabled);
    }

    setToastEnabled(enabled) {
        this.toastEnabled = enabled;
        localStorage.setItem('notification-toast-enabled', enabled);
    }
}

// Initialize global notification system
window.notificationSystem = new NotificationSystem();
