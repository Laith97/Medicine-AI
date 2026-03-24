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
    // Installing notification service worker

    event.waitUntil(
        caches.open(NOTIFICATION_CACHE)
            .then(cache => {
                // Caching notification assets
                return cache.addAll(NOTIFICATION_ASSETS);
            })
            .then(() => {
                // Notification assets cached successfully
                return self.skipWaiting();
            })
            .catch(error => {
                // Failed to cache notification assets
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    // Activating notification service worker

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== NOTIFICATION_CACHE) {
                        // Deleting old cache:
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Service worker activated and old caches cleaned
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
                        // Serving cached asset:
                        return response;
                    }

                    // If not in cache, try to fetch and cache
                    return fetch(event.request)
                        .then(response => {
                            // Only cache successful, complete responses (not partial 206 responses)
                            if (response.ok && response.status === 200 && !response.headers.get('content-range')) {
                                const responseClone = response.clone();
                                caches.open(NOTIFICATION_CACHE)
                                    .then(cache => cache.put(event.request, responseClone))
                                    .catch(error => {
                                        // console.warn('⚠️ Failed to cache response:', error);
                                    });
                            }
                            return response;
                        })
                        .catch(error => {
                            // Failed to fetch asset:
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
    // Background sync triggered:

    if (event.tag === 'notification-sync') {
        event.waitUntil(syncMissedNotifications());
    }
});

// Push event for handling push notifications
self.addEventListener('push', event => {
    // Push notification received

    if (event.data) {
        const data = event.data.json();
        // Push data:

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
    // Notification clicked:

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
    // Message received from main thread:

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
            // Unknown message type:
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

        // Notification stored locally:

        // Notify clients about the new notification
        notifyClients('notification-stored', notification);

    } catch (error) {
        // Failed to store notification locally:
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
                // Retrieved stored notifications:
                resolve(notifications);
            };
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        // Failed to get stored notifications:
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

        // Cleared stored notifications
    } catch (error) {
        // Failed to clear stored notifications:
    }
}

// Sync missed notifications when connection is restored
async function syncMissedNotifications() {
    // Syncing missed notifications

    try {
        const storedNotifications = await getStoredNotifications();

        if (storedNotifications.length === 0) {
            // No stored notifications to sync
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
                    // Notification synced successfully:
                    // Remove from local storage
                    await removeNotificationFromStorage(notification.id);
                } else {
                    // Failed to sync notification:
                }
            } catch (error) {
                // Error syncing notification:
            }
        }

        // Notify clients that sync is complete
        notifyClients('notifications-synced', { count: storedNotifications.length });

    } catch (error) {
        // Failed to sync missed notifications:
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

        // Removed notification from storage:
    } catch (error) {
        // Failed to remove notification from storage:
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
                // Created notifications object store
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
    // Checking for new notifications

    try {
        const response = await fetch('/api/notifications/check', {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.notifications && data.notifications.length > 0) {
                // Found new notifications:

                // Store new notifications locally
                for (const notification of data.notifications) {
                    await storeNotificationLocally(notification);
                }

                // Notify clients
                notifyClients('new-notifications-found', data.notifications);
            }
        }
    } catch (error) {
        // Failed to check for new notifications:
    }
}

// Medicine-AI Notification Service Worker loaded
