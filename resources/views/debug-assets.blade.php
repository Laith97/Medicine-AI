<!DOCTYPE html>
<html>
<head>
    <title>🔍 Asset Debug Page</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5;">
    <h1>🔍 Asset Loading Debug</h1>

    <div id="results" style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;"></div>

    <button onclick="runDebug()" style="background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
        🔍 Run Complete Debug
    </button>

    <!-- Include the Vite assets -->
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <script>
        function log(message, color = '#000') {
            const results = document.getElementById('results');
            results.innerHTML += `<div style="color: ${color}; margin: 5px 0; padding: 5px; border-left: 3px solid ${color}; padding-left: 10px;">[${new Date().toLocaleTimeString()}] ${message}</div>`;
            console.log(message);
        }

        function runDebug() {
            const results = document.getElementById('results');
            results.innerHTML = '<h3>🔍 Debug Results:</h3>';

            log('🚀 Starting comprehensive debug...', '#007bff');

            // 1. Check basic JavaScript
            log('1️⃣ Testing basic JavaScript...', '#28a745');
            try {
                log('✅ JavaScript is working', '#28a745');
            } catch (e) {
                log('❌ JavaScript error: ' + e.message, '#dc3545');
            }

            // 2. Check window objects
            log('2️⃣ Checking window objects...', '#28a745');
            log(`📦 window.Pusher: ${typeof window.Pusher}`, window.Pusher ? '#28a745' : '#dc3545');
            log(`📦 window.Echo: ${typeof window.Echo}`, window.Echo ? '#28a745' : '#dc3545');
            log(`📦 window.axios: ${typeof window.axios}`, window.axios ? '#28a745' : '#dc3545');
            log(`📦 window.Alpine: ${typeof window.Alpine}`, window.Alpine ? '#28a745' : '#dc3545');

            // 3. Check environment variables
            log('3️⃣ Checking environment variables...', '#28a745');
            log(`🔑 VITE_PUSHER_APP_KEY: ${import.meta.env.VITE_PUSHER_APP_KEY || 'MISSING'}`, import.meta.env.VITE_PUSHER_APP_KEY ? '#28a745' : '#dc3545');
            log(`🌍 VITE_PUSHER_APP_CLUSTER: ${import.meta.env.VITE_PUSHER_APP_CLUSTER || 'MISSING'}`, import.meta.env.VITE_PUSHER_APP_CLUSTER ? '#28a745' : '#dc3545');

            // 4. Check if modules were imported
            log('4️⃣ Checking module imports...', '#28a745');

            // Try to access Pusher directly
            if (window.Pusher) {
                log('✅ Pusher module accessible', '#28a745');
                try {
                    const testPusher = new window.Pusher('test-key', { cluster: 'test' });
                    log('✅ Pusher constructor works', '#28a745');
                } catch (e) {
                    log('❌ Pusher constructor error: ' + e.message, '#dc3545');
                }
            } else {
                log('❌ Pusher not found in window object', '#dc3545');
            }

            // Try to access Echo
            if (window.Echo) {
                log('✅ Echo module accessible', '#28a745');
                log(`📡 Echo connector: ${window.Echo.connector?.name || 'unknown'}`, '#17a2b8');
                log(`🔗 Echo pusher: ${typeof window.Echo.connector?.pusher}`, '#17a2b8');

                if (window.Echo.connector?.pusher) {
                    const state = window.Echo.connector.pusher.connection.state;
                    log(`📊 Pusher connection state: ${state}`, state === 'connected' ? '#28a745' : '#ffc107');
                }
            } else {
                log('❌ Echo not found in window object', '#dc3545');
            }

            // 5. Check if notification system is available
            log('5️⃣ Checking notification system...', '#28a745');
            log(`📢 notificationSystem: ${typeof window.notificationSystem}`, window.notificationSystem ? '#28a745' : '#dc3545');
            log(`🔊 notificationSound: ${typeof window.notificationSound}`, window.notificationSound ? '#28a745' : '#dc3545');

            // 6. Check console for errors
            log('6️⃣ Check browser console for detailed errors', '#17a2b8');
            log('✅ Debug completed! Check results above.', '#28a745');
        }

        // Auto-run on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(runDebug, 1000); // Wait 1 second for assets to load
        });
    </script>
</body>
</html>
