// Comprehensive Notification System Tester
class NotificationTester {
    constructor() {
        this.results = [];
        this.init();
    }

    init() {
        console.log('🧪 Notification Tester initialized');
        // this.createTestInterface();
    }

    createTestInterface() {
        // Create floating test interface
        const testInterface = document.createElement('div');
        testInterface.id = 'notification-tester';
        testInterface.style.cssText = `
            position: fixed;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 10002;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 300px;
            display: none;
        `;

        testInterface.innerHTML = `
            <div style="text-align: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #fff;">🔔 Notification Tester</h3>
                <small>Press F2 to toggle</small>
            </div>

            <div style="margin: 10px 0;">
                <button onclick="notificationTester.runFullTest()" style="width: 100%; padding: 8px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 2px 0;">🚀 Full Test</button>
                <button onclick="notificationTester.testEcho()" style="width: 100%; padding: 8px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 2px 0;">📡 Test Echo</button>
                <button onclick="notificationTester.testSound()" style="width: 100%; padding: 8px; background: #FF9800; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 2px 0;">🔊 Test Sound</button>
                <button onclick="notificationTester.sendNotification()" style="width: 100%; padding: 8px; background: #9C27B0; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 2px 0;">💬 Send Notification</button>
                <button onclick="notificationTester.showResults()" style="width: 100%; padding: 8px; background: #607D8B; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 2px 0;">📊 Show Results</button>
            </div>

            <div id="test-status" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 5px; font-size: 12px; max-height: 200px; overflow-y: auto;">
                <p>Ready to test notifications...</p>
            </div>
        `;

        document.body.appendChild(testInterface);
        this.testInterface = testInterface;

        // Keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                this.toggle();
            }
        });

        // Auto-show for testing
        this.show();
    }

    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: '#4CAF50',
            warning: '#FF9800',
            error: '#F44336',
            success: '#8BC34A'
        };

        this.results.push({ timestamp, message, type });

        const statusDiv = document.getElementById('test-status');
        if (statusDiv) {
            statusDiv.innerHTML += `<div style="color: ${colors[type]}; margin: 2px 0;">[${timestamp}] ${message}</div>`;
            statusDiv.scrollTop = statusDiv.scrollHeight;
        }

        console.log(`[Tester ${timestamp}] ${message}`);
    }

    async runFullTest() {
        this.log('🚀 Starting full notification system test...', 'info');

        // Test 1: Dependencies
        this.log('1️⃣ Testing dependencies...', 'info');
        this.testDependencies();

        // Test 2: Echo Connection
        this.log('2️⃣ Testing Echo connection...', 'info');
        await this.testEcho();

        // Test 3: API Endpoints
        this.log('3️⃣ Testing API endpoints...', 'info');
        await this.testAPI();

        // Test 4: Sound System
        this.log('4️⃣ Testing sound system...', 'info');
        this.testSound();

        // Test 5: Send Real Notification
        this.log('5️⃣ Sending test notification...', 'info');
        await this.sendNotification();

        this.log('✅ Full test completed!', 'success');
    }

    testDependencies() {
        // Check Pusher
        if (typeof window.Pusher !== 'undefined') {
            this.log('✅ Pusher loaded', 'success');
        } else {
            this.log('❌ Pusher not loaded', 'error');
        }

        // Check Echo
        if (typeof window.Echo !== 'undefined') {
            this.log('✅ Echo loaded', 'success');
        } else {
            this.log('❌ Echo not loaded', 'error');
        }

        // Check notification system
        if (window.notificationSystem) {
            this.log('✅ Global notification system loaded', 'success');
        } else {
            this.log('❌ Global notification system not loaded', 'error');
        }

        // Check sound system
        if (window.notificationSound) {
            this.log('✅ Notification sound system loaded', 'success');
        } else {
            this.log('❌ Notification sound system not loaded', 'error');
        }

        // Check user auth
        const userMeta = document.querySelector('meta[name="user-id"]');
        if (userMeta) {
            this.log(`✅ User authenticated (ID: ${userMeta.content})`, 'success');
        } else {
            this.log('❌ User not authenticated', 'error');
        }
    }

    async testEcho() {
        if (!window.Echo) {
            this.log('❌ Echo not available for testing', 'error');
            return;
        }

        try {
            const pusher = window.Echo.connector.pusher;
            const state = pusher.connection.state;

            this.log(`📊 Pusher connection state: ${state}`, 'info');

            if (state === 'connected') {
                this.log('✅ Pusher connected successfully!', 'success');
            } else if (state === 'connecting') {
                this.log('🔄 Pusher is connecting...', 'warning');

                // Wait for connection
                await new Promise((resolve) => {
                    const checkConnection = () => {
                        if (pusher.connection.state === 'connected') {
                            this.log('✅ Pusher connected!', 'success');
                            resolve();
                        } else {
                            setTimeout(checkConnection, 1000);
                        }
                    };
                    checkConnection();
                });
            } else {
                this.log(`❌ Pusher connection failed: ${state}`, 'error');
            }

            // Test private channel
            const userMeta = document.querySelector('meta[name="user-id"]');
            if (userMeta) {
                const userId = userMeta.content;
                const channelName = `App.User.${userId}`;

                this.log(`🔒 Testing private channel: ${channelName}`, 'info');

                const channel = window.Echo.private(channelName);
                channel.subscribed(() => {
                    this.log('✅ Private channel subscription successful!', 'success');
                });

                channel.error((error) => {
                    this.log(`❌ Private channel error: ${JSON.stringify(error)}`, 'error');
                });
            }

        } catch (error) {
            this.log(`❌ Echo test failed: ${error.message}`, 'error');
        }
    }

    async testAPI() {
        try {
            // Test notifications API
            const response = await fetch('/api/notifications');
            if (response.ok) {
                const data = await response.json();
                this.log('✅ Notifications API working', 'success');
                this.log(`📊 Current unread count: ${data.unread_count}`, 'info');
            } else {
                this.log(`❌ Notifications API error: ${response.status}`, 'error');
            }
        } catch (error) {
            this.log(`❌ API test failed: ${error.message}`, 'error');
        }
    }

    testSound() {
        if (window.notificationSound) {
            window.notificationSound.play();
            this.log('🔊 Sound test triggered', 'success');
        } else {
            this.log('❌ Sound system not available', 'error');
        }
    }

    async sendNotification() {
        try {
            this.log('📤 Sending test notification via API...', 'info');

            const response = await fetch('/notifications/test');
            const result = await response.json();

            if (result.success) {
                this.log('✅ Test notification sent successfully!', 'success');
                this.log('⏳ Waiting for real-time notification...', 'info');

                // Wait for real-time notification
                const waitForNotification = () => {
                    let timeout = setTimeout(() => {
                        this.log('⚠️ No real-time notification received within 10 seconds', 'warning');
                    }, 10000);

                    const handler = (event) => {
                        clearTimeout(timeout);
                        this.log('🎉 REAL-TIME NOTIFICATION RECEIVED!', 'success');
                        this.log(`📋 Notification: ${event.detail.title}`, 'success');
                        document.removeEventListener('notificationReceived', handler);
                    };

                    document.addEventListener('notificationReceived', handler);
                };

                waitForNotification();

            } else {
                this.log(`❌ Failed to send notification: ${JSON.stringify(result)}`, 'error');
            }
        } catch (error) {
            this.log(`❌ Notification send failed: ${error.message}`, 'error');
        }
    }

    showResults() {
        const resultsWindow = window.open('', '_blank', 'width=600,height=400,scrollbars=yes');
        const resultsHTML = `
            <html>
                <head><title>Notification Test Results</title></head>
                <body style="font-family: monospace; padding: 20px;">
                    <h2>🔔 Notification System Test Results</h2>
                    <p>Test run: ${new Date().toLocaleString()}</p>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        ${this.results.map(result =>
                            `<div style="margin: 5px 0;">[${result.timestamp}] <span style="color: ${
                                result.type === 'success' ? 'green' :
                                result.type === 'error' ? 'red' :
                                result.type === 'warning' ? 'orange' : 'blue'
                            }">${result.message}</span></div>`
                        ).join('')}
                    </div>
                </body>
            </html>
        `;
        resultsWindow.document.write(resultsHTML);
    }

    show() {
        if (this.testInterface) this.testInterface.style.display = 'block';
    }

    hide() {
        if (this.testInterface) this.testInterface.style.display = 'none';
    }

    toggle() {
        if (!this.testInterface) return;
        const isVisible = this.testInterface.style.display !== 'none';
        this.testInterface.style.display = isVisible ? 'none' : 'block';
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationTester = new NotificationTester();
    });
} else {
    window.notificationTester = new NotificationTester();
}
