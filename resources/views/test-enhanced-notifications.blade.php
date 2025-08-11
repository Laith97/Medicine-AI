<!DOCTYPE html>
<html>
<head>
    <title>Enhanced Notification System Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="notification-sound-enabled" content="{{ env('NOTIFICATION_SOUND_ENABLED', 'true') }}">
    <meta name="notification-toast-enabled" content="{{ env('NOTIFICATION_TOAST_ENABLED', 'true') }}">
    @endauth
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 2px solid #ddd;
        }
        .success { border-color: #28a745; background-color: #d4edda; }
        .warning { border-color: #ffc107; background-color: #fff3cd; }
        .error { border-color: #dc3545; background-color: #f8d7da; }
        .log {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            max-height: 200px;
            overflow-y: auto;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        .badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <h1>🔔 Enhanced Notification System Test</h1>

    @auth
    <div class="status-box" id="status">
        <h3>System Status</h3>
        <div id="status-content">Loading...</div>
    </div>

    <div class="status-box">
        <h3>Test Controls</h3>
        <button onclick="testConnection()">Test Connection</button>
        <button onclick="testNotification()">Send Test Notification</button>
        <button onclick="testSound()">Test Sound</button>
        <button onclick="clearLogs()">Clear Logs</button>
        <div style="margin-top: 10px;">
            <label>
                <input type="checkbox" id="enableSound" checked> Enable Sound
            </label>
            <label style="margin-left: 15px;">
                <input type="checkbox" id="enableToast" checked> Enable Toast
            </label>
        </div>
    </div>

    <div class="status-box">
        <h3>Configuration</h3>
        <div id="config-content">Loading...</div>
    </div>

    <div class="status-box">
        <h3>Real-time Logs</h3>
        <div id="logs" class="log"></div>
    </div>
    @else
    <div class="status-box error">
        <h3>Authentication Required</h3>
        <p>Please <a href="/login">login</a> to test the notification system.</p>
    </div>
    @endauth

    <!-- Load notification sound -->
    <script src="{{ asset('sounds/notification-sound.js') }}"></script>

    <!-- Vite Assets -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    @auth
    <script>
    let logContainer;

    function log(message, color = '#333') {
        console.log(message);
        if (!logContainer) logContainer = document.getElementById('logs');

        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.style.color = color;
        logEntry.innerHTML = `[${timestamp}] ${message}`;
        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function clearLogs() {
        if (logContainer) {
            logContainer.innerHTML = '';
        }
    }

    function updateStatus() {
        const statusContent = document.getElementById('status-content');
        const configContent = document.getElementById('config-content');

        const status = {
            userId: document.querySelector('meta[name="user-id"]')?.getAttribute('content'),
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ? 'Present' : 'Missing',
            echoAvailable: typeof window.Echo !== 'undefined',
            echoConnector: window.Echo?.connector ? 'Connected' : 'Not Connected',
            pusherState: window.Echo?.connector?.pusher?.connection?.state || 'Unknown',
            notificationSystem: typeof window.enhancedNotificationSystem !== 'undefined',
            soundSystem: typeof window.notificationSound !== 'undefined'
        };

        statusContent.innerHTML = `
            <div><strong>User ID:</strong> ${status.userId || 'Not found'}</div>
            <div><strong>CSRF Token:</strong> ${status.csrfToken}</div>
            <div><strong>Echo Available:</strong> ${status.echoAvailable ? '✅' : '❌'}</div>
            <div><strong>Echo Connector:</strong> ${status.echoConnector}</div>
            <div><strong>Pusher State:</strong> ${status.pusherState}</div>
            <div><strong>Enhanced System:</strong> ${status.notificationSystem ? '✅' : '❌'}</div>
            <div><strong>Sound System:</strong> ${status.soundSystem ? '✅' : '❌'}</div>
        `;

        // Configuration
        configContent.innerHTML = `
            <div><strong>Pusher App Key:</strong> {{ env('VITE_PUSHER_APP_KEY') }}</div>
            <div><strong>Pusher Cluster:</strong> {{ env('VITE_PUSHER_APP_CLUSTER') }}</div>
            <div><strong>Channel:</strong> App.User.${status.userId}</div>
        `;
    }

    function testConnection() {
        log('🧪 Testing connection...', '#007bff');

        if (typeof window.Echo === 'undefined') {
            log('❌ Echo not available', '#dc3545');
            return;
        }

        if (!window.Echo.connector) {
            log('❌ Echo connector not available', '#dc3545');
            return;
        }

        const pusher = window.Echo.connector.pusher;
        if (pusher) {
            log(`📡 Pusher state: ${pusher.connection.state}`, pusher.connection.state === 'connected' ? '#28a745' : '#ffc107');
            log(`🆔 Socket ID: ${pusher.connection.socket_id || 'None'}`, '#6c757d');
        }

        if (window.enhancedNotificationSystem) {
            log('✅ Enhanced notification system is active', '#28a745');
            log(`🔊 Sound enabled: ${window.enhancedNotificationSystem.soundEnabled}`, '#17a2b8');
            log(`📋 Toast enabled: ${window.enhancedNotificationSystem.toastEnabled}`, '#17a2b8');
        } else {
            log('❌ Enhanced notification system not found', '#dc3545');
        }
    }

    async function testNotification() {
        log('🧪 Sending test notification...', '#007bff');

        try {
            const response = await fetch('/api/test/notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: 'Test Notification',
                    message: 'This is a test notification from the enhanced system'
                })
            });

            if (response.ok) {
                const data = await response.json();
                log('✅ Test notification sent successfully', '#28a745');
                log(`📨 Response: ${JSON.stringify(data)}`, '#6c757d');
            } else {
                log(`❌ Failed to send notification: ${response.status}`, '#dc3545');
            }
        } catch (error) {
            log(`❌ Error sending notification: ${error.message}`, '#dc3545');
        }
    }

    function testSound() {
        log('🔊 Testing sound...', '#007bff');
        if (window.notificationSound) {
            window.notificationSound.play();
            log('✅ Sound test triggered', '#28a745');
        } else {
            log('❌ Sound system not available', '#dc3545');
        }
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        log('🚀 Test page initialized', '#28a745');
        updateStatus();

        // Update status every 2 seconds
        setInterval(updateStatus, 2000);

        // Listen for our enhanced notifications
        document.addEventListener('enhancedNotificationReceived', function(event) {
            log('🔔 Enhanced notification received!', '#28a745');
            log(`📝 Title: ${event.detail.title}`, '#17a2b8');
            log(`💬 Message: ${event.detail.message}`, '#17a2b8');
        });

        // Settings controls
        document.getElementById('enableSound').addEventListener('change', function(e) {
            if (window.enhancedNotificationSystem) {
                window.enhancedNotificationSystem.soundEnabled = e.target.checked;
                log(`🔊 Sound ${e.target.checked ? 'enabled' : 'disabled'}`, '#17a2b8');
            }
        });

        document.getElementById('enableToast').addEventListener('change', function(e) {
            if (window.enhancedNotificationSystem) {
                window.enhancedNotificationSystem.toastEnabled = e.target.checked;
                log(`📋 Toast ${e.target.checked ? 'enabled' : 'disabled'}`, '#17a2b8');
            }
        });
    });
    </script>
    @endauth
</body>
</html>
