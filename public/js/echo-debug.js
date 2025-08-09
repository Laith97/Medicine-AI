// Advanced Echo/Pusher Debugging Tool
class EchoDebugger {
    constructor() {
        this.logs = [];
        this.isDebugging = true;
        this.panel = null;
        this.init();
    }

    init() {
        this.createPanel();
        this.setupEchoDebugging();
        this.log('🔧 Echo Debugger initialized');
    }

    createPanel() {
        const panel = document.createElement('div');
        panel.id = 'echo-debug-panel';
        panel.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            width: 500px;
            max-height: 400px;
            background: rgba(0, 0, 0, 0.95);
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            border: 2px solid #00ff00;
            border-radius: 8px;
            padding: 10px;
            z-index: 10001;
            overflow-y: auto;
            display: none;
        `;

        panel.innerHTML = `
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #00ff00; padding-bottom: 5px;">
                <strong style="color: #00ffff;">📡 ECHO DEBUGGER</strong>
                <div>
                    <button onclick="echoDebugger.testConnection()" style="background: #333; color: #00ff00; border: 1px solid #00ff00; padding: 2px 6px; margin: 0 2px; border-radius: 3px; cursor: pointer; font-size: 10px;">Test</button>
                    <button onclick="echoDebugger.clear()" style="background: #333; color: #00ff00; border: 1px solid #00ff00; padding: 2px 6px; margin: 0 2px; border-radius: 3px; cursor: pointer; font-size: 10px;">Clear</button>
                    <button onclick="echoDebugger.toggle()" style="background: #333; color: #ff0000; border: 1px solid #ff0000; padding: 2px 6px; margin: 0 2px; border-radius: 3px; cursor: pointer; font-size: 10px;">Hide</button>
                </div>
            </div>
            <div id="echo-debug-logs" style="max-height: 300px; overflow-y: auto;"></div>
        `;

        document.body.appendChild(panel);
        this.panel = panel;

        // Auto show for debugging
        this.show();

        // Keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                this.toggle();
            }
        });

        this.log('🎮 Press Ctrl+Shift+E to toggle this panel');
    }

    setupEchoDebugging() {
        // Check if dependencies are loaded
        this.checkDependencies();

        // Wait for Echo to be ready
        const waitForEcho = () => {
            if (typeof window.Echo !== 'undefined') {
                this.debugEcho();
            } else {
                this.log('⏳ Waiting for Echo to load...');
                setTimeout(waitForEcho, 1000);
            }
        };

        waitForEcho();
    }

    checkDependencies() {
        this.log('🔍 Checking dependencies...');

        // Check Pusher
        if (typeof window.Pusher !== 'undefined') {
            this.log('✅ Pusher loaded');
            this.log(`📦 Pusher version: ${window.Pusher.VERSION || 'unknown'}`);
        } else {
            this.error('❌ Pusher not loaded!');
        }

        // Check Laravel Echo
        if (typeof window.Echo !== 'undefined') {
            this.log('✅ Laravel Echo loaded');
        } else {
            this.error('❌ Laravel Echo not loaded!');
        }

        // Check user authentication
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (userMeta) {
            const userId = userMeta.content;
            this.log(`👤 User ID: ${userId}`);
        } else {
            this.error('❌ User ID not found in meta tags!');
        }

        // Check CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            this.log('🔐 CSRF token found');
        } else {
            this.error('❌ CSRF token not found!');
        }
    }

    debugEcho() {
        this.log('🚀 Starting Echo debugging...');

        if (!window.Echo) {
            this.error('❌ Echo is not available');
            return;
        }

        // Check Echo connector
        if (window.Echo.connector) {
            this.log(`📡 Echo connector: ${window.Echo.connector.name || 'unknown'}`);

            // Debug Pusher connection if available
            if (window.Echo.connector.pusher) {
                this.debugPusher(window.Echo.connector.pusher);
            }
        } else {
            this.error('❌ Echo connector not found');
        }

        // Test private channel authentication
        this.testPrivateChannel();
    }

    debugPusher(pusher) {
        this.log('🔄 Debugging Pusher connection...');

        // Current connection state
        this.log(`📊 Current Pusher state: ${pusher.connection.state}`);

        // Bind to connection events
        pusher.connection.bind('connecting', () => {
            this.log('🔄 Pusher connecting...');
        });

        pusher.connection.bind('connected', () => {
            this.log('✅ Pusher connected!');
            this.log(`🆔 Socket ID: ${pusher.connection.socket_id}`);
        });

        pusher.connection.bind('unavailable', () => {
            this.error('❌ Pusher unavailable');
        });

        pusher.connection.bind('failed', () => {
            this.error('❌ Pusher connection failed');
        });

        pusher.connection.bind('disconnected', () => {
            this.error('❌ Pusher disconnected');
        });

        pusher.connection.bind('error', (error) => {
            this.error(`❌ Pusher error: ${JSON.stringify(error)}`);
        });

        // Bind to all events for debugging
        pusher.bind_global((eventName, data) => {
            if (eventName.startsWith('pusher:')) {
                // Internal Pusher events
                this.log(`🔧 Pusher internal: ${eventName}`);
            } else {
                // Application events
                this.log(`📨 Event received: ${eventName}`);
                this.log(`📦 Event data:`, data);
            }
        });
    }

    testPrivateChannel() {
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (!userMeta) {
            this.error('❌ Cannot test private channel - no user ID');
            return;
        }

        const userId = userMeta.content;
        const channelName = `App.User.${userId}`;

        this.log(`🔒 Testing private channel: ${channelName}`);

        try {
            const channel = window.Echo.private(channelName);

            this.log('✅ Private channel created');

            // Bind to channel events
            channel.subscribed(() => {
                this.log('✅ Successfully subscribed to private channel!');
            });

            channel.error((error) => {
                this.error(`❌ Private channel error: ${JSON.stringify(error)}`);
            });

            // Listen for notifications
            channel.notification((notification) => {
                this.log('🔔 NOTIFICATION RECEIVED!');
                this.log('📋 Notification data:', notification);
            });

            // Listen for any Laravel events
            channel.listen('.notification', (e) => {
                this.log('🔔 Laravel notification event received!');
                this.log('📦 Event data:', e);
            });

        } catch (error) {
            this.error(`❌ Failed to create private channel: ${error.message}`);
        }
    }

    async testConnection() {
        this.log('🧪 Testing connection...');

        // Test API endpoint
        try {
            const response = await fetch('/api/notifications');
            if (response.ok) {
                const data = await response.json();
                this.log('✅ API endpoint working');
                this.log(`📊 Unread count: ${data.unread_count}`);
            } else {
                this.error(`❌ API error: ${response.status}`);
            }
        } catch (error) {
            this.error(`❌ API fetch error: ${error.message}`);
        }

        // Test notification endpoint
        try {
            this.log('🧪 Sending test notification...');
            const response = await fetch('/notifications/test');
            const result = await response.json();
            this.log('📨 Test notification response:', result);
        } catch (error) {
            this.error(`❌ Test notification error: ${error.message}`);
        }
    }

    log(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = {
            time: timestamp,
            type: 'log',
            message: message,
            data: data
        };

        this.logs.unshift(logEntry);
        this.updateDisplay();
        console.log(`[Echo Debug ${timestamp}] ${message}`, data || '');
    }

    error(message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = {
            time: timestamp,
            type: 'error',
            message: message,
            data: data
        };

        this.logs.unshift(logEntry);
        this.updateDisplay();
        console.error(`[Echo Debug ${timestamp}] ${message}`, data || '');
    }

    updateDisplay() {
        if (!this.panel) return;

        const logsContainer = this.panel.querySelector('#echo-debug-logs');
        if (!logsContainer) return;

        logsContainer.innerHTML = this.logs.slice(0, 50).map(log => {
            const color = log.type === 'error' ? '#ff4444' : '#00ff00';
            const dataStr = log.data ? `\n${JSON.stringify(log.data, null, 2)}` : '';

            return `<div style="margin: 2px 0; padding: 2px 0; border-bottom: 1px solid #333; font-size: 10px;">
                        <span style="color: #888;">[${log.time}]</span>
                        <span style="color: ${color};">${log.message}</span>
                        ${dataStr ? `<pre style="color: #aaa; font-size: 9px; margin: 2px 0;">${dataStr}</pre>` : ''}
                    </div>`;
        }).join('');

        logsContainer.scrollTop = 0;
    }

    show() {
        if (this.panel) this.panel.style.display = 'block';
    }

    hide() {
        if (this.panel) this.panel.style.display = 'none';
    }

    toggle() {
        if (!this.panel) return;
        const isVisible = this.panel.style.display !== 'none';
        this.panel.style.display = isVisible ? 'none' : 'block';
    }

    clear() {
        this.logs = [];
        this.updateDisplay();
        this.log('🧹 Logs cleared');
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.echoDebugger = new EchoDebugger();
    });
} else {
    window.echoDebugger = new EchoDebugger();
}
