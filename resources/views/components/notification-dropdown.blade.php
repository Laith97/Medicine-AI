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
            <span x-show="unreadCount > 0"
                  x-text="unreadCount > 99 ? '99+' : unreadCount"
                  class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[1.2rem] h-5"
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 scale-0"
                  x-transition:enter-end="opacity-100 scale-100">
            </span>
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
                                <path x-show="notification.data?.type === 'message'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                <path x-show="!['appointment_booked', 'message'].includes(notification.data?.type)"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
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

<!-- Include notification sound script -->
<script src="{{ asset('sounds/notification-sound.js') }}"></script>

<script>
function notificationDropdown() {
    return {
        isOpen: false,
        notifications: [],
        unreadCount: 0,
        soundEnabled: {{ env('NOTIFICATION_SOUND_ENABLED', 'true') === 'true' ? 'true' : 'false' }},

        init() {
            this.loadNotifications();

            // Register this instance with the global notification system
            window.notificationDropdownInstance = this;

            // Listen for global notification events
            document.addEventListener('notificationReceived', (event) => {
                this.handleNewNotification(event.detail);
            });
        },

        handleNewNotification(notification) {
            // Add to notifications list
            this.notifications.unshift({
                id: notification.id,
                type: notification.type,
                data: notification,
                read_at: null,
                created_at: new Date().toISOString(),
                title: notification.title || notification.data?.title || 'Notification',
                message: notification.message || notification.data?.message || notification.body
            });
            this.unreadCount += 1;
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.loadNotifications();
            }
        },

        closeDropdown() {
            this.isOpen = false;
        },

        async loadNotifications() {
            try {
                const response = await fetch('/api/notifications');
                const data = await response.json();
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        },

        async markAsRead(notificationId, link = null) {
            try {
                await fetch(`/api/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                // Update notification as read
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }

                // Navigate to link if provided
                if (link) {
                    window.location.href = link;
                } else {
                    this.closeDropdown();
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('/api/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                // Update all notifications as read
                this.notifications.forEach(notification => {
                    notification.read_at = new Date().toISOString();
                });
                this.unreadCount = 0;
            } catch (error) {
                console.error('Failed to mark all notifications as read:', error);
            }
        },

        refreshNotifications() {
            this.loadNotifications();
        },





        getNotificationIconClass(type) {
            switch (type) {
                case 'appointment_booked':
                    return 'bg-green-100 text-green-600';
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
