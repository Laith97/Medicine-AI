// Offline Notification Manager
// Handles offline capabilities for the notification system

class OfflineNotificationManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.serviceWorker = null;
        this.db = null;
        this.syncInProgress = false;
        this.init();
    }

    async init() {

        // Register service worker
        await this.registerServiceWorker();

        // Initialize IndexedDB
        await this.initIndexedDB();

        // Setup event listeners
        this.setupEventListeners();

        // Check for stored notifications
        await this.checkStoredNotifications();

        // Offline Notification Manager initialized
    }

    // Register the service worker
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                

                const registration = await navigator.serviceWorker.register('/sw.js', {
                    scope: '/'
                });

                this.serviceWorker = registration;

                

                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    

                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            
                            this.showUpdateNotification();
                        }
                    });
                });

                // Handle messages from service worker
                navigator.serviceWorker.addEventListener('message', this.handleServiceWorkerMessage.bind(this));

            } catch (error) {
                
            }
        } else {
            
        }
    }

    // Initialize IndexedDB for local storage
    async initIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('MedicineAINotifications', 1);

            request.onerror = () => {
                
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create notifications object store
                if (!db.objectStoreNames.contains('notifications')) {
                    const store = db.createObjectStore('notifications', { keyPath: 'id' });
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                    
                }

                // Create sync queue object store
                if (!db.objectStoreNames.contains('syncQueue')) {
                    const syncStore = db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
                    syncStore.createIndex('timestamp', 'timestamp', { unique: false });
                    
                }
            };
        });
    }

    // Setup event listeners for online/offline status
    setupEventListeners() {
        window.addEventListener('online', this.handleOnline.bind(this));
        window.addEventListener('offline', this.handleOffline.bind(this));

        // Listen for visibility changes to sync when app becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isOnline) {
                this.syncStoredNotifications();
            }
        });
    }

    // Handle coming online
    async handleOnline() {
        
        this.isOnline = true;

        // Show connection restored notification
        this.showConnectionNotification('Connection restored', 'success');

        // Sync any stored notifications
        await this.syncStoredNotifications();

        // Register background sync if supported
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('notification-sync');
                // Background sync registered
            } catch (error) {
                // Background sync registration failed
            }
        }
    }

    // Handle going offline
    handleOffline() {
        
        this.isOnline = false;

        // Show offline notification
        this.showConnectionNotification('You are offline. Notifications will be stored locally.', 'warning');
    }

    // Store notification locally when offline
    async storeNotificationLocally(notification) {
        if (!this.db) {
            // IndexedDB not initialized
            return;
        }

        try {
            const transaction = this.db.transaction(['notifications'], 'readwrite');
            const store = transaction.objectStore('notifications');

            // Add metadata
            const offlineNotification = {
                ...notification,
                id: notification.id || `offline-${Date.now()}`,
                timestamp: Date.now(),
                offline: true,
                synced: false
            };

            await new Promise((resolve, reject) => {
                const request = store.add(offlineNotification);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });

            

            // Also add to sync queue
            await this.addToSyncQueue(offlineNotification);

        } catch (error) {
            
        }
    }

    // Add notification to sync queue
    async addToSyncQueue(notification) {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['syncQueue'], 'readwrite');
            const store = transaction.objectStore('syncQueue');

            const syncItem = {
                notification: notification,
                timestamp: Date.now(),
                attempts: 0
            };

            await new Promise((resolve, reject) => {
                const request = store.add(syncItem);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });

            
        } catch (error) {
            
        }
    }

    // Sync stored notifications when online
    async syncStoredNotifications() {
        if (!this.isOnline || this.syncInProgress || !this.db) {
            return;
        }

        this.syncInProgress = true;
        

        try {
            const transaction = this.db.transaction(['syncQueue'], 'readwrite');
            const store = transaction.objectStore('syncQueue');

            const syncItems = await new Promise((resolve, reject) => {
                const request = store.getAll();
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });

            

            for (const item of syncItems) {
                try {
                    const success = await this.syncNotification(item.notification);

                    if (success) {
                        // Remove from sync queue
                        await this.removeFromSyncQueue(item.id);

                        // Mark as synced in notifications store
                        await this.markNotificationSynced(item.notification.id);

                        
                    } else {
                        // Increment attempt count
                        item.attempts++;
                        if (item.attempts < 3) {
                            await this.updateSyncQueueItem(item);
                        } else {
                            
                            await this.removeFromSyncQueue(item.id);
                        }
                    }
                } catch (error) {
                    // Error syncing notification
                    item.attempts++;
                    if (item.attempts < 3) {
                        await this.updateSyncQueueItem(item);
                    } else {
                        await this.removeFromSyncQueue(item.id);
                    }
                }
            }

            if (syncItems.length > 0) {
                this.showConnectionNotification(`Synced ${syncItems.length} offline notifications`, 'success');

                // Trigger enhanced notification system to process synced notifications
                if (window.enhancedNotificationSystem && window.enhancedNotificationSystem.syncOfflineNotifications) {
                    window.enhancedNotificationSystem.syncOfflineNotifications();
                }
            }

        } catch (error) {
            
        } finally {
            this.syncInProgress = false;
        }
    }

    // Sync a single notification
    async syncNotification(notification) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/api/notifications/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ...notification,
                    synced: true
                })
            });

            return response.ok;
        } catch (error) {
            
            return false;
        }
    }

    // Remove item from sync queue
    async removeFromSyncQueue(id) {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['syncQueue'], 'readwrite');
            const store = transaction.objectStore('syncQueue');

            await new Promise((resolve, reject) => {
                const request = store.delete(id);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            
        }
    }

    // Update sync queue item
    async updateSyncQueueItem(item) {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['syncQueue'], 'readwrite');
            const store = transaction.objectStore('syncQueue');

            await new Promise((resolve, reject) => {
                const request = store.put(item);
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            
        }
    }

    // Mark notification as synced
    async markNotificationSynced(notificationId) {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['notifications'], 'readwrite');
            const store = transaction.objectStore('notifications');

            const notification = await new Promise((resolve, reject) => {
                const request = store.get(notificationId);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });

            if (notification) {
                notification.synced = true;
                await new Promise((resolve, reject) => {
                    const request = store.put(notification);
                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                });
            }
        } catch (error) {
            
        }
    }

    // Check for stored notifications on startup
    async checkStoredNotifications() {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['notifications'], 'readonly');
            const store = transaction.objectStore('notifications');

            const notifications = await new Promise((resolve, reject) => {
                const request = store.getAll();
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });

            const unsyncedCount = notifications.filter(n => !n.synced).length;

            if (unsyncedCount > 0) {
                
                this.showConnectionNotification(`${unsyncedCount} offline notifications ready to sync`, 'info');

                if (this.isOnline) {
                    this.syncStoredNotifications();
                }
            }
        } catch (error) {
            
        }
    }

    // Handle messages from service worker
    handleServiceWorkerMessage(event) {
        const { type, data } = event.data;

        switch (type) {
            case 'notification-stored':
                
                break;

            case 'notifications-synced':
                
                this.showConnectionNotification(`Synced ${data.count} notifications from service worker`, 'success');
                break;

            case 'new-notifications-found':
                
                
                data.forEach(notification => {
                    if (window.enhancedNotificationSystem) {
                        window.enhancedNotificationSystem.handleNewNotification(notification, 'service-worker');
                    }
                });
                break;

            default:
                
        }
    }

    // Show connection status notification
    showConnectionNotification(message, type = 'info') {
        if (window.enhancedNotificationSystem) {
            window.enhancedNotificationSystem.showToastNotification({
                id: `connection-${Date.now()}`,
                title: 'Connection Status',
                message: message,
                type: type
            });
        } else {
            // Fallback to browser notification if enhanced system not available
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Medicine-AI', {
                    body: message,
                    icon: '/favicon.ico'
                });
            }
        }
    }

    // Show update notification
    showUpdateNotification() {
        this.showConnectionNotification('App updated! Please refresh for the latest features.', 'info');
    }

    // Public methods for external use
    async getStoredNotifications() {
        if (!this.db) return [];

        try {
            const transaction = this.db.transaction(['notifications'], 'readonly');
            const store = transaction.objectStore('notifications');

            return await new Promise((resolve, reject) => {
                const request = store.getAll();
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        } catch (error) {
            
            return [];
        }
    }

    async clearStoredNotifications() {
        if (!this.db) return;

        try {
            const transaction = this.db.transaction(['notifications', 'syncQueue'], 'readwrite');

            await Promise.all([
                new Promise((resolve, reject) => {
                    const request = transaction.objectStore('notifications').clear();
                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                }),
                new Promise((resolve, reject) => {
                    const request = transaction.objectStore('syncQueue').clear();
                    request.onsuccess = () => resolve();
                    request.onerror = () => reject(request.error);
                })
            ]);

            
        } catch (error) {
            
        }
    }

    // Force sync (can be called manually)
    async forceSync() {
        if (this.isOnline) {
            await this.syncStoredNotifications();
        } else {
            this.showConnectionNotification('Cannot sync while offline', 'warning');
        }
    }

    // Get sync status
    getSyncStatus() {
        return {
            isOnline: this.isOnline,
            syncInProgress: this.syncInProgress,
            serviceWorkerRegistered: !!this.serviceWorker
        };
    }
}

// Initialize offline notification manager
if (!window.offlineNotificationManager) {
    window.offlineNotificationManager = new OfflineNotificationManager();
}

export default OfflineNotificationManager;
