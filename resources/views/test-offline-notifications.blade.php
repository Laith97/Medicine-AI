@extends('master')

@section('title', 'Offline Notifications Test')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-wifi-off me-2"></i>
                        Offline Notifications Test
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        System Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Connection Status:</strong>
                                        <span id="connection-status" class="badge bg-success">Online</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Service Worker:</strong>
                                        <span id="sw-status" class="badge bg-secondary">Checking...</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Offline Storage:</strong>
                                        <span id="storage-status" class="badge bg-secondary">Checking...</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Stored Notifications:</strong>
                                        <span id="stored-count" class="badge bg-info">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-gear me-2"></i>
                                        Test Controls
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button id="test-notification-btn" class="btn btn-primary">
                                            <i class="bi bi-bell me-2"></i>
                                            Send Test Notification
                                        </button>
                                        <button id="simulate-offline-btn" class="btn btn-warning">
                                            <i class="bi bi-wifi-off me-2"></i>
                                            Simulate Offline
                                        </button>
                                        <button id="simulate-online-btn" class="btn btn-success">
                                            <i class="bi bi-wifi me-2"></i>
                                            Simulate Online
                                        </button>
                                        <button id="force-sync-btn" class="btn btn-info">
                                            <i class="bi bi-arrow-clockwise me-2"></i>
                                            Force Sync
                                        </button>
                                        <button id="clear-storage-btn" class="btn btn-danger">
                                            <i class="bi bi-trash me-2"></i>
                                            Clear Storage
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-list me-2"></i>
                                        Stored Notifications
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="stored-notifications" class="list-group">
                                        <div class="list-group-item text-center text-muted">
                                            <i class="bi bi-info-circle me-2"></i>
                                            No stored notifications
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-terminal me-2"></i>
                                        Debug Console
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="debug-console" class="bg-dark text-light p-3 rounded" style="height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                        <div>🔌 Initializing offline notification test...</div>
                                    </div>
                                    <div class="mt-2">
                                        <button id="clear-console-btn" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-trash me-2"></i>
                                            Clear Console
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Offline Notifications Test
class OfflineNotificationsTest {
    constructor() {
        this.debugConsole = document.getElementById('debug-console');
        this.init();
    }

    init() {
        this.log('🚀 Initializing Offline Notifications Test');
        this.setupEventListeners();
        this.updateStatus();
        this.loadStoredNotifications();
    }

    setupEventListeners() {
        // Test notification button
        document.getElementById('test-notification-btn').addEventListener('click', () => {
            this.sendTestNotification();
        });

        // Simulate offline button
        document.getElementById('simulate-offline-btn').addEventListener('click', () => {
            this.simulateOffline();
        });

        // Simulate online button
        document.getElementById('simulate-online-btn').addEventListener('click', () => {
            this.simulateOnline();
        });

        // Force sync button
        document.getElementById('force-sync-btn').addEventListener('click', () => {
            this.forceSync();
        });

        // Clear storage button
        document.getElementById('clear-storage-btn').addEventListener('click', () => {
            this.clearStorage();
        });

        // Clear console button
        document.getElementById('clear-console-btn').addEventListener('click', () => {
            this.clearConsole();
        });

        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.log('🌐 Browser reports online');
            this.updateStatus();
        });

        window.addEventListener('offline', () => {
            this.log('📴 Browser reports offline');
            this.updateStatus();
        });
    }

    async updateStatus() {
        // Update connection status
        const isOnline = navigator.onLine;
        const statusElement = document.getElementById('connection-status');
        statusElement.textContent = isOnline ? 'Online' : 'Offline';
        statusElement.className = `badge ${isOnline ? 'bg-success' : 'bg-danger'}`;

        // Update service worker status
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.ready;
                document.getElementById('sw-status').textContent = 'Active';
                document.getElementById('sw-status').className = 'badge bg-success';
                this.log('✅ Service worker is active');
            } catch (error) {
                document.getElementById('sw-status').textContent = 'Inactive';
                document.getElementById('sw-status').className = 'badge bg-danger';
                this.log('❌ Service worker error:', error);
            }
        } else {
            document.getElementById('sw-status').textContent = 'Not Supported';
            document.getElementById('sw-status').className = 'badge bg-warning';
            this.log('⚠️ Service workers not supported');
        }

        // Update storage status
        if (window.indexedDB) {
            document.getElementById('storage-status').textContent = 'Available';
            document.getElementById('storage-status').className = 'badge bg-success';
        } else {
            document.getElementById('storage-status').textContent = 'Not Supported';
            document.getElementById('storage-status').className = 'badge bg-danger';
        }
    }

    async sendTestNotification() {
        this.log('🔔 Sending test notification...');

        try {
            const response = await fetch('/notifications/test', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.log('✅ Test notification sent:', result);
            } else {
                this.log('❌ Failed to send test notification:', response.status);
            }
        } catch (error) {
            this.log('❌ Error sending test notification:', error);
        }
    }

    simulateOffline() {
        this.log('📴 Simulating offline mode...');
        // Note: This is just for testing UI, actual offline simulation requires network tools
        Object.defineProperty(navigator, 'onLine', {
            writable: true,
            value: false
        });
        window.dispatchEvent(new Event('offline'));
        this.log('📴 Offline mode simulated (use browser dev tools for actual offline testing)');
    }

    simulateOnline() {
        this.log('🌐 Simulating online mode...');
        Object.defineProperty(navigator, 'onLine', {
            writable: true,
            value: true
        });
        window.dispatchEvent(new Event('online'));
        this.log('🌐 Online mode simulated');
    }

    async forceSync() {
        this.log('🔄 Forcing notification sync...');

        if (window.offlineNotificationManager) {
            await window.offlineNotificationManager.forceSync();
            this.log('✅ Sync completed');
            this.loadStoredNotifications();
        } else {
            this.log('❌ Offline notification manager not available');
        }
    }

    async clearStorage() {
        this.log('🗑️ Clearing offline storage...');

        if (window.offlineNotificationManager) {
            await window.offlineNotificationManager.clearStoredNotifications();
            this.log('✅ Storage cleared');
            this.loadStoredNotifications();
        } else {
            this.log('❌ Offline notification manager not available');
        }
    }

    async loadStoredNotifications() {
        if (!window.offlineNotificationManager) {
            this.log('❌ Offline notification manager not available');
            return;
        }

        try {
            const notifications = await window.offlineNotificationManager.getStoredNotifications();
            document.getElementById('stored-count').textContent = notifications.length;

            const container = document.getElementById('stored-notifications');

            if (notifications.length === 0) {
                container.innerHTML = `
                    <div class="list-group-item text-center text-muted">
                        <i class="bi bi-info-circle me-2"></i>
                        No stored notifications
                    </div>
                `;
                return;
            }

            container.innerHTML = notifications.map(notification => `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${notification.title || 'Notification'}</h6>
                        <small class="text-muted">${new Date(notification.timestamp).toLocaleString()}</small>
                    </div>
                    <p class="mb-1">${notification.message || 'No message'}</p>
                    <div class="d-flex gap-2">
                        <span class="badge ${notification.synced ? 'bg-success' : 'bg-warning'}">
                            ${notification.synced ? 'Synced' : 'Pending'}
                        </span>
                        <span class="badge ${notification.offline ? 'bg-info' : 'bg-secondary'}">
                            ${notification.offline ? 'Offline' : 'Online'}
                        </span>
                    </div>
                </div>
            `).join('');

            this.log(`📋 Loaded ${notifications.length} stored notifications`);
        } catch (error) {
            this.log('❌ Error loading stored notifications:', error);
        }
    }

    log(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = `[${timestamp}] ${message}`;

        if (data) {
            console.log(message, data);
        } else {
            console.log(message);
        }

        // Add to debug console
        const logElement = document.createElement('div');
        logElement.textContent = logEntry;
        this.debugConsole.appendChild(logElement);
        this.debugConsole.scrollTop = this.debugConsole.scrollHeight;
    }

    clearConsole() {
        this.debugConsole.innerHTML = '';
        this.log('🧹 Console cleared');
    }
}

// Initialize test when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.offlineNotificationsTest = new OfflineNotificationsTest();
});

// Auto-refresh stored notifications every 5 seconds
setInterval(() => {
    if (window.offlineNotificationsTest) {
        window.offlineNotificationsTest.loadStoredNotifications();
    }
}, 5000);
</script>
@endsection
