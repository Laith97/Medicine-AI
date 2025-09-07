// Medicine-AI Notification Service Worker
// Handles offline capabilities for the notification system

const CACHE_NAME = 'medicine-ai-notifications-v1';
const NOTIFICATION_CACHE = 'medicine-ai-notification-assets-v1';

// Assets to cache for offline notification functionality
const NOTIFICATION_ASSETS = [
    '/sounds/notification.mp3',
    '/js/notification-manager.js',
    '/js/notification-debug.js',
    '/js/laravel-notification-catcher.js',
    '/js/appointment-notification-debug.js',
    '/js/websocket-test.js',
    '/js/pusher-connection-test.js',
    '/js/connection-test.js',
    '/js/sounds/notification-sound.js',
    '/css/custom.css',
    '/css/style.css',
    '/css/medical.css',
    '/demos/medical/css/medical-icons.css'
];

// Install event - cache notification assets
self.addEventListener('install', event => {
    console.log('🛠️ Installing notification service worker');

    event.waitUntil(
        caches.open(NOTIFICATION_CACHE)
            .then(cache => {
                console.log('📦 Caching notification assets');
                return cache.addAll(NOTIFICATION_ASSETS);
            })
            .then(() => {
                console.log('✅ Notification assets cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('❌ Failed to cache notification assets:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('🚀 Activating notification service worker');

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== NOTIFICATION_CACHE) {
                        console.log('🗑️ Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('✅ Service worker activated and old caches cleaned');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve cached assets when offline
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only handle notification-related assets
    if (NOTIFICATION_ASSETS.some(asset => url.pathname.endsWith(asset))) {
        event.respondWith(
            caches.match(event.request)
                .then(response => {
                    if (response) {
                        console.log('📦 Serving cached asset:', url.pathname);
                        return response;
                    }

                    // If not in cache, try to fetch and cache
                    return fetch(event.request)
                        .then(response => {
                            if (response.ok) {
                                const responseClone = response.clone();
                                caches.open(NOTIFICATION_CACHE)
                                    .then(cache => cache.put(event.request, responseClone));
                            }
                            return response;
                        })
                        .catch(error => {
                            console.error('❌ Failed to fetch asset:', error);
                            // Return a basic fallback for critical assets
                            if (url.pathname.includes('notification.mp3')) {
                                return new Response('', { status: 404 });
                            }
                        });
                })
        );
    }
});

// Background sync for missed notifications
self.addEventListener('sync', event => {
    console.log('🔄 Background sync triggered:', event.tag);

    if (event.tag === 'notification-sync') {
        event.waitUntil(syncMissedNotifications());
    }
});

// Push event for handling push notifications
self.addEventListener('push', event => {
    console.log('📲 Push notification received');

    if (event.data) {
        const data = event.data.json();
        console.log('📋 Push data:', data);

        const options = {
            body: data.message || 'You have a new notification',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            vibrate: [200, 100, 200],
            data: {
                url: data.url || '/',
                notificationId: data.id
            },
            actions: [
                {
                    action: 'view',
                    title: 'View',
                    icon: '/icons/view.png'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss',
                    icon: '/icons/dismiss.png'
                }
            ],
            requireInteraction: true,
            silent: false
        };

        event.waitUntil(
            self.registration.showNotification(data.title || 'Medicine-AI', options)
                .then(() => {
                    // Store notification locally for offline access
                    return storeNotificationLocally(data);
                })
        );
    }
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('🔔 Notification clicked:', event.action);

    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Check if there's already a window/tab open with the target URL
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }

                // If no window/tab is open, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Message event for communication with the main thread
self.addEventListener('message', event => {
    console.log('💬 Message received from main thread:', event.data);

    const { type, data } = event.data;

    switch (type) {
        case 'STORE_NOTIFICATION':
            storeNotificationLocally(data);
            break;

        case 'GET_STORED_NOTIFICATIONS':
            getStoredNotifications().then(notifications => {
                event.ports[0].postMessage({
                    type: 'STORED_NOTIFICATIONS',
                    notifications: notifications
                });
            });
            break;

        case 'CLEAR_STORED_NOTIFICATIONS':
            clearStoredNotifications();
            break;

        case 'SYNC_NOTIFICATIONS':
            syncMissedNotifications();
            break;

        default:
            console.log('❓ Unknown message type:', type);
    }
});

// Store notification locally using IndexedDB
async function storeNotificationLocally(notification) {
    try {
        const db = await openNotificationDB();
        const transaction = db.transaction(['notifications'], 'readwrite');
        const store = transaction.objectStore('notifications');

        // Add timestamp if not present
        if (!notification.timestamp) {
            notification.timestamp = Date.now();
        }

        // Store the notification
        await new Promise((resolve, reject) => {
            const request = store.add(notification);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });

        console.log('💾 Notification stored locally:', notification.id);

        // Notify clients about the new notification
        notifyClients('notification-stored', notification);

    } catch (error) {
        console.error('❌ Failed to store notification locally:', error);
    }
}

// Get stored notifications from IndexedDB
async function getStoredNotifications() {
    try {
        const db = await openNotificationDB();
        const transaction = db.transaction(['notifications'], 'readonly');
        const store = transaction.objectStore('notifications');

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => {
                const notifications = request.result;
                console.log('📋 Retrieved stored notifications:', notifications.length);
                resolve(notifications);
            };
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('❌ Failed to get stored notifications:', error);
        return [];
    }
}

// Clear stored notifications
async function clearStoredNotifications() {
    try {
        const db = await openNotificationDB();
        const transaction = db.transaction(['notifications'], 'readwrite');
        const store = transaction.objectStore('notifications');

        await new Promise((resolve, reject) => {
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });

        console.log('🗑️ Cleared stored notifications');
    } catch (error) {
        console.error('❌ Failed to clear stored notifications:', error);
    }
}

// Sync missed notifications when connection is restored
async function syncMissedNotifications() {
    console.log('🔄 Syncing missed notifications');

    try {
        const storedNotifications = await getStoredNotifications();

        if (storedNotifications.length === 0) {
            console.log('📭 No stored notifications to sync');
            return;
        }

        // Try to send stored notifications to the server
        for (const notification of storedNotifications) {
            try {
                const response = await fetch('/api/notifications/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(notification)
                });

                if (response.ok) {
                    console.log('✅ Notification synced successfully:', notification.id);
                    // Remove from local storage
                    await removeNotificationFromStorage(notification.id);
                } else {
                    console.warn('⚠️ Failed to sync notification:', notification.id, response.status);
                }
            } catch (error) {
                console.error('❌ Error syncing notification:', notification.id, error);
            }
        }

        // Notify clients that sync is complete
        notifyClients('notifications-synced', { count: storedNotifications.length });

    } catch (error) {
        console.error('❌ Failed to sync missed notifications:', error);
    }
}

// Remove notification from local storage
async function removeNotificationFromStorage(notificationId) {
    try {
        const db = await openNotificationDB();
        const transaction = db.transaction(['notifications'], 'readwrite');
        const store = transaction.objectStore('notifications');

        await new Promise((resolve, reject) => {
            const request = store.delete(notificationId);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });

        console.log('🗑️ Removed notification from storage:', notificationId);
    } catch (error) {
        console.error('❌ Failed to remove notification from storage:', error);
    }
}

// Open IndexedDB for notifications
function openNotificationDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedicineAINotifications', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            // Create notifications object store
            if (!db.objectStoreNames.contains('notifications')) {
                const store = db.createObjectStore('notifications', { keyPath: 'id' });
                store.createIndex('timestamp', 'timestamp', { unique: false });
                console.log('📦 Created notifications object store');
            }
        };
    });
}

// Notify all clients about events
async function notifyClients(eventType, data) {
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
        client.postMessage({
            type: eventType,
            data: data
        });
    });
}

// Get CSRF token (this would need to be passed from the main thread)
function getCsrfToken() {
    // This should be updated when the service worker receives messages from the main thread
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Periodic sync for regular notification checks (if supported)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'notification-check') {
        event.waitUntil(checkForNewNotifications());
    }
});

// Check for new notifications periodically
async function checkForNewNotifications() {
    console.log('🔍 Checking for new notifications');

    try {
        const response = await fetch('/api/notifications/check', {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.notifications && data.notifications.length > 0) {
                console.log('🔔 Found new notifications:', data.notifications.length);

                // Store new notifications locally
                for (const notification of data.notifications) {
                    await storeNotificationLocally(notification);
                }

                // Notify clients
                notifyClients('new-notifications-found', data.notifications);
            }
        }
    } catch (error) {
        console.error('❌ Failed to check for new notifications:', error);
    }
}

console.log('🚀 Medicine-AI Notification Service Worker loaded');
