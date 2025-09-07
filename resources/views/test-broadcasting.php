<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcasting Test</title>
    @vite(['resources/js/bootstrap.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        button { margin: 5px; padding: 8px 15px; cursor: pointer; }
        .log { max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>🔊 Broadcasting Test Page</h1>

    <div class="test-section">
        <h2>🔑 Authentication Status</h2>
        <div id="auth-status">Checking...</div>
        <button onclick="checkAuth()">Check Authentication</button>
    </div>

    <div class="test-section">
        <h2>📡 Echo Configuration</h2>
        <div id="echo-status">Checking...</div>
        <button onclick="checkEcho()">Check Echo</button>
    </div>

    <div class="test-section">
        <h2>🔐 Broadcasting Auth Test</h2>
        <div id="auth-test-status">Ready to test</div>
        <button onclick="testBroadcastingAuth()">Test Broadcasting Auth</button>
        <button onclick="testChannelSubscription()">Test Channel Subscription</button>
    </div>

    <div class="test-section">
        <h2>📝 Console Logs</h2>
        <div id="console-logs" class="log"></div>
        <button onclick="clearLogs()">Clear Logs</button>
    </div>

    <script>
        // Global log function
        function log(message, type = 'info') {
            const logs = document.getElementById('console-logs');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = type;
            logEntry.innerHTML = `<span class="timestamp">[${timestamp}]</span> ${message}`;
            logs.appendChild(logEntry);
            logs.scrollTop = logs.scrollHeight;
            console.log(`[${type.toUpperCase()}]`, message);
        }

        // Check authentication status
        async function checkAuth() {
            try {
                const response = await fetch('/debug-broadcasting-auth');
                const data = await response.json();

                const authDiv = document.getElementById('auth-status');
                if (data.authenticated) {
                    authDiv.innerHTML = `
                        <div class="success">✅ Authenticated</div>
                        <div>User ID: ${data.user_id}</div>
                        <div>User Name: ${data.user_name}</div>
                        <div>User Role: ${data.user_role}</div>
                        <div>Session ID: ${data.session_id}</div>
                    `;
                } else {
                    authDiv.innerHTML = '<div class="error">❌ Not authenticated</div>';
                }
                log('Authentication check completed', 'info');
            } catch (error) {
                document.getElementById('auth-status').innerHTML = '<div class="error">❌ Error checking auth</div>';
                log('Authentication check failed: ' + error.message, 'error');
            }
        }

        // Check Echo configuration
        function checkEcho() {
            const echoDiv = document.getElementById('echo-status');

            if (typeof window.Echo !== 'undefined') {
                echoDiv.innerHTML = `
                    <div class="success">✅ Echo is initialized</div>
                    <div>Key: ${window.Echo.options.key}</div>
                    <div>Cluster: ${window.Echo.options.cluster}</div>
                    <div>Auth Endpoint: ${window.Echo.options.authEndpoint}</div>
                `;
                log('Echo configuration check passed', 'success');
            } else {
                echoDiv.innerHTML = '<div class="error">❌ Echo is not initialized</div>';
                log('Echo configuration check failed', 'error');
            }
        }

        // Test broadcasting auth endpoint
        async function testBroadcastingAuth() {
            const statusDiv = document.getElementById('auth-test-status');
            statusDiv.innerHTML = '<div class="info">🔄 Testing broadcasting auth...</div>';

            try {
                const response = await fetch('/test-broadcasting-auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        channel_name: 'private-App.User.' + (window.Laravel?.user?.id || '1'),
                        socket_id: 'test-socket-id'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="success">✅ Broadcasting auth successful</div>
                        <div>Authorized: ${data.authorized}</div>
                        <div>Channel: ${data.channel_name}</div>
                        <div>User: ${data.user_name}</div>
                    `;
                    log('Broadcasting auth test successful', 'success');
                } else {
                    statusDiv.innerHTML = '<div class="error">❌ Broadcasting auth failed</div>';
                    log('Broadcasting auth test failed: ' + JSON.stringify(data), 'error');
                }
            } catch (error) {
                statusDiv.innerHTML = '<div class="error">❌ Error testing broadcasting auth</div>';
                log('Broadcasting auth test error: ' + error.message, 'error');
            }
        }

        // Test channel subscription
        function testChannelSubscription() {
            const statusDiv = document.getElementById('auth-test-status');
            statusDiv.innerHTML = '<div class="info">🔄 Testing channel subscription...</div>';

            if (typeof window.Echo === 'undefined') {
                statusDiv.innerHTML = '<div class="error">❌ Echo not available</div>';
                log('Channel subscription test failed: Echo not available', 'error');
                return;
            }

            const userId = window.Laravel?.user?.id || '1';
            const channelName = `private-App.User.${userId}`;

            try {
                const channel = window.Echo.private(channelName);

                channel.subscribed(() => {
                    statusDiv.innerHTML = `<div class="success">✅ Successfully subscribed to ${channelName}</div>`;
                    log(`Channel subscription successful: ${channelName}`, 'success');
                });

                channel.error((error) => {
                    statusDiv.innerHTML = `<div class="error">❌ Channel subscription error: ${error.message}</div>`;
                    log(`Channel subscription error: ${error.message}`, 'error');
                });

                // Listen for notifications
                channel.notification((notification) => {
                    log(`Notification received: ${notification.title}`, 'info');
                });

                // Set timeout to check subscription status
                setTimeout(() => {
                    if (!channel.subscribed) {
                        statusDiv.innerHTML = '<div class="error">❌ Channel subscription timeout</div>';
                        log('Channel subscription timeout', 'error');
                    }
                }, 5000);

            } catch (error) {
                statusDiv.innerHTML = `<div class="error">❌ Error subscribing to channel: ${error.message}</div>`;
                log('Channel subscription error: ' + error.message, 'error');
            }
        }

        // Clear logs
        function clearLogs() {
            document.getElementById('console-logs').innerHTML = '';
            log('Logs cleared', 'info');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            log('Broadcasting test page loaded', 'info');
            checkAuth();
            checkEcho();
        });

        // Listen for Echo events
        if (typeof window.Echo !== 'undefined') {
            window.Echo.connector.socket.on('connected', () => {
                log('Echo socket connected', 'success');
            });

            window.Echo.connector.socket.on('disconnected', () => {
                log('Echo socket disconnected', 'error');
            });
        }
    </script>
</body>
</html>
