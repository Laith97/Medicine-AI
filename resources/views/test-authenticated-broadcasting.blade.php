<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="user-role" content="{{ Auth::user()->role }}">
    @endauth
    <title>Authenticated Broadcasting Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .status-connected { background-color: #28a745; }
        .status-disconnected { background-color: #dc3545; }
        .status-connecting { background-color: #ffc107; }
        .log-entry { font-family: monospace; font-size: 12px; margin: 2px 0; padding: 4px; background: #f8f9fa; border-radius: 4px; }
        .notification-preview { background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🔐 Authenticated Broadcasting Test</h1>

                <!-- Authentication Status -->
                <div class="test-section">
                    <h4>🔒 Authentication Status</h4>
                    <div id="auth-status">
                        <div class="status-indicator status-connecting"></div>
                        <span>Checking authentication...</span>
                    </div>
                    <div id="auth-details" class="mt-3" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>User ID:</strong> <span id="user-id">-</span><br>
                                <strong>User Name:</strong> <span id="user-name">-</span><br>
                                <strong>User Email:</strong> <span id="user-email">-</span>
                            </div>
                            <div class="col-md-6">
                                <strong>User Role:</strong> <span id="user-role">-</span><br>
                                <strong>Channel:</strong> <span id="user-channel">-</span><br>
                                <strong>Session ID:</strong> <span id="session-id">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Broadcasting Configuration -->
                <div class="test-section">
                    <h4>📡 Broadcasting Configuration</h4>
                    <div id="broadcast-config">
                        <strong>Driver:</strong> <span id="broadcast-driver">-</span><br>
                        <strong>Pusher Key:</strong> <span id="pusher-key">-</span><br>
                        <strong>Pusher Cluster:</strong> <span id="pusher-cluster">-</span><br>
                        <strong>Auth Endpoint:</strong> <code>/broadcasting/auth</code>
                    </div>
                </div>

                <!-- Connection Status -->
                <div class="test-section">
                    <h4>🔌 Connection Status</h4>
                    <div id="connection-status">
                        <div class="status-indicator status-disconnected"></div>
                        <span>Not connected</span>
                    </div>
                    <div id="connection-details" class="mt-3" style="display: none;">
                        <strong>Echo Status:</strong> <span id="echo-status">-</span><br>
                        <strong>Pusher State:</strong> <span id="pusher-state">-</span><br>
                        <strong>Socket ID:</strong> <span id="socket-id">-</span>
                    </div>
                </div>

                <!-- Test Controls -->
                <div class="test-section">
                    <h4>🧪 Test Controls</h4>
                    <button id="test-authenticated-notification" class="btn btn-primary me-2" onclick="runAuthenticatedBroadcastingTest()">
                        🚀 Run Authenticated Broadcasting Test
                    </button>
                    <button id="clear-logs" class="btn btn-secondary" onclick="clearLogs()">
                        🗑️ Clear Logs
                    </button>
                    <div class="mt-3">
                        <strong>Test Results:</strong>
                        <div id="test-results" class="alert alert-info mt-2" style="display: none;">
                            <div id="test-results-content"></div>
                        </div>
                    </div>
                </div>

                <!-- Expected Notification Preview -->
                <div class="test-section">
                    <h4>🔔 Expected Notification</h4>
                    <div class="notification-preview">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <i class="bi bi-shield-check text-primary" style="font-size: 24px;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Authenticated Broadcasting Test</h6>
                                <p class="mb-2">This notification was sent through an authenticated user session to test private channel broadcasting</p>
                                <small class="text-muted">Just now</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="test-section">
                    <h4>📋 Activity Log</h4>
                    <div id="activity-log" style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                        <div class="log-entry">Test page loaded at {{ now()->format('H:i:s') }}</div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="test-section bg-light">
                    <h4>📖 Instructions</h4>
                    <ol>
                        <li>Check that authentication status shows as connected</li>
                        <li>Verify broadcasting configuration is properly loaded</li>
                        <li>Click "Run Authenticated Broadcasting Test" to send a test notification</li>
                        <li>Watch the activity log for real-time updates</li>
                        <li>Check browser console for detailed Echo/Pusher logs</li>
                        <li>Verify that the notification appears in the expected format</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Vite assets for Echo -->
    @vite(['resources/js/app.js'])

    <!-- Include notification system -->
    <script src="{{ asset('js/notifications-fixed.js') }}"></script>

    <script>
        let testResults = {};

        // Initialize the test page
        document.addEventListener('DOMContentLoaded', function() {
            logActivity('Initializing authenticated broadcasting test page...');
            checkAuthenticationStatus();
            checkBroadcastingConfig();
            initializeEchoMonitoring();
        });

        // Check authentication status
        function checkAuthenticationStatus() {
            logActivity('Checking authentication status...');

            fetch('/debug-broadcasting-auth', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const authStatus = document.getElementById('auth-status');
                const authDetails = document.getElementById('auth-details');

                if (data.authenticated) {
                    authStatus.innerHTML = '<div class="status-indicator status-connected"></div><span>Authenticated</span>';
                    authDetails.style.display = 'block';

                    document.getElementById('user-id').textContent = data.user_id || '-';
                    document.getElementById('user-name').textContent = data.user_name || '-';
                    document.getElementById('user-email').textContent = data.user_email || '-';
                    document.getElementById('user-role').textContent = data.user_role || '-';
                    document.getElementById('user-channel').textContent = data.expected_channel || '-';
                    document.getElementById('session-id').textContent = data.session_id || '-';

                    logActivity('✅ User authenticated: ' + data.user_name + ' (ID: ' + data.user_id + ')');
                } else {
                    authStatus.innerHTML = '<div class="status-indicator status-disconnected"></div><span>Not authenticated</span>';
                    logActivity('❌ User not authenticated');
                }
            })
            .catch(error => {
                logActivity('❌ Failed to check authentication: ' + error.message);
            });
        }

        // Check broadcasting configuration
        function checkBroadcastingConfig() {
            logActivity('Checking broadcasting configuration...');

            fetch('/api/notification-diagnostics')
            .then(response => response.json())
            .then(data => {
                document.getElementById('broadcast-driver').textContent = data.broadcast_driver || '-';
                document.getElementById('pusher-key').textContent = data.pusher_app_key || '-';
                document.getElementById('pusher-cluster').textContent = 'ap2'; // Default cluster

                logActivity('✅ Broadcasting config loaded: ' + data.broadcast_driver + ' driver');
            })
            .catch(error => {
                logActivity('❌ Failed to load broadcasting config: ' + error.message);
            });
        }

        // Initialize Echo monitoring
        function initializeEchoMonitoring() {
            logActivity('Initializing Echo monitoring...');

            // Wait for Echo to be ready
            const checkEcho = setInterval(() => {
                if (typeof window.Echo !== 'undefined' && window.Echo.connector) {
                    clearInterval(checkEcho);
                    updateConnectionStatus();

                    // Monitor connection state changes
                    if (window.Echo.connector.pusher) {
                        window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                            logActivity('🔄 Pusher state changed: ' + states.previous + ' → ' + states.current);
                            updateConnectionStatus();
                        });

                        window.Echo.connector.pusher.connection.bind('connected', () => {
                            logActivity('🟢 Pusher connected successfully');
                            updateConnectionStatus();
                        });

                        window.Echo.connector.pusher.connection.bind('disconnected', () => {
                            logActivity('🔴 Pusher disconnected');
                            updateConnectionStatus();
                        });
                    }
                }
            }, 100);
        }

        // Update connection status display
        function updateConnectionStatus() {
            const connectionStatus = document.getElementById('connection-status');
            const connectionDetails = document.getElementById('connection-details');

            if (typeof window.Echo !== 'undefined' && window.Echo.connector) {
                const pusher = window.Echo.connector.pusher;
                const state = pusher ? pusher.connection.state : 'unknown';
                const socketId = pusher ? pusher.connection.socket_id : null;

                let statusClass = 'status-disconnected';
                let statusText = 'Disconnected';

                switch (state) {
                    case 'connected':
                        statusClass = 'status-connected';
                        statusText = 'Connected';
                        break;
                    case 'connecting':
                        statusClass = 'status-connecting';
                        statusText = 'Connecting...';
                        break;
                    default:
                        statusClass = 'status-disconnected';
                        statusText = 'Disconnected';
                }

                connectionStatus.innerHTML = `<div class="status-indicator ${statusClass}"></div><span>${statusText} (${state})</span>`;

                if (state === 'connected') {
                    connectionDetails.style.display = 'block';
                    document.getElementById('echo-status').textContent = 'Ready';
                    document.getElementById('pusher-state').textContent = state;
                    document.getElementById('socket-id').textContent = socketId || 'N/A';
                }
            } else {
                connectionStatus.innerHTML = '<div class="status-indicator status-disconnected"></div><span>Echo not initialized</span>';
            }
        }

        // Run authenticated broadcasting test
        function runAuthenticatedBroadcastingTest() {
            const button = document.getElementById('test-authenticated-notification');
            const originalText = button.textContent;

            button.disabled = true;
            button.textContent = '⏳ Running Test...';
            logActivity('🚀 Starting authenticated broadcasting test...');

            fetch('/test-authenticated-broadcasting', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                testResults = data;

                const resultsDiv = document.getElementById('test-results');
                const resultsContent = document.getElementById('test-results-content');

                if (data.success) {
                    resultsDiv.className = 'alert alert-success mt-2';
                    resultsContent.innerHTML = `
                        <strong>✅ Test Successful!</strong><br>
                        <small>Notification sent to user ${data.user.name} (ID: ${data.user.id})</small><br>
                        <small>Channel: ${data.notification.channel}</small><br>
                        <small>Check browser console and activity log for reception confirmation</small>
                    `;
                    logActivity('✅ Backend test completed: Notification sent successfully');
                } else {
                    resultsDiv.className = 'alert alert-danger mt-2';
                    resultsContent.innerHTML = `
                        <strong>❌ Test Failed!</strong><br>
                        <small>${data.message}</small><br>
                        ${data.error ? '<small>Error: ' + data.error + '</small>' : ''}
                    `;
                    logActivity('❌ Backend test failed: ' + data.message);
                }

                resultsDiv.style.display = 'block';
            })
            .catch(error => {
                logActivity('❌ Test request failed: ' + error.message);
                const resultsDiv = document.getElementById('test-results');
                const resultsContent = document.getElementById('test-results-content');
                resultsDiv.className = 'alert alert-danger mt-2';
                resultsContent.innerHTML = '<strong>❌ Request Failed!</strong><br><small>' + error.message + '</small>';
                resultsDiv.style.display = 'block';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        }

        // Log activity
        function logActivity(message) {
            const logContainer = document.getElementById('activity-log');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            logEntry.innerHTML = `<strong>${timestamp}:</strong> ${message}`;
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        // Clear logs
        function clearLogs() {
            document.getElementById('activity-log').innerHTML = '';
            logActivity('Logs cleared');
        }

        // Listen for notification events to confirm reception
        document.addEventListener('enhancedNotificationReceived', function(event) {
            const notification = event.detail;
            logActivity('🔔 Frontend notification received: ' + notification.title);
            logActivity('📝 Message: ' + notification.message);
            logActivity('🏷️ Type: ' + notification.type);
        });

        // Also listen for the legacy event
        document.addEventListener('notificationReceived', function(event) {
            const notification = event.detail;
            logActivity('🔔 Legacy notification received: ' + notification.title);
        });
    </script>
</body>
</html>
