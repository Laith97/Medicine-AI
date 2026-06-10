@auth
<div class="notification-dropdown" x-data="notificationDropdown()" x-init="init()">
    <!-- Notification Bell -->
    <div class="relative">
        <button @click="toggleDropdown()"
                class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition duration-150 ease-in-out"
                :class="{ 'text-blue-600': isOpen }">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <!-- Notification Badge -->
            <template x-if="unreadCount > 0">
                <span class="absolute -top-1 -right-1 flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full"
                      style="min-width: 1.25rem; min-height: 1.25rem; text-align: center; line-height: 1;">
                    <span x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
                </span>
            </template>
        </button>
    </div>

    <!-- Dropdown Menu -->
    <div x-show="isOpen"
         @click.away="closeDropdown()"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
         style="top: 100%;">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <div class="flex space-x-2">
                <button @click="markAllAsRead()"
                        x-show="unreadCount > 0"
                        class="text-sm text-blue-600 hover:text-blue-800">
                    Mark all read
                </button>
                <button @click="refreshNotifications()"
                        class="text-sm text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="mt-2">No notifications yet</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                     :class="{ 'bg-blue-50 border-blue-100': !notification.read_at }"
                     @click="markAsRead(notification.id, notification.data?.link)">
                    <div class="flex items-start space-x-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                             :class="getNotificationIconClass(notification.data?.type)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="notification.data?.type === 'appointment_booked'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                <path x-show="notification.data?.type === 'appointment_cancelled'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                                <path x-show="notification.data?.type === 'appointment_status_changed' || notification.data?.type === 'appointment_completed'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path x-show="notification.data?.type === 'no_show'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                <path x-show="notification.data?.type?.startsWith('waitlist')"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                <path x-show="notification.data?.type === 'diagnosis_submitted'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                <path x-show="notification.data?.type === 'review_submitted'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                <path x-show="notification.data?.type === 'message'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                <path x-show="!['appointment_booked', 'appointment_cancelled', 'appointment_status_changed', 'appointment_completed', 'no_show', 'message'].includes(notification.data?.type) && !notification.data?.type?.startsWith('waitlist') && !['diagnosis_submitted', 'review_submitted'].includes(notification.data?.type)"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900" x-text="notification.data?.title || 'Notification'"></p>
                                    <p class="text-sm text-gray-600 mt-1" x-text="notification.data?.message || notification.data?.body"></p>
                                    <p class="text-xs text-gray-400 mt-2" x-text="formatDate(notification.created_at)"></p>
                                </div>
                                <!-- Unread indicator -->
                                <div x-show="!notification.read_at"
                                     class="ml-2 w-2 h-2 bg-blue-500 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-100 text-center">
            <a href="{{ route('notifications.index', ['user' => auth()->id()]) }}"
               class="text-sm text-blue-600 hover:text-blue-800">
                View all notifications
            </a>
        </div>
    </div>
</div>

<script>
function notificationDropdown() {
    return {
        isOpen: false,
        notifications: [],
        unreadCount: 0,
        soundEnabled: true,

        init() {
            // Register this instance with the global notification system FIRST
            window.notificationDropdownInstance = this;

            // Listen for global notification events BEFORE loadNotifications
            window.addEventListener('notificationReceived', (event) => {
                this.handleNewNotification(event.detail);
            });

            // Now load notifications from server
            this.loadNotifications();
        },

        handleNewNotification(notification) {
            console.log('🔔 Alpine handleNewNotification called with:', notification);

            // Deduplication: Use BOTH notification type AND appointment_id to prevent duplicates
            // Different notification types (booking vs status change vs waitlist) for same appointment
            // should ALL appear separately
            const apptId = notification.data?.appointment_id || notification.data?.data?.appointment_id || notification.data?.id;
            const notifType = notification.data?.type || notification.type;
            // Create unique key combining type and appointment
            const dedupKey = `${notifType}-${apptId}`;

            const existingForKey = this.notifications.find(n => {
                const nApptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
                const nType = n.data?.type || n.type;
                return `${nType}-${nApptId}` === dedupKey;
            });

            if (existingForKey) {
                console.log('🔔 Notification already exists for key:', dedupKey, '- updating existing');
                // Update the existing notification instead of adding new one
                // IMPORTANT: Preserve the existing read_at state if already read
                const index = this.notifications.indexOf(existingForKey);
                this.notifications[index] = {
                    ...existingForKey,  // Preserve all existing properties
                    id: notification.id,
                    type: notification.type,
                    data: notification.data,
                    created_at: new Date().toISOString(),
                    title: notification.title || notification.data?.title || 'Notification',
                    message: notification.message || notification.data?.message || notification.body
                    // Do NOT reset read_at - preserve if user already marked as read
                };
                this.unreadCount = this.notifications.filter(n => !n.read_at).length;
                console.log('🔔 Alpine notifications updated:', this.notifications.length, 'unreadCount:', this.unreadCount);
                return;
            }

            // Add new notification
            this.notifications.unshift({
                id: notification.id,
                type: notification.type,
                data: notification.data,
                read_at: null,
                created_at: new Date().toISOString(),
                title: notification.title || notification.data?.title || 'Notification',
                message: notification.message || notification.data?.message || notification.body
            });

            // Recalculate unread count
            this.unreadCount = this.notifications.filter(n => !n.read_at).length;
            console.log('🔔 Alpine notifications now:', this.notifications.length, 'unreadCount:', this.unreadCount);
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            // Always refresh when opening to get latest notifications from server
            if (this.isOpen) {
                this.loadNotifications();
            }
        },

        closeDropdown() {
            this.isOpen = false;
        },

        async loadNotifications() {
            try {
                console.log('📱 Loading notifications...');

                const response = await fetch('/api/notifications', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('📋 API Notifications loaded:', data);

                // Build Sets of existing notification identifiers to avoid duplicates
                const existingIds = new Set(this.notifications.map(n => n.id));
                // Track existing type+appointment combinations for deduplication
                // Different notification types (booking vs status change vs waitlist) for same appointment
                // should ALL appear separately
                const existingTypeAppointmentKeys = new Set();
                this.notifications.forEach(n => {
                    const apptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
                    const nType = n.data?.type || n.type;
                    if (apptId && nType) {
                        existingTypeAppointmentKeys.add(`${nType}-${apptId}`);
                    }
                });

                // Filter API notifications: only add if not already present
                // This keeps local realtime notifications (they have composite IDs with hyphens)
                // IMPORTANT: We skip API notifications if a notification for same TYPE+appointment already exists locally
                const newApiNotifications = (data.notifications || []).filter(n => {
                    // Skip if we already have this exact ID
                    if (existingIds.has(n.id)) {
                        return false;
                    }
                    // Skip if we already have a local notification for same TYPE+appointment
                    const apptId = n.data?.appointment_id || n.data?.data?.appointment_id || n.data?.id;
                    const nType = n.data?.type || n.type;
                    const key = `${nType}-${apptId}`;
                    if (apptId && existingTypeAppointmentKeys.has(key)) {
                        console.log('🔔 Skipping API notification (local realtime exists for same type+appointment):', key);
                        return false;
                    }
                    return true;
                });

                console.log('📋 New API notifications to add:', newApiNotifications.length);
                console.log('📋 Local notifications being preserved:', this.notifications.length);

                // Prepend new API notifications (most recent first) and keep all local ones
                // This preserves realtime notifications that haven't been saved to DB yet
                if (newApiNotifications.length > 0) {
                    this.notifications = [...newApiNotifications, ...this.notifications];
                }

                // Recalculate unread count from LOCAL data to ensure consistency
                // Server count might not include recently added realtime notifications
                this.unreadCount = this.notifications.filter(n => !n.read_at).length;

                console.log('📋 Total notifications after merge:', this.notifications.length, 'unreadCount:', this.unreadCount);
            } catch (error) {
                console.error('❌ Failed to load notifications:', error);
            }
        },

        async markAsRead(notificationId, link = null) {
            console.log('🔔 markAsRead called:', notificationId, link);

            // Check if this is a composite realtime notification ID
            // Realtime IDs have format: type-id-timestamp (e.g., appointment_booked-73-1778148361965)
            // Database UUIDs like 1bb94c72-9ec4-4144-a8ef-bc85920a2801 have a different format
            // (8-4-4-4-12 pattern with no trailing timestamp)
            const isCompositeId = String(notificationId).match(/^[a-z_]+-\d+-\d+$/);

            // For realtime composite IDs, just mark locally and navigate
            if (isCompositeId) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                if (link) {
                    window.location.href = link;
                } else {
                    this.closeDropdown();
                }
                return;
            }

            // For database notification IDs (including UUIDs), call the API
            console.log('🔔 Calling API to mark as read:', notificationId);
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                // Use web route instead of API route (web uses session auth, not Sanctum)
                const markReadUrl = '/notifications/' + notificationId + '/mark-read?_token=' + encodeURIComponent(csrfToken);
                console.log('🔔 Fetch URL:', markReadUrl);
                const response = await fetch(markReadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('🔔 API response status:', response.status);
                const data = await response.json();
                console.log('🔔 API response data:', data);

                if (data.success) {
                    const notification = this.notifications.find(n => n.id === notificationId);
                    if (notification && !notification.read_at) {
                        notification.read_at = new Date().toISOString();
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                        console.log('🔔 Notification marked as read locally, unreadCount now:', this.unreadCount);
                    }
                }

                // Navigate or close
                console.log('🔔 Navigation: link =', link);
                if (link) {
                    window.location.href = link;
                } else {
                    this.closeDropdown();
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
                if (link) {
                    window.location.href = link;
                } else {
                    this.closeDropdown();
                }
            }
        },

        async markAllAsRead() {
            try {
                console.log('🔔 Mark all as read clicked');

                const response = await fetch('/api/notifications/mark-all-read', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('🔔 Mark all response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('🔔 Mark all response data:', data);

                if (data.success) {
                    this.notifications.forEach(notification => {
                        notification.read_at = new Date().toISOString();
                    });
                    this.unreadCount = 0;
                } else {
                    console.error('Failed to mark all as read:', data.error);
                    // Try local update anyway - don't block user
                    this.notifications.forEach(notification => {
                        notification.read_at = new Date().toISOString();
                    });
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Failed to mark all notifications as read:', error);
                // Try local update on error too
                this.notifications.forEach(notification => {
                    notification.read_at = new Date().toISOString();
                });
                this.unreadCount = 0;
            }
        },

        refreshNotifications() {
            this.loadNotifications();
        },

        getNotificationIconClass(type) {
            switch (type) {
                case 'appointment_booked':
                    return 'bg-green-100 text-green-600';
                case 'appointment_cancelled':
                    return 'bg-red-100 text-red-600';
                case 'appointment_status_changed':
                    return 'bg-orange-100 text-orange-600';
                case 'appointment_completed':
                    return 'bg-blue-100 text-blue-600';
                case 'no_show':
                    return 'bg-yellow-100 text-yellow-600';
                case 'waitlist_slot_available':
                    return 'bg-green-100 text-green-600';
                case 'waitlist_auto_booked':
                    return 'bg-teal-100 text-teal-600';
                case 'waitlist_position_update':
                    return 'bg-blue-100 text-blue-600';
                case 'waitlist_offer_expiring':
                    return 'bg-orange-100 text-orange-600';
                case 'waitlist_expired':
                    return 'bg-red-100 text-red-600';
                case 'diagnosis_submitted':
                    return 'bg-purple-100 text-purple-600';
                case 'review_submitted':
                    return 'bg-yellow-100 text-yellow-600';
                case 'system_alert':
                    return 'bg-gray-100 text-gray-600';
                case 'message':
                    return 'bg-blue-100 text-blue-600';
                default:
                    return 'bg-gray-100 text-gray-600';
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);

            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes}m ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours}h ago`;
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days}d ago`;
            }
        }
    }
}
</script>
@endauth
