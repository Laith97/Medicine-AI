// Notification System Debug Tool
class NotificationDebugger {
    constructor() {
        this.debugPanel = null;
        this.logs = [];
        this.maxLogs = 50;
        this.init();
    }

    init() {
        this.createDebugPanel();
        this.setupEchoDebugger();
        this.log('Debug system initialized');
    }

    createDebugPanel() {
        // Create debug panel
        const panel = document.createElement('div');
        panel.id = 'notification-debug-panel';
        panel.style.cssText = `
            position: fixed;
            top: 10px;
            left: 10px;
            width: 400px;
            max-height: 600px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            font-family: monospace;
            font-size: 12px;
            border-radius: 8px;
            padding: 10px;
            z-index: 10000;
            overflow-y: auto;
            display: none;
        `;

        panel.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                <strong>🔔 Notification Debug</strong>
                <div>
                    <button onclick="notificationDebugger.clear()" style="background: #555; color: white; border: none; padding: 2px 8px; margin: 0 2px; border-radius: 3px; cursor: pointer;">Clear</button>
                    <button onclick="notificationDebugger.testNotification()" style="background: #007bff; color: white; border: none; padding: 2px 8px; margin: 0 2px; border-radius: 3px; cursor: pointer;">Test</button>
                    <button onclick="notificationDebugger.toggle()" style="background: #dc3545; color: white; border: none; padding: 2px 8px; margin: 0 2px; border-radius: 3px; cursor: pointer;">Hide</button>
                </div>
            </div>
            <div id="debug-logs"></div>
        `;

        document.body.appendChild(panel);
        this.debugPanel = panel;

        // Add keyboard shortcut to toggle debug panel (Ctrl+Shift+N)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'N') {
                this.toggle();
            }
        });

        this.log('Debug panel created. Press Ctrl+Shift+N to toggle.');
    }

    setupEchoDebugger() {
        // Wait for Echo to be ready
        const checkEcho = () => {
            if (typeof window.Echo !== 'undefined') {
                this.log('✅ Echo is available');
                this.log(`📡 Echo connector: ${window.Echo.connector?.name || 'unknown'}`);

                // Debug Pusher connection
                if (window.Pusher && window.Echo.connector?.pusher) {
                    const pusher = window.Echo.connector.pusher;

                    pusher.connection.bind('connected', () => {
                        this.log('✅ Pusher connected');
                    });

                    pusher.connection.bind('disconnected', () => {
                        this.log('❌ Pusher disconnected');
                    });

                    pusher.connection.bind('error', (error) => {
                        this.log(`❌ Pusher error: ${JSON.stringify(error)}`);
                    });

                    this.log(`📊 Pusher state: ${pusher.connection.state}`);
                }

                // Get user ID
                const userIdMeta = document.querySelector('meta[name="user-id"]');
                if (userIdMeta) {
                    const userId = userIdMeta.getAttribute('content');
                    this.log(`👤 User ID: ${userId}`);

                    // Listen for notifications on the user's private channel
                    try {
                        window.Echo.private(`App.User.${userId}`)
                            .notification((notification) => {
                                this.log('🔔 NOTIFICATION RECEIVED!');
                                this.log(`📋 Type: ${notification.type}`);
                                this.log(`📝 Title: ${notification.title}`);
                                this.log(`💬 Message: ${notification.message}`);
                                this.log(`🔗 Link: ${notification.link || 'none'}`);
                                this.log('📦 Full Data:', notification);
                            })
                            .error((error) => {
                                this.log(`❌ Echo channel error: ${error}`);
                            });

                        this.log(`📻 Listening on channel: App.User.${userId}`);
                    } catch (error) {
                        this.log(`❌ Failed to setup private channel: ${error.message}`);
                    }
                } else {
                    this.log('❌ User ID not found in meta tags');
                }

            } else {
                this.log('⏳ Waiting for Echo...');
                setTimeout(checkEcho, 1000);
            }
        };

        checkEcho();
    }

    log(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = {
            time: timestamp,
            message: message,
            data: data
        };

        this.logs.unshift(logEntry);

        // Keep only last N logs
        if (this.logs.length > this.maxLogs) {
            this.logs = this.logs.slice(0, this.maxLogs);
        }

        this.updateLogDisplay();
        console.log(`[${timestamp}] ${message}`, data || '');
    }

    updateLogDisplay() {
        if (!this.debugPanel) return;

        const logsContainer = this.debugPanel.querySelector('#debug-logs');
        if (!logsContainer) return;

        logsContainer.innerHTML = this.logs.map(log => {
            const dataStr = log.data ? `\n   ${JSON.stringify(log.data, null, 2)}` : '';
            return `<div style="margin: 2px 0; padding: 2px 0; border-bottom: 1px solid #222;">
                        <span style="color: #888;">[${log.time}]</span>
                        <span style="color: #fff;">${log.message}</span>
                        ${dataStr ? `<pre style="color: #aaa; font-size: 10px; margin: 2px 0;">${dataStr}</pre>` : ''}
                    </div>`;
        }).join('');

        // Auto scroll to top
        logsContainer.scrollTop = 0;
    }

    async testNotification() {
        try {
            this.log('🧪 Sending test notification...');
            const response = await fetch('/notifications/test');
            const result = await response.json();
            this.log('✅ Test notification sent', result);
        } catch (error) {
            this.log('❌ Failed to send test notification', error.message);
        }
    }

    toggle() {
        if (!this.debugPanel) return;

        const isVisible = this.debugPanel.style.display !== 'none';
        this.debugPanel.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            this.log('👀 Debug panel opened');
        }
    }

    show() {
        if (this.debugPanel) {
            this.debugPanel.style.display = 'block';
        }
    }

    hide() {
        if (this.debugPanel) {
            this.debugPanel.style.display = 'none';
        }
    }

    clear() {
        this.logs = [];
        this.updateLogDisplay();
        this.log('🧹 Debug log cleared');
    }
}

// Initialize debugger when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationDebugger = new NotificationDebugger();
    });
} else {
    window.notificationDebugger = new NotificationDebugger();
}
