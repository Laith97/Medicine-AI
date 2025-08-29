<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Debug Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('js/connection-test.js') }}"></script>
    <!-- Add meta tags for user info -->
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()?->role }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .debug-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .debug-item {
            margin: 10px 0;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .debug-item.success {
            border-left-color: #28a745;
        }
        .debug-item.error {
            border-left-color: #dc3545;
        }
        .debug-item.warning {
            border-left-color: #ffc107;
        }
        .debug-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .debug-button:hover {
            background: #0056b3;
        }
        .debug-button.success {
            background: #28a745;
        }
        .debug-button.success:hover {
            background: #218838;
        }
        .debug-button.danger {
            background: #dc3545;
        }
        .debug-button.danger:hover {
            background: #c82333;
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
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            background: #e9ecef;
        }
        .test-result.passed {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .test-result.failed {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>🔔 Notification System Debug Test</h1>

        <!-- System Status Section -->
        <div class="debug-section">
            <h3>System Status</h3>
            <div id="system-status" class="debug-item">
                <p>Loading system status...</p>
            </div>
            <button class="debug-button" onclick="checkSystemStatus()">Refresh Status</button>
        </div>

        <!-- Connection Tests Section -->
        <div class="debug-section">
            <h3>Connection Tests</h3>
            <div id="connection-tests">
                <button class="debug-button" onclick="runConnectionTests()">Run All Connection Tests</button>
                <button class="debug-button" onclick="testPusherConnection()">Test Pusher</button>
                <button class="debug-button" onclick="testEchoConnection()">Test Echo</button>
                <button class="debug-button" onclick="testUserChannel()">Test User Channel</button>
                <div id="connection-results"></div>
            </div>
        </div>

        <!-- Notification Tests Section -->
        <div class="debug-section">
            <h3>Notification Tests</h3>
            <div id="notification-tests">
                <button class="debug-button" onclick="sendTestNotification()">Send Test Notification</button>
                <button class="debug-button" onclick="testSoundPlayback()">Test Sound</button>
                <button class="debug-button" onclick="testToastDisplay()">Test Toast</button>
                <button class="debug-button" onclick="testNotificationSystem()">Test Notification System</button>
                <div id="notification-results"></div>
            </div>
        </div>

        <!-- Environment Info Section -->
        <div class="debug-section">
            <h3>Environment Information</h3>
            <div id="environment-info">
                <div class="debug-item">Checking environment...</div>
            </div>
            <button class="debug-button" onclick="refreshEnvironmentInfo()">Refresh Info</button>
        </div>

        <!-- Console Log Section -->
        <div class="debug-section">
            <h3>Console Log</h3>
            <button class="debug-button" onclick="clearConsoleLog()">Clear Log</button>
            <button class="debug-button" onclick="toggleConsoleLog()">Toggle Log</button>
            <button class="debug-button success" onclick="downloadLog()">Download Log</button>
            <div id="console-log" class="log-output" style="display: none;"></div>
        </div>

        <!-- Quick Actions Section -->
        <div class="debug-section">
            <h3>Quick Actions</h3>
            <button class="debug-button" onclick="runAllTests()">Run All Tests</button>
            <button class="debug-button danger" onclick="resetNotificationSystem()">Reset Notification System</button>
            <button class="debug-button" onclick="openDiagnostics()">Open Full Diagnostics</button>
            <div id="quick-results"></div>
        </div>
    </div>

    <script>
        // Make functions globally available immediately
        window.checkSystemStatus = function() {
            const statusDiv = document.getElementById('system-status');
            statusDiv.innerHTML = '<p>Checking system status...</p>';

            setTimeout(() => {
                let html = '';

                // Check Echo
                if (typeof window.Echo !== 'undefined') {
                    html += '<div class="debug-item success">✅ Echo: Available</div>';

                    if (window.Echo.connector) {
                        html += '<div class="debug-item success">✅ Echo Connector: Available</div>';

                        if (window.Echo.connector.pusher) {
                            html += '<div class="debug-item success">✅ Pusher: Available</div>';
                            const state = window.Echo.connector.pusher.connection.state;
                            html += `<div class="debug-item">Pusher State: ${state}</div>`;

                            if (state === 'connected') {
                                html += '<div class="debug-item success">✅ Pusher Connected</div>';
                            } else {
                                html += '<div class="debug-item warning">⚠️ Pusher Not Connected</div>';
                            }
                        } else {
                            html += '<div class="debug-item error">❌ Pusher: Not Available</div>';
                        }
                    } else {
                        html += '<div class="debug-item error">❌ Echo Connector: Not Available</div>';
                    }
                } else {
                    html += '<div class="debug-item error">❌ Echo: Not Available</div>';
                }

                // Check notification system
                if (typeof window.enhancedNotificationSystem !== 'undefined') {
                    html += '<div class="debug-item success">✅ Enhanced Notification System: Available</div>';

                    if (window.enhancedNotificationSystem.isInitialized) {
                        html += '<div class="debug-item success">✅ Notification System: Initialized</div>';
                    } else {
                        html += '<div class="debug-item warning">⚠️ Notification System: Not Initialized</div>';
                    }
                } else {
                    html += '<div class="debug-item error">❌ Enhanced Notification System: Not Available</div>';
                }

                // Check user info
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
                const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');

                if (userId) {
                    html += `<div class="debug-item">User ID: ${userId}</div>`;
                } else {
                    html += '<div class="debug-item error">❌ User ID: Not Found</div>';
                }

                if (userRole) {
                    html += `<div class="debug-item">User Role: ${userRole}</div>`;
                } else {
                    html += '<div class="debug-item warning">⚠️ User Role: Not Found</div>';
                }

                statusDiv.innerHTML = html;
            }, 500);
        };

        window.runConnectionTests = function() {
            const resultsDiv = document.getElementById('connection-results');
            resultsDiv.innerHTML = '<div class="debug-item">Running connection tests...</div>';

            // Use the connection test script
            if (typeof window.runNotificationTests === 'function') {
                window.runNotificationTests().then(results => {
                    let html = '';

                    Object.entries(results).forEach(([key, value]) => {
                        const status = value ? 'success' : 'error';
                        const text = key.replace(/_/g, ' ').toUpperCase();
                        html += `<div class="debug-item ${status}">${text}: ${value ? '✅' : '❌'}</div>`;
                    });

                    resultsDiv.innerHTML = html;
                });
            } else {
                resultsDiv.innerHTML = '<div class="debug-item error">❌ Connection test script not loaded</div>';
            }
        };

        window.testPusherConnection = function() {
            const resultsDiv = document.getElementById('connection-results');
            window.addTestResult(resultsDiv, 'Testing Pusher connection...', 'info');

            if (typeof window.Echo === 'undefined' || !window.Echo.connector || !window.Echo.connector.pusher) {
                window.addTestResult(resultsDiv, '❌ Pusher not available', 'error');
                return;
            }

            const pusher = window.Echo.connector.pusher;
            const state = pusher.connection.state;

            window.addTestResult(resultsDiv, `Pusher state: ${state}`, 'info');

            if (state === 'connected') {
                window.addTestResult(resultsDiv, '✅ Pusher connected', 'success');
            } else {
                window.addTestResult(resultsDiv, '⚠️ Pusher not connected', 'warning');
                pusher.connection.connect();
                setTimeout(() => {
                    const newState = pusher.connection.state;
                    window.addTestResult(resultsDiv, `Reconnection result: ${newState}`, newState === 'connected' ? 'success' : 'error');
                }, 2000);
            }
        };

        window.testEchoConnection = function() {
            const resultsDiv = document.getElementById('connection-results');
            window.addTestResult(resultsDiv, 'Testing Echo connection...', 'info');

            if (typeof window.Echo === 'undefined') {
                window.addTestResult(resultsDiv, '❌ Echo not available', 'error');
                return;
            }

            window.addTestResult(resultsDiv, '✅ Echo available', 'success');

            try {
                const testChannel = window.Echo.channel('test-debug');
                testChannel.subscribed(() => {
                    window.addTestResult(resultsDiv, '✅ Test channel subscription successful', 'success');
                    testChannel.unsubscribe();
                });

                testChannel.error((error) => {
                    window.addTestResult(resultsDiv, `❌ Test channel error: ${error.message}`, 'error');
                });
            } catch (error) {
                window.addTestResult(resultsDiv, `❌ Echo test failed: ${error.message}`, 'error');
            }
        };

        window.testUserChannel = function() {
            const resultsDiv = document.getElementById('connection-results');
            window.addTestResult(resultsDiv, 'Testing user channel subscription...', 'info');

            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (!userId) {
                window.addTestResult(resultsDiv, '❌ User ID not found', 'error');
                return;
            }

            window.addTestResult(resultsDiv, `User ID: ${userId}`, 'info');

            try {
                const userChannel = window.Echo.private(`App.User.${userId}`);

                userChannel.subscribed(() => {
                    window.addTestResult(resultsDiv, '✅ User channel subscription successful', 'success');

                    // Test notification listener
                    userChannel.notification((notification) => {
                        window.addTestResult(resultsDiv, '✅ Notification listener working', 'success');
                        console.log('Test notification received:', notification);
                    });
                });

                userChannel.error((error) => {
                    window.addTestResult(resultsDiv, `❌ User channel error: ${error.message}`, 'error');
                });

            } catch (error) {
                window.addTestResult(resultsDiv, `❌ User channel test failed: ${error.message}`, 'error');
            }
        };

        window.sendTestNotification = function() {
            const resultsDiv = document.getElementById('notification-results');
            window.addTestResult(resultsDiv, 'Sending test notification...', 'info');

            fetch('/notifications/test')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.addTestResult(resultsDiv, '✅ Test notification sent successfully', 'success');
                        window.addTestResult(resultsDiv, `Response: ${JSON.stringify(result)}`, 'info');
                    } else {
                        window.addTestResult(resultsDiv, `❌ Failed to send test notification: ${result.message}`, 'error');
                    }
                })
                .catch(error => {
                    window.addTestResult(resultsDiv, `❌ Error sending test notification: ${error.message}`, 'error');
                });
        };

        window.testSoundPlayback = function() {
            const resultsDiv = document.getElementById('notification-results');
            window.addTestResult(resultsDiv, 'Testing sound playback...', 'info');

            try {
                if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                    window.addTestResult(resultsDiv, '✅ Preloaded sound available', 'success');
                    window.notificationSound.play();
                    window.addTestResult(resultsDiv, '✅ Sound playback initiated', 'success');
                } else {
                    window.addTestResult(resultsDiv, '⚠️ Preloaded sound not available, testing fallback...', 'warning');

                    const audio = new Audio('/sounds/notification.mp3');
                    audio.volume = 0.3;

                    audio.oncanplaythrough = () => {
                        window.addTestResult(resultsDiv, '✅ Fallback sound loaded', 'success');
                        audio.play();
                        window.addTestResult(resultsDiv, '✅ Fallback sound playback initiated', 'success');
                    };

                    audio.onerror = () => {
                        window.addTestResult(resultsDiv, '❌ Failed to load fallback sound', 'error');
                    };

                    audio.load();
                }
            } catch (error) {
                window.addTestResult(resultsDiv, `❌ Sound test failed: ${error.message}`, 'error');
            }
        };

        window.testToastDisplay = function() {
            const resultsDiv = document.getElementById('notification-results');
            window.addTestResult(resultsDiv, 'Testing toast display...', 'info');

            try {
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

                setTimeout(() => {
                    toast.style.transform = 'translateX(0)';
                    window.addTestResult(resultsDiv, '✅ Toast displayed successfully', 'success');
                }, 100);

                setTimeout(() => {
                    toast.style.transform = 'translateX(400px)';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 3000);

            } catch (error) {
                window.addTestResult(resultsDiv, `❌ Toast test failed: ${error.message}`, 'error');
            }
        };

        window.testNotificationSystem = function() {
            const resultsDiv = document.getElementById('notification-results');
            window.addTestResult(resultsDiv, 'Testing notification system...', 'info');

            if (typeof window.enhancedNotificationSystem !== 'undefined') {
                if (window.enhancedNotificationSystem.isInitialized) {
                    window.addTestResult(resultsDiv, '✅ Notification system initialized', 'success');

                    // Test with a mock notification
                    const mockNotification = {
                        id: 'test-' + Date.now(),
                        type: 'test',
                        title: 'Test Notification',
                        message: 'This is a test notification',
                        data: { test: true }
                    };

                    window.enhancedNotificationSystem.handleNewNotification(mockNotification, 'test');
                    window.addTestResult(resultsDiv, '✅ Mock notification processed', 'success');
                } else {
                    window.addTestResult(resultsDiv, '⚠️ Notification system not initialized', 'warning');
                }
            } else {
                window.addTestResult(resultsDiv, '❌ Notification system not available', 'error');
            }
        };

        window.runAllTests = function() {
            const resultsDiv = document.getElementById('quick-results');
            resultsDiv.innerHTML = '<div class="debug-item">Running all tests...</div>';

            window.checkSystemStatus();
            window.runConnectionTests();
            window.sendTestNotification();
            window.testSoundPlayback();
            window.testToastDisplay();
            window.testNotificationSystem();

            setTimeout(() => {
                window.refreshEnvironmentInfo();
            }, 1000);
        };

        window.resetNotificationSystem = function() {
            if (confirm('Are you sure you want to reset the notification system?')) {
                if (typeof window.enhancedNotificationSystem !== 'undefined') {
                    window.enhancedNotificationSystem.isInitialized = false;
                    delete window.enhancedNotificationSystem;
                }

                location.reload();
            }
        };

        window.openDiagnostics = function() {
            window.open('/notification-diagnostics', '_blank');
        };

        window.addTestResult = function(container, message, type = 'info') {
            const resultDiv = document.createElement('div');
            resultDiv.className = `test-result ${type}`;
            resultDiv.textContent = message;
            container.appendChild(resultDiv);
        };

        window.clearConsoleLog = function() {
            consoleLog.length = 0;
            logOutput.innerHTML = '';
            addToLog('Console log cleared', 'log');
        };

        window.toggleConsoleLog = function() {
            logVisible = !logVisible;
            logOutput.style.display = logVisible ? 'block' : 'none';
            if (logVisible) {
                refreshConsoleLog();
                addToLog('Console log enabled', 'log');
            }
        };

        window.downloadLog = function() {
            const logText = consoleLog.map(entry => `[${entry.timestamp}] ${entry.message}`).join('\n');
            const blob = new Blob([logText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `notification-debug-${new Date().toISOString()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
            addToLog('Log downloaded', 'log');
        };

        window.refreshEnvironmentInfo = function() {
            const envDiv = document.getElementById('environment-info');
            envDiv.innerHTML = '<div class="debug-item">Loading environment info...</div>';

            fetch('/api/notification-diagnostics')
                .then(response => response.json())
                .then(data => {
                    let html = '';

                    html += `<div class="debug-item">Broadcast Driver: ${data.broadcast_driver}</div>`;
                    html += `<div class="debug-item">Queue Driver: ${data.queue_driver}</div>`;
                    html += `<div class="debug-item">Pusher App Key: ${data.pusher_app_key ? 'Available' : 'Not Available'}</div>`;
                    html += `<div class="debug-item">User ID: ${data.user_id || 'Not Authenticated'}</div>`;
                    html += `<div class="debug-item">User Role: ${data.user_role || 'Not Available'}</div>`;
                    html += `<div class="debug-item">Sound Enabled: ${data.sound_enabled ? 'Yes' : 'No'}</div>`;
                    html += `<div class="debug-item">Toast Enabled: ${data.toast_enabled ? 'Yes' : 'No'}</div>`;

                    envDiv.innerHTML = html;
                })
                .catch(error => {
                    envDiv.innerHTML = `<div class="debug-item error">❌ Failed to load environment info: ${error.message}</div>`;
                });
        };

        // Console log capture
        const consoleLog = [];
        const logOutput = document.getElementById('console-log');
        let logVisible = false;

        function addToLog(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = {
                timestamp,
                message,
                type
            };
            consoleLog.push(logEntry);

            if (logVisible) {
                const logDiv = document.createElement('div');
                logDiv.style.marginBottom = '5px';
                logDiv.style.color = type === 'error' ? '#ff6b6b' : type === 'warn' ? '#ffd93d' : '#6bcf7f';
                logDiv.textContent = `[${timestamp}] ${message}`;
                logOutput.appendChild(logDiv);
                logOutput.scrollTop = logOutput.scrollHeight;
            }
        }

        // Override console methods
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;

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

        function toggleConsoleLog() {
            logVisible = !logVisible;
            logOutput.style.display = logVisible ? 'block' : 'none';
            if (logVisible) {
                refreshConsoleLog();
                addToLog('Console log enabled', 'log');
            }
        }

        function clearConsoleLog() {
            consoleLog.length = 0;
            logOutput.innerHTML = '';
            addToLog('Console log cleared', 'log');
        }

        function refreshConsoleLog() {
            logOutput.innerHTML = '';
            consoleLog.forEach(entry => {
                const logDiv = document.createElement('div');
                logDiv.style.marginBottom = '5px';
                logDiv.style.color = entry.type === 'error' ? '#ff6b6b' : entry.type === 'warn' ? '#ffd93d' : '#6bcf7f';
                logDiv.textContent = `[${entry.timestamp}] ${entry.message}`;
                logOutput.appendChild(logDiv);
            });
            logOutput.scrollTop = logOutput.scrollHeight;
        }

        function downloadLog() {
            const logText = consoleLog.map(entry => `[${entry.timestamp}] ${entry.message}`).join('\n');
            const blob = new Blob([logText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `notification-debug-${new Date().toISOString()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
            addToLog('Log downloaded', 'log');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            addToLog('Notification debug page loaded', 'log');
            window.checkSystemStatus();
            window.refreshEnvironmentInfo();
        });
    </script>
</body>
</html>
