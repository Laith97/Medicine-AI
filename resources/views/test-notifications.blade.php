@extends('master')

@section('title', 'Test Notifications')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3>🧪 Real-time Notification Test</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Current User Info:</h5>
                        <p><strong>User ID:</strong> {{ auth()->id() }}</p>
                        <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
                        <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                        <p><strong>Role:</strong> {{ auth()->user()->role }}</p>
                    </div>

                    <div class="mb-4">
                        <h5>Environment Check:</h5>
                        <p><strong>Pusher App Key:</strong> {{ env('PUSHER_APP_KEY') ? '✅ Set' : '❌ Not Set' }}</p>
                        <p><strong>Pusher Cluster:</strong> {{ env('PUSHER_APP_CLUSTER', 'Not Set') }}</p>
                        <p><strong>Broadcast Driver:</strong> {{ env('BROADCAST_CONNECTION', 'Not Set') }}</p>
                        <p><strong>Queue Driver:</strong> {{ env('QUEUE_CONNECTION', 'Not Set') }}</p>
                    </div>

                    <div class="mb-4">
                        <h5>Test Actions:</h5>
                        <button class="btn btn-primary me-2" onclick="sendTestNotification()">Send Test Notification</button>
                        <button class="btn btn-success me-2" onclick="testSound()">Test Sound</button>
                        <button class="btn btn-info me-2" onclick="checkConnection()">Check Connection</button>
                        <button class="btn btn-warning" onclick="toggleDebugPanel()">Toggle Debug Panel</button>
                    </div>

                    <div class="mb-4">
                        <h5>Test Results:</h5>
                        <div id="test-results" class="alert alert-info">
                            <p>Click the buttons above to test the notification system...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let testResults = document.getElementById('test-results');

function log(message) {
    console.log(message);
    testResults.innerHTML += '<p>' + new Date().toLocaleTimeString() + ' - ' + message + '</p>';
    testResults.scrollTop = testResults.scrollHeight;
}

function clearLog() {
    testResults.innerHTML = '<p>Test log cleared...</p>';
}

async function sendTestNotification() {
    log('🧪 Sending test notification...');
    try {
        const response = await fetch('/notifications/test');
        const result = await response.json();

        if (result.success) {
            log('✅ Test notification sent successfully!');
            log('📨 Response: ' + result.message);
        } else {
            log('❌ Failed to send notification: ' + JSON.stringify(result));
        }
    } catch (error) {
        log('❌ Error: ' + error.message);
    }
}

function testSound() {
    log('🔊 Testing notification sound...');
    if (window.notificationSound) {
        window.notificationSound.play();
        log('✅ Sound test triggered');
    } else {
        log('❌ Notification sound system not available');
    }
}

function checkConnection() {
    log('🔍 Checking connection status...');

    // Check Echo
    if (typeof window.Echo !== 'undefined') {
        log('✅ Echo is loaded');

        if (window.Echo.connector) {
            log('📡 Echo connector: ' + (window.Echo.connector.name || 'unknown'));

            if (window.Echo.connector.pusher) {
                const state = window.Echo.connector.pusher.connection.state;
                log('📊 Pusher state: ' + state);

                if (state === 'connected') {
                    log('✅ Pusher connected successfully!');
                } else {
                    log('⚠️ Pusher not connected. Current state: ' + state);
                }
            }
        }
    } else {
        log('❌ Echo not loaded');
    }

    // Check notification system
    if (window.notificationSystem) {
        log('✅ Global notification system loaded');
        log('📊 Unread count: ' + window.notificationSystem.unreadCount);
    } else {
        log('❌ Global notification system not loaded');
    }

    // Check user ID
    const userMeta = document.querySelector('meta[name="user-id"]');
    if (userMeta) {
        log('👤 User ID from meta: ' + userMeta.content);
    } else {
        log('❌ User ID not found in meta tags');
    }
}

function toggleDebugPanel() {
    if (window.echoDebugger) {
        window.echoDebugger.toggle();
        log('🐛 Debug panel toggled');
    } else {
        log('❌ Debug panel not available');
    }
}

// Listen for notifications
document.addEventListener('notificationReceived', function(event) {
    log('🔔 REAL-TIME NOTIFICATION RECEIVED!');
    log('📋 Notification: ' + JSON.stringify(event.detail));
});

// Auto-check connection on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(checkConnection, 2000);
});
</script>
@endsection
