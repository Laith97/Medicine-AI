<!DOCTYPE html>
<html>
<head>
    <title>🔧 Notification System Diagnostics</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="notification-sound-enabled" content="true">
    <meta name="notification-toast-enabled" content="true">
    @endauth
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left: 4px solid #28a745; }
        .warning { border-left: 4px solid #ffc107; }
        .error { border-left: 4px solid #dc3545; }
        .log {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { background: #0056b3; }
        button:disabled { background: #6c757d; cursor: not-allowed; }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.good { background: #d4edda; color: #155724; }
        .status.bad { background: #f8d7da; color: #721c24; }
        .status.pending { background: #fff3cd; color: #856404; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Real-time Notification System Diagnostics</h1>
        <p><strong>Current Time:</strong> <span id="currentTime"></span></p>

        @auth
        <div class="grid">
            <!-- System Status -->
            <div class="test-section">
                <h3>📊 System Status</h3>
                <div id="systemStatus">
                    <div><strong>User ID:</strong> <span id="userId">{{ Auth::id() }}</span></div>
                    <div><strong>CSRF Token:</strong> <span class="status" id="csrfStatus">Checking...</span></div>
                    <div><strong>Echo Library:</strong> <span class="status" id="echoStatus">Checking...</span></div>
                    <div><strong>Pusher Connection:</strong> <span class="status" id="pusherStatus">Checking...</span></div>
                    <div><strong>Enhanced System:</strong> <span class="status" id="enhancedStatus">Checking...</span></div>
                    <div><strong>Sound System:</strong> <span class="status" id="soundStatus">Checking...</span></div>
                    <div><strong>Dropdown Component:</strong> <span class="status" id="dropdownStatus">Checking...</span></div>
                </div>
            </div>

            <!-- Configuration -->
            <div class="test-section">
                <h3>⚙️ Configuration</h3>
                <div>
                    <div><strong>Broadcasting Driver:</strong> {{ config('broadcasting.default') }}</div>
                    <div><strong>Queue Driver:</strong> {{ config('queue.default') }}</div>
                    <div><strong>App Environment:</strong> {{ app()->environment() }}</div>
                    <div><strong>Pusher App ID:</strong> {{ config('broadcasting.connections.pusher.app_id') }}</div>
                    <div><strong>Pusher Cluster:</strong> {{ config('broadcasting.connections.pusher.options.cluster') }}</div>
                    <div><strong>Expected Channel:</strong> App.User.{{ Auth::id() }}</div>
                </div>
            </div>
        </div>

        <!-- Test Controls -->
        <div class="test-section">
            <h3>🧪 Test Controls</h3>
            <button id="diagBtn">🔍 Run Full Diagnostics</button>
            <button id="testBtn">📤 Send Enhanced Test</button>
            <button id="legacyBtn">📤 Send Legacy Test</button>
            <button id="directBtn">⚡ Send Direct Test</button>
            <button id="pusherBtn">📡 Test Pusher Direct</button>
            <button id="soundBtn">🔊 Test Sound</button>
            <button id="dropdownBtn">📋 Test Dropdown</button>
            <button id="clearBtn">🗑️ Clear Logs</button>
        </div>

        <!-- Real-time Event Monitor -->
        <div class="test-section">
            <h3>📡 Real-time Event Monitor</h3>
            <div>
                <strong>Events Received:</strong> <span id="eventCount">0</span>
                <button id="monitorBtn">Start Monitoring</button>
            </div>
            <div class="log" id="eventLog">
                <div style="color: #666;">Event monitor inactive - click "Start Monitoring" to begin</div>
            </div>
        </div>

        <!-- System Logs -->
        <div class="test-section">
            <h3>📋 System Logs</h3>
            <div class="log" id="systemLog">
                <div style="color: #666;">System logs will appear here...</div>
            </div>
        </div>
        @else
        <div class="test-section error">
            <h3>🚫 Authentication Required</h3>
            <p>Please <a href="/login">login</a> to access the notification diagnostics.</p>
        </div>
        @endauth
    </div>

    <!-- Load scripts -->
    <script src="{{ asset('sounds/notification-sound.js') }}"></script>
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <script>
    // Global variables
    let eventCount = 0;
    let monitoring = false;

    // Utility functions
    function updateTime() {
        document.getElementById('currentTime').textContent = new Date().toLocaleString();
    }

    function log(message, color = '#333', target = 'systemLog') {
        console.log(message);
        const logContainer = document.getElementById(target);
        if (!logContainer) return;

        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.style.color = color;
        logEntry.style.marginBottom = '4px';
        logEntry.innerHTML = `<span style="color: #666;">[${timestamp}]</span> ${message}`;
        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function eventLog(message, color = '#0066cc') {
        log(message, color, 'eventLog');
        eventCount++;
        const eventCountEl = document.getElementById('eventCount');
        if (eventCountEl) eventCountEl.textContent = eventCount;
    }

    function updateStatus(elementId, status, isGood) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = status;
            element.className = 'status ' + (isGood ? 'good' : 'bad');
        }
    }

    // Diagnostic functions
    function runFullDiagnostics() {
        log('🔍 Starting full system diagnostics...', '#007bff');

        // Check CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        updateStatus('csrfStatus', csrfToken ? 'Found' : 'Missing', !!csrfToken);

        // Check Echo
        const echoAvailable = typeof window.Echo !== 'undefined';
        updateStatus('echoStatus', echoAvailable ? 'Available' : 'Missing', echoAvailable);

        // Check Pusher Connection
        if (echoAvailable && window.Echo.connector) {
            const pusher = window.Echo.connector.pusher;
            if (pusher) {
                const connectionState = pusher.connection.state;
                updateStatus('pusherStatus', connectionState, connectionState === 'connected');
                log(`📡 Pusher connection state: ${connectionState}`, connectionState === 'connected' ? '#28a745' : '#dc3545');
                log(`🆔 Socket ID: ${pusher.connection.socket_id || 'None'}`, '#6c757d');
            } else {
                updateStatus('pusherStatus', 'No Pusher', false);
            }
        } else {
            updateStatus('pusherStatus', 'Echo Missing', false);
        }

        // Check Enhanced System
        const enhancedAvailable = typeof window.enhancedNotificationSystem !== 'undefined';
        updateStatus('enhancedStatus', enhancedAvailable ? 'Active' : 'Missing', enhancedAvailable);

        if (enhancedAvailable) {
            log('✅ Enhanced notification system found', '#28a745');
            log(`🔊 Sound enabled: ${window.enhancedNotificationSystem.soundEnabled}`, '#17a2b8');
            log(`📋 Toast enabled: ${window.enhancedNotificationSystem.toastEnabled}`, '#17a2b8');
        } else {
            log('❌ Enhanced notification system not found', '#dc3545');
        }

        // Check Sound System
        const soundAvailable = typeof window.notificationSound !== 'undefined';
        updateStatus('soundStatus', soundAvailable ? 'Available' : 'Missing', soundAvailable);

        // Check Dropdown Component
        const dropdownAvailable = typeof window.notificationDropdownInstance !== 'undefined';
        updateStatus('dropdownStatus', dropdownAvailable ? 'Active' : 'Missing', dropdownAvailable);

        log('🏁 Diagnostics complete', '#28a745');
    }

    async function testEnhancedNotification() {
        log('🧪 Testing enhanced notification...', '#007bff');

        try {
            const response = await fetch('/api/test/notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    title: 'Enhanced Diagnostic Test',
                    message: `Test notification sent at ${new Date().toLocaleTimeString()}`
                })
            });

            if (response.ok) {
                const data = await response.json();
                log('✅ Enhanced test notification sent successfully', '#28a745');
                log(`📨 Response: ${JSON.stringify(data, null, 2)}`, '#6c757d');
            } else {
                log(`❌ Failed to send notification: ${response.status}`, '#dc3545');
            }
        } catch (error) {
            log(`❌ Error: ${error.message}`, '#dc3545');
        }
    }

    async function testLegacyNotification() {
        log('🧪 Testing legacy notification...', '#007bff');

        try {
            const response = await fetch('/api/test-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: `Legacy test at ${new Date().toLocaleTimeString()}`
                })
            });

            if (response.ok) {
                const data = await response.json();
                log('✅ Legacy test notification sent successfully', '#28a745');
                log(`📨 Response: ${JSON.stringify(data, null, 2)}`, '#6c757d');
            } else {
                log(`❌ Failed to send legacy notification: ${response.status}`, '#dc3545');
            }
        } catch (error) {
            log(`❌ Error: ${error.message}`, '#dc3545');
        }
    }

    async function testDirectNotification() {
        log('⚡ Testing direct notification (bypass queue)...', '#007bff');

        try {
            const response = await fetch('/api/test/direct-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                const data = await response.json();
                log('✅ Direct test notification sent successfully', '#28a745');
                log(`📨 Response: ${JSON.stringify(data, null, 2)}`, '#6c757d');
            } else {
                const error = await response.text();
                log(`❌ Failed to send direct notification: ${response.status}`, '#dc3545');
                log(`📨 Error: ${error}`, '#dc3545');
            }
        } catch (error) {
            log(`❌ Error: ${error.message}`, '#dc3545');
        }
    }

    async function testPusherConnection() {
        log('📡 Testing direct Pusher connection...', '#007bff');

        try {
            const response = await fetch('/api/test/pusher-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                const data = await response.json();
                log('✅ Direct Pusher test sent successfully', '#28a745');
                log(`📨 Response: ${JSON.stringify(data, null, 2)}`, '#6c757d');

                // Also log the system status
                const statusResponse = await fetch('/api/test/system-status');
                if (statusResponse.ok) {
                    const status = await statusResponse.json();
                    log('📊 System Status:', '#17a2b8');
                    log(`• Broadcast Driver: ${status.broadcast_driver}`, '#6c757d');
                    log(`• Queue Driver: ${status.queue_driver}`, '#6c757d');
                    log(`• Pusher App ID: ${status.pusher_config.app_id}`, '#6c757d');
                    log(`• Pusher Cluster: ${status.pusher_config.cluster}`, '#6c757d');
                    log(`• Channel: ${status.channel}`, '#6c757d');
                }
            } else {
                const error = await response.text();
                log(`❌ Failed to test Pusher connection: ${response.status}`, '#dc3545');
                log(`📨 Error: ${error}`, '#dc3545');
            }
        } catch (error) {
            log(`❌ Error: ${error.message}`, '#dc3545');
        }
    }

    function testSoundSystem() {
        log('🔊 Testing sound system...', '#007bff');
        if (window.notificationSound) {
            window.notificationSound.play();
            log('✅ Sound test triggered', '#28a745');
        } else {
            log('❌ Sound system not available', '#dc3545');
        }
    }

    function testDropdownUpdate() {
        log('📋 Testing dropdown update...', '#007bff');
        if (window.notificationDropdownInstance) {
            const testNotification = {
                id: 'test-' + Date.now(),
                title: 'Test Dropdown Update',
                message: 'This is a test dropdown update',
                created_at: new Date().toISOString()
            };
            window.notificationDropdownInstance.handleNewNotification(testNotification);
            log('✅ Dropdown test update sent', '#28a745');
        } else {
            log('❌ Dropdown component not available', '#dc3545');
        }
    }

    function toggleEventMonitor() {
        monitoring = !monitoring;
        const btn = document.getElementById('monitorBtn');
        if (!btn) return;

        if (monitoring) {
            btn.textContent = 'Stop Monitoring';
            btn.style.background = '#dc3545';
            eventLog('🟢 Event monitoring started', '#28a745');
        } else {
            btn.textContent = 'Start Monitoring';
            btn.style.background = '#007bff';
            eventLog('🔴 Event monitoring stopped', '#dc3545');
        }
    }

    function clearAllLogs() {
        const systemLog = document.getElementById('systemLog');
        const eventLogEl = document.getElementById('eventLog');
        const eventCountEl = document.getElementById('eventCount');

        if (systemLog) systemLog.innerHTML = '<div style="color: #666;">System logs cleared...</div>';
        if (eventLogEl) eventLogEl.innerHTML = '<div style="color: #666;">Event logs cleared...</div>';
        if (eventCountEl) eventCountEl.textContent = '0';

        eventCount = 0;
        log('🗑️ Logs cleared', '#6c757d');
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Set up time updates
        setInterval(updateTime, 1000);
        updateTime();

        // Attach event listeners
        document.getElementById('diagBtn')?.addEventListener('click', runFullDiagnostics);
        document.getElementById('testBtn')?.addEventListener('click', testEnhancedNotification);
        document.getElementById('legacyBtn')?.addEventListener('click', testLegacyNotification);
        document.getElementById('directBtn')?.addEventListener('click', testDirectNotification);
        document.getElementById('pusherBtn')?.addEventListener('click', testPusherConnection);
        document.getElementById('soundBtn')?.addEventListener('click', testSoundSystem);
        document.getElementById('dropdownBtn')?.addEventListener('click', testDropdownUpdate);
        document.getElementById('clearBtn')?.addEventListener('click', clearAllLogs);
        document.getElementById('monitorBtn')?.addEventListener('click', toggleEventMonitor);

        log('🚀 Diagnostic page initialized', '#28a745');
        setTimeout(runFullDiagnostics, 1000);

        @auth
        // Listen for all notification events
        document.addEventListener('enhancedNotificationReceived', function(event) {
            if (monitoring) {
                eventLog('🔔 [ENHANCED] Notification received!', '#28a745');
                eventLog(`📝 Title: ${event.detail.title}`, '#17a2b8');
                eventLog(`💬 Message: ${event.detail.message}`, '#17a2b8');
            }
        });

        document.addEventListener('notificationReceived', function(event) {
            if (monitoring) {
                eventLog('🔔 [LEGACY] Notification received!', '#ffc107');
                eventLog(`📝 Data: ${JSON.stringify(event.detail).substring(0, 100)}...`, '#17a2b8');
            }
        });

        // Listen for Pusher events directly
        setTimeout(() => {
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                const pusher = window.Echo.connector.pusher;

                // Monitor all events
                pusher.bind_global((eventName, data) => {
                    if (monitoring && eventName.includes('App.User.{{ Auth::id() }}')) {
                        eventLog(`📡 [PUSHER] Raw event: ${eventName}`, '#6f42c1');
                        eventLog(`📊 Data: ${JSON.stringify(data).substring(0, 100)}...`, '#6c757d');
                    }
                });
            }
        }, 2000);
        @endauth
    });
    </script>
</body>
</html>
