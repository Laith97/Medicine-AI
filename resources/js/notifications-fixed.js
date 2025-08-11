// Enhanced Real-time Notification System
// Fixes all the issues with multiple notification systems

class EnhancedNotificationSystem {
    constructor() {
        this.isInitialized = false;
        this.userId = null;
        this.soundEnabled = true;
        this.toastEnabled = true;
        this.unreadCount = 0;
        this.echoReady = false;
        this.channel = null;

        // Initialize when both DOM and Echo are ready
        this.waitForReady();
    }

    waitForReady() {
        const checkReady = () => {
            const domReady = document.readyState === 'complete' || document.readyState === 'interactive';
            const echoReady = typeof window.Echo !== 'undefined' && window.Echo.connector;

            if (domReady && echoReady) {
                console.log('✅ DOM and Echo ready, initializing enhanced notifications');
                this.init();
            } else {
                console.log('⏳ Waiting for DOM and Echo...', { domReady, echoReady });
                setTimeout(checkReady, 500);
            }
        };
        checkReady();
    }

    init() {
        if (this.isInitialized) {
            console.log('⚠️ Enhanced notification system already initialized');
            return;
        }

        console.log('🚀 Initializing Enhanced Notification System...');

        // Get user ID from meta tag
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        if (userIdMeta) {
            this.userId = userIdMeta.getAttribute('content');
        }

        if (!this.userId) {
            console.warn('⚠️ User ID not found, notifications disabled');
            return;
        }

        // Get settings from meta tags
        this.soundEnabled = document.querySelector('meta[name="notification-sound-enabled"]')?.getAttribute('content') !== 'false';
        this.toastEnabled = document.querySelector('meta[name="notification-toast-enabled"]')?.getAttribute('content') !== 'false';

        console.log('⚙️ Settings:', {
            userId: this.userId,
            soundEnabled: this.soundEnabled,
            toastEnabled: this.toastEnabled
        });

        // Setup Echo listener
        this.setupEchoListener();

        // Load initial unread count
        this.loadUnreadCount();

        // Register global instance
        window.enhancedNotificationSystem = this;

        this.isInitialized = true;
        console.log('✅ Enhanced Notification System initialized for user:', this.userId);
    }

    setupEchoListener() {
        console.log(`🚀 Setting up enhanced Echo listener for user ${this.userId}`);

        try {
            const channelName = `App.User.${this.userId}`;
            console.log(`📡 Connecting to channel: ${channelName}`);

            this.channel = window.Echo.private(channelName);

            // PRIMARY: Laravel's standard notification broadcasts
            this.channel.notification((notification) => {
                console.log('🔔 [PRIMARY] Laravel notification broadcast:', notification);
                this.handleNewNotification(notification, 'notification');
            });

            // SECONDARY: BroadcastNotificationCreated events
            this.channel.listen('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                console.log('🔔 [SECONDARY] BroadcastNotificationCreated:', data);
                this.handleNewNotification(data, 'broadcast_event');
            });

            // TERTIARY: Generic notification events
            this.channel.listen('.notification', (data) => {
                console.log('🔔 [TERTIARY] Generic notification event:', data);
                this.handleNewNotification(data, 'generic');
            });

            // DEBUG: Monitor all raw Pusher events for our channel
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;
                console.log('🔌 Pusher connection state:', pusher.connection.state);

                pusher.bind_global((eventName, data) => {
                    if (eventName.includes(`private-${channelName}`) || eventName.includes(channelName)) {
                        console.log('🔍 [RAW] Pusher event for our channel:', eventName, data);

                        // Try to handle raw events too
                        if (eventName.includes('notification') || eventName.includes('Notification')) {
                            this.handleNewNotification(data, 'raw');
                        }
                    }
                });

                // Monitor connection state changes
                pusher.connection.bind('state_change', (states) => {
                    console.log('🔄 Pusher connection state changed:', states.previous, '->', states.current);
                });

                pusher.connection.bind('connected', () => {
                    console.log('🟢 Pusher connected successfully');
                });

                pusher.connection.bind('disconnected', () => {
                    console.log('🔴 Pusher disconnected');
                });

                pusher.connection.bind('error', (error) => {
                    console.error('❌ Pusher connection error:', error);
                });
            }

            // Channel status handlers
            this.channel.subscribed(() => {
                console.log(`✅ Successfully subscribed to channel: ${channelName}`);
                this.echoReady = true;

                // Verify connection
                if (window.Echo.connector && window.Echo.connector.pusher) {
                    const connectionState = window.Echo.connector.pusher.connection.state;
                    console.log(`🏓 Final connection state: ${connectionState}`);

                    if (connectionState === 'connected') {
                        console.log('🎉 Real-time notifications are fully ready!');
                        this.showSystemNotification('Real-time notifications enabled', 'success');
                    }
                }
            });

            this.channel.error((error) => {
                console.error('❌ Echo channel error:', error);
                this.echoReady = false;
            });

        } catch (error) {
            console.error('❌ Failed to setup enhanced Echo listener:', error);
        }
    }

    handleNewNotification(notification, source = 'unknown') {
        console.log(`🔔 Processing notification from ${source}:`, notification);

        // Normalize notification data
        const normalizedNotification = this.normalizeNotification(notification);
        console.log('📝 Normalized notification:', normalizedNotification);

        // Update UI
        this.updateUnreadCount(1);
        this.updateNotificationDropdown(normalizedNotification);

        // Play sound if enabled
        if (this.soundEnabled) {
            this.playNotificationSound();
        }

        // Show toast if enabled
        if (this.toastEnabled) {
            this.showToastNotification(normalizedNotification);
        }

        // Dispatch custom events for compatibility
        document.dispatchEvent(new CustomEvent('enhancedNotificationReceived', {
            detail: normalizedNotification
        }));

        // Also dispatch the legacy event that the dropdown component expects
        document.dispatchEvent(new CustomEvent('notificationReceived', {
            detail: normalizedNotification
        }));

        console.log('✅ Notification processed successfully');
    }

    normalizeNotification(notification) {
        // Handle different notification structures from Laravel
        let normalized = {
            id: null,
            type: 'notification',
            title: 'New Notification',
            message: 'You have a new notification',
            data: {},
            read_at: null,
            created_at: new Date().toISOString()
        };

        if (notification) {
            // Direct properties
            normalized.id = notification.id || 'notification-' + Date.now();
            normalized.type = notification.type || normalized.type;

            // Title and message extraction
            normalized.title = notification.title ||
                             notification.data?.title ||
                             normalized.title;

            normalized.message = notification.message ||
                               notification.body ||
                               notification.data?.message ||
                               notification.data?.body ||
                               normalized.message;

            // Data extraction
            normalized.data = notification.data || notification;
            normalized.read_at = notification.read_at || null;
            normalized.created_at = notification.created_at || normalized.created_at;

            // Handle wrapped notifications
            if (notification.notification && typeof notification.notification === 'object') {
                const wrapped = notification.notification;
                normalized.id = wrapped.id || normalized.id;
                normalized.type = wrapped.type || normalized.type;
                normalized.title = wrapped.title || wrapped.data?.title || normalized.title;
                normalized.message = wrapped.message || wrapped.body || wrapped.data?.message || normalized.message;
                normalized.data = wrapped.data || wrapped;
            }
        }

        return normalized;
    }

    async loadUnreadCount() {
        try {
            const response = await fetch('/api/notifications');
            if (response.ok) {
                const data = await response.json();
                this.unreadCount = data.unread_count || 0;
                this.updateUnreadCountDisplay();
                console.log('📊 Loaded unread count:', this.unreadCount);
            }
        } catch (error) {
            console.error('❌ Failed to load unread count:', error);
        }
    }

    updateUnreadCount(increment = 0) {
        this.unreadCount = Math.max(0, this.unreadCount + increment);
        this.updateUnreadCountDisplay();
    }

    updateUnreadCountDisplay() {
        // Update notification badge
        const badges = document.querySelectorAll('.notification-badge, [data-notification-badge]');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        });

        // Update Alpine.js components
        if (window.notificationDropdownInstance) {
            window.notificationDropdownInstance.unreadCount = this.unreadCount;
        }
    }

    updateNotificationDropdown(notification) {
        // Update Alpine.js dropdown instance directly
        if (window.notificationDropdownInstance) {
            console.log('📋 Updating Alpine.js notification dropdown');
            try {
                // Use Alpine.js $nextTick to ensure proper reactivity
                window.notificationDropdownInstance.handleNewNotification(notification);
                console.log('✅ Alpine.js dropdown updated successfully');
            } catch (error) {
                console.error('❌ Failed to update Alpine.js dropdown:', error);
                // Fallback: manually update
                window.notificationDropdownInstance.notifications.unshift({
                    id: notification.id,
                    type: notification.type,
                    data: notification,
                    read_at: null,
                    created_at: notification.created_at,
                    title: notification.title,
                    message: notification.message
                });
                window.notificationDropdownInstance.unreadCount = this.unreadCount;
            }
        } else {
            console.warn('⚠️ Alpine.js notification dropdown instance not found');
        }

        // Also update any other notification lists in the DOM
        const notificationLists = document.querySelectorAll('.notification-list, [data-notification-list]');
        notificationLists.forEach(list => {
            const notificationElement = this.createNotificationElement(notification);
            if (list.firstChild) {
                list.insertBefore(notificationElement, list.firstChild);
            } else {
                list.appendChild(notificationElement);
            }
        });
    }

    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = 'notification-item border-b border-gray-200 p-3 hover:bg-gray-50 cursor-pointer';
        element.dataset.notificationId = notification.id;

        element.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900">${this.escapeHtml(notification.title)}</div>
                    <div class="text-sm text-gray-600 mt-1">${this.escapeHtml(notification.message)}</div>
                    <div class="text-xs text-gray-400 mt-1">Just now</div>
                </div>
            </div>
        `;

        return element;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    playNotificationSound() {
        console.log('🔊 Playing notification sound');

        try {
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                window.notificationSound.play().then(() => {
                    console.log('✅ Sound played successfully');
                }).catch(error => {
                    console.warn('⚠️ Sound play failed:', error);
                });
            } else {
                console.log('⚠️ Notification sound not available, trying fallback');
                this.playFallbackSound();
            }
        } catch (error) {
            console.error('❌ Sound error:', error);
        }
    }

    playFallbackSound() {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.3;
            audio.play().catch(() => {
                console.log('📢 Could not play notification sound');
            });
        } catch (error) {
            console.log('📢 Fallback sound also failed');
        }
    }

    showToastNotification(notification) {
        const toast = document.createElement('div');
        toast.className = 'enhanced-notification-toast';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 16px;
            max-width: 350px;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease-in-out;
        `;

        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1a202c;">
                        ${this.escapeHtml(notification.title)}
                    </h4>
                    <p style="margin: 0; font-size: 13px; color: #4a5568; line-height: 1.4;">
                        ${this.escapeHtml(notification.message)}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()"
                        style="background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0; margin-left: 8px;">
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

        console.log('📋 Toast notification displayed');
    }

    showSystemNotification(message, type = 'info') {
        this.showToastNotification({
            id: 'system-' + Date.now(),
            title: 'System',
            message: message,
            type: type
        });
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
                this.updateUnreadCount(-1);
                return true;
            }
        } catch (error) {
            console.error('❌ Failed to mark notification as read:', error);
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
            console.error('❌ Failed to mark all notifications as read:', error);
        }
        return false;
    }
}

// Initialize the enhanced notification system
window.enhancedNotificationSystem = new EnhancedNotificationSystem();
