<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Diagnostics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .diagnostic-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .diagnostic-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .diagnostic-item.success {
            border-left-color: #28a745;
        }
        .diagnostic-item.error {
            border-left-color: #dc3545;
        }
        .diagnostic-item.warning {
            border-left-color: #ffc107;
        }
        .test-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #0056b3;
        }
        .log-output {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔔 Notification System Diagnostics</h1>

        <div class="diagnostic-panel">
            <h3>System Status</h3>
            <div id="system-status">
                <div class="diagnostic-item">Checking system...</div>
            </div>
        </div>

        <div class="diagnostic-panel">
            <h3>Connection Tests</h3>
            <button class="test-button" onclick="testPusherConnection()">Test Pusher Connection</button>
            <button class="test-button" onclick="testEchoConnection()">Test Echo Connection</button>
            <button class="test-button" onclick="testUserChannel()">Test User Channel</button>
            <div id="connection-results"></div>
        </div>

        <div class="diagnostic-panel">
            <h3>Notification Tests</h3>
            <button class="test-button" onclick="sendTestNotification()">Send Test Notification</button>
            <button class="test-button" onclick="testSoundPlayback()">Test Sound Playback</button>
            <button class="test-button" onclick="testToastDisplay()">Test Toast Display</button>
            <div id="notification-results"></div>
        </div>

        <div class="diagnostic-panel">
            <h3>Live Console Log</h3>
            <button class="test-button" onclick="clearLog()">Clear Log</button>
            <button class="test-button" onclick="toggleLog()">Toggle Log</button>
            <div id="console-log" class="log-output" style="display: none;"></div>
        </div>

        <div class="diagnostic-panel">
            <h3>Environment Info</h3>
            <div id="environment-info"></div>
        </div>
    </div>

    <script>
        // Override console.log to capture output
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;

        const logOutput = document.getElementById('console-log');
        let logVisible = false;

        function addToLog(message, type = 'log') {
            if (!logVisible) return;

            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.style.marginBottom = '5px';

            if (type === 'error') {
                logEntry.style.color = '#ff6b6b';
            } else if (type === 'warn') {
                logEntry.style.color = '#ffd93d';
            } else {
                logEntry.style.color = '#6bcf7f';
            }

            logEntry.textContent = `[${timestamp}] ${message}`;
            logOutput.appendChild(logEntry);
            logOutput.scrollTop = logOutput.scrollHeight;
        }

        console.log = function(...args) {
            originalLog.apply(console, args);
            addToLog(args.join(' '), 'log');
        };

        console.error = function(...args) {
            originalError.apply(console, args);
            addToLog(args.join(' '), 'error');
        };

        console.warn = function(...args) {
            originalWarn.apply(console, args);
            addToLog(args.join(' '), 'warn');
        };

        function toggleLog() {
            logVisible = !logVisible;
            logOutput.style.display = logVisible ? 'block' : 'none';
            if (logVisible) {
                addToLog('Console log enabled', 'log');
            }
        }

        function clearLog() {
            logOutput.innerHTML = '';
            addToLog('Log cleared', 'log');
        }

        function updateStatus(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            element.innerHTML = `<div class="diagnostic-item ${type}">${message}</div>`;
        }

        function addResult(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            const resultDiv = document.createElement('div');
            resultDiv.className = `diagnostic-item ${type}`;
            resultDiv.textContent = message;
            element.appendChild(resultDiv);
        }

        // Initialize diagnostics
        document.addEventListener('DOMContentLoaded', function() {
            runDiagnostics();
        });

        function runDiagnostics() {
            updateStatus('system-status', 'Running diagnostics...');

            setTimeout(() => {
                checkEnvironment();
                checkEcho();
                checkNotificationSystem();
            }, 1000);
        }

        function checkEnvironment() {
            const envInfo = document.getElementById('environment-info');
            let html = '';

            html += `<div class="diagnostic-item">APP_ENV: @config('app.env')</div>`;
            html += `<div class="diagnostic-item">BROADCAST_CONNECTION: @config('broadcasting.default')</div>`;
            html += `<div class="diagnostic-item">QUEUE_CONNECTION: @config('queue.default')</div>`;
            html += `<div class="diagnostic-item">PUSHER_APP_KEY: @config('broadcasting.connections.pusher.key')</div>`;

            if (typeof window.Echo !== 'undefined') {
                html += `<div class="diagnostic-item success">Echo: Available</div>`;
                if (window.Echo.connector) {
                    html += `<div class="diagnostic-item success">Echo Connector: Available</div>`;
                    if (window.Echo.connector.pusher) {
                        html += `<div class="diagnostic-item success">Pusher: Available</div>`;
                        html += `<div class="diagnostic-item">Pusher State: ${window.Echo.connector.pusher.connection.state}</div>`;
                    } else {
                        html += `<div class="diagnostic-item error">Pusher: Not Available</div>`;
                    }
                } else {
                    html += `<div class="diagnostic-item error">Echo Connector: Not Available</div>`;
                }
            } else {
                html += `<div class="diagnostic-item error">Echo: Not Available</div>`;
            }

            envInfo.innerHTML = html;
        }

        function checkEcho() {
            if (typeof window.Echo === 'undefined') {
                updateStatus('system-status', '❌ Echo not loaded', 'error');
                return;
            }

            if (!window.Echo.connector) {
                updateStatus('system-status', '❌ Echo connector not available', 'error');
                return;
            }

            if (!window.Echo.connector.pusher) {
                updateStatus('system-status', '❌ Pusher not available', 'error');
                return;
            }

            const pusher = window.Echo.connector.pusher;
            const state = pusher.connection.state;

            if (state === 'connected') {
                updateStatus('system-status', '✅ Pusher connected', 'success');
            } else {
                updateStatus('system-status', `⚠️ Pusher state: ${state}`, 'warning');
            }
        }

        function checkNotificationSystem() {
            if (typeof window.enhancedNotificationSystem !== 'undefined') {
                updateStatus('system-status', '✅ Enhanced notification system loaded', 'success');

                if (window.enhancedNotificationSystem.isInitialized) {
                    updateStatus('system-status', '✅ Notification system initialized', 'success');
                } else {
                    updateStatus('system-status', '⚠️ Notification system not initialized', 'warning');
                }
            } else {
                updateStatus('system-status', '❌ Enhanced notification system not loaded', 'error');
            }
        }

        async function testPusherConnection() {
            const results = document.getElementById('connection-results');
            results.innerHTML = '';

            addResult('connection-results', 'Testing Pusher connection...', 'info');

            if (typeof window.Echo === 'undefined') {
                addResult('connection-results', '❌ Echo not available', 'error');
                return;
            }

            if (!window.Echo.connector || !window.Echo.connector.pusher) {
                addResult('connection-results', '❌ Pusher not available', 'error');
                return;
            }

            const pusher = window.Echo.connector.pusher;
            const state = pusher.connection.state;

            addResult('connection-results', `Current Pusher state: ${state}`, 'info');

            if (state === 'connected') {
                addResult('connection-results', '✅ Pusher is connected', 'success');
            } else {
                addResult('connection-results', '⚠️ Pusher is not connected', 'warning');

                // Try to reconnect
                try {
                    addResult('connection-results', 'Attempting to reconnect...', 'info');
                    pusher.connection.connect();
                    setTimeout(() => {
                        const newState = pusher.connection.state;
                        addResult('connection-results', `Reconnection attempt result: ${newState}`, newState === 'connected' ? 'success' : 'error');
                    }, 2000);
                } catch (error) {
                    addResult('connection-results', `❌ Reconnection failed: ${error.message}`, 'error');
                }
            }
        }

        async function testEchoConnection() {
            const results = document.getElementById('connection-results');
            addResult('connection-results', 'Testing Echo connection...', 'info');

            if (typeof window.Echo === 'undefined') {
                addResult('connection-results', '❌ Echo not available', 'error');
                return;
            }

            addResult('connection-results', '✅ Echo is available', 'success');

            // Test creating a channel
            try {
                const testChannel = window.Echo.channel('test-diagnostic');
                testChannel.subscribed(() => {
                    addResult('connection-results', '✅ Test channel subscription successful', 'success');
                    testChannel.unsubscribe();
                });

                testChannel.error((error) => {
                    addResult('connection-results', `❌ Test channel error: ${error.message}`, 'error');
                });
            } catch (error) {
                addResult('connection-results', `❌ Echo connection test failed: ${error.message}`, 'error');
            }
        }

        async function testUserChannel() {
            const results = document.getElementById('connection-results');
            addResult('connection-results', 'Testing user channel subscription...', 'info');

            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!userId) {
                addResult('connection-results', '❌ User ID not found in meta tags', 'error');
                return;
            }

            addResult('connection-results', `User ID: ${userId}`, 'info');

            try {
                const userChannel = window.Echo.private(`App.User.${userId}`);

                userChannel.subscribed(() => {
                    addResult('connection-results', '✅ User channel subscription successful', 'success');

                    // Test listening for notifications
                    userChannel.notification((notification) => {
                        addResult('connection-results', '✅ Notification listener working', 'success');
                        // console.log('Test notification received:', notification);
                    });

                    userChannel.error((error) => {
                        addResult('connection-results', `❌ User channel error: ${error.message}`, 'error');
                    });
                });

                userChannel.error((error) => {
                    addResult('connection-results', `❌ Failed to subscribe to user channel: ${error.message}`, 'error');
                });

            } catch (error) {
                addResult('connection-results', `❌ User channel test failed: ${error.message}`, 'error');
            }
        }

        async function sendTestNotification() {
            const results = document.getElementById('notification-results');
            results.innerHTML = '';

            addResult('notification-results', 'Sending test notification...', 'info');

            try {
                const response = await fetch('/notifications/test');
                const result = await response.json();

                if (response.ok) {
                    addResult('notification-results', '✅ Test notification sent successfully', 'success');
                    addResult('notification-results', `Response: ${JSON.stringify(result)}`, 'info');
                } else {
                    addResult('notification-results', `❌ Failed to send test notification: ${result.message}`, 'error');
                }
            } catch (error) {
                addResult('notification-results', `❌ Error sending test notification: ${error.message}`, 'error');
            }
        }

        function testSoundPlayback() {
            const results = document.getElementById('notification-results');
            addResult('notification-results', 'Testing sound playback...', 'info');

            try {
                // Test with preloaded sound
                if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                    addResult('notification-results', '✅ Preloaded sound available', 'success');
                    window.notificationSound.play();
                    addResult('notification-results', '✅ Sound playback initiated', 'success');
                } else {
                    addResult('notification-results', '⚠️ Preloaded sound not available, testing fallback...', 'warning');

                    // Test with fallback
                    const audio = new Audio('/sounds/notification.mp3');
                    audio.volume = 0.3;

                    audio.oncanplaythrough = () => {
                        addResult('notification-results', '✅ Fallback sound loaded', 'success');
                        audio.play();
                        addResult('notification-results', '✅ Fallback sound playback initiated', 'success');
                    };

                    audio.onerror = () => {
                        addResult('notification-results', '❌ Failed to load fallback sound', 'error');
                    };

                    audio.load();
                }
            } catch (error) {
                addResult('notification-results', `❌ Sound test failed: ${error.message}`, 'error');
            }
        }

        function testToastDisplay() {
            const results = document.getElementById('notification-results');
            addResult('notification-results', 'Testing toast display...', 'info');

            try {
                // Create a test toast
                const toast = document.createElement('div');
                toast.className = 'enhanced-notification-toast';
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border: 1px solid #e2e8f0;
                    border-left: 4px solid #28a745;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                    padding: 16px;
                    max-width: 350px;
                    z-index: 10000;
                    transform: translateX(400px);
                    transition: transform 0.3s ease-in-out;
                `;

                toast.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 32px; height: 32px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1a202c;">Test Toast</h4>
                            <p style="margin: 0; font-size: 13px; color: #4a5568; line-height: 1.4;">This is a test toast notification</p>
                        </div>
                    </div>
                `;

                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.style.transform = 'translateX(0)';
                    addResult('notification-results', '✅ Toast displayed successfully', 'success');
                }, 100);

                // Auto remove after 3 seconds
                setTimeout(() => {
                    toast.style.transform = 'translateX(400px)';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 3000);

            } catch (error) {
                addResult('notification-results', `❌ Toast test failed: ${error.message}`, 'error');
            }
        }
    </script>
</body>
</html>
