<!DOCTYPE html>
<html>
<head>
    <title>Broadcasting Auth Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>🔐 Broadcasting Authentication Test</h1>

    <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3>Current User Info:</h3>
        <p><strong>User ID:</strong> {{ auth()->id() }}</p>
        <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
        <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
    </div>

    <button onclick="testAuth()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        🔐 Test Broadcasting Auth
    </button>

    <button onclick="testEcho()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
        📡 Test Echo Connection
    </button>

    <div id="result" style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px;"></div>

    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <script>
        function log(message) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML += '<p>[' + new Date().toLocaleTimeString() + '] ' + message + '</p>';
            resultDiv.scrollTop = resultDiv.scrollHeight;
        }

        async function testAuth() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<h3>🔐 Broadcasting Auth Test Results:</h3>';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 'missing';
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '1';

                log('🔍 Starting authentication test...');
                log('👤 User ID: ' + userId);
                log('🔐 CSRF Token: ' + (csrfToken !== 'missing' ? '✅ Found' : '❌ Missing'));

                const response = await fetch('/broadcasting/auth', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        socket_id: 'test.123',
                        channel_name: `private-App.User.${userId}`
                    })
                });

                log(`📊 Response Status: ${response.status}`);

                if (response.ok) {
                    const data = await response.text();
                    log('✅ Authentication successful!');
                    log('📦 Response Data: ' + data);
                } else {
                    const error = await response.text();
                    log('❌ Authentication failed!');
                    log('📦 Error Response: ' + error);
                }

            } catch (error) {
                log('❌ Request failed: ' + error.message);
            }
        }

        async function testEcho() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<h3>📡 Echo Connection Test Results:</h3>';

            try {
                log('🔍 Testing Echo connection...');

                // Initialize Pusher
                const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
                    cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
                    encrypted: true
                });

                log('📡 Pusher initialized');

                pusher.connection.bind('connected', () => {
                    log('✅ Pusher connected successfully!');
                    log('🆔 Socket ID: ' + pusher.connection.socket_id);
                });

                pusher.connection.bind('error', (error) => {
                    log('❌ Pusher connection error: ' + JSON.stringify(error));
                });

                pusher.connection.bind('state_change', (states) => {
                    log(`🔄 Connection state changed: ${states.previous} → ${states.current}`);
                });

                // Test private channel
                const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || '1';
                const channelName = `private-App.User.${userId}`;

                log('🔒 Testing private channel: ' + channelName);

                const channel = pusher.subscribe(channelName);

                channel.bind('pusher:subscription_succeeded', () => {
                    log('✅ Private channel subscription successful!');
                });

                channel.bind('pusher:subscription_error', (error) => {
                    log('❌ Private channel subscription failed: ' + JSON.stringify(error));
                });

                // Test notification event
                channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', (data) => {
                    log('🔔 NOTIFICATION EVENT RECEIVED!');
                    log('📦 Event Data: ' + JSON.stringify(data));
                });

            } catch (error) {
                log('❌ Echo test failed: ' + error.message);
            }
        }
    </script>
</body>
</html>
