@extends('master')

@section('title', 'Test Appointment Notifications')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Test Appointment Notifications</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Instructions:</strong>
                        <ol>
                            <li>Open browser console (F12)</li>
                            <li>Click the test buttons below</li>
                            <li>Check if notifications appear and sounds play</li>
                        </ol>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <button id="testNotificationBtn" class="btn btn-primary w-100">
                                <i class="bi bi-bell"></i> Test Basic Notification
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="testAppointmentBtn" class="btn btn-success w-100">
                                <i class="bi bi-calendar-check"></i> Test Appointment Notification
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="testSoundBtn" class="btn btn-warning w-100">
                                <i class="bi bi-speaker"></i> Test Sound Only
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="testToastBtn" class="btn btn-info w-100">
                                <i class="bi bi-chat-square"></i> Test Toast Only
                            </button>
                        </div>
                        <div class="col-12">
                            <button id="sendRealNotificationBtn" class="btn btn-danger w-100">
                                <i class="bi bi-broadcast"></i> Send Real Laravel Notification
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>System Status:</h6>
                        <div id="systemStatus" class="small text-muted">
                            Loading system status...
                        </div>
                    </div>

                    <div class="mt-3">
                        <h6>Console Output:</h6>
                        <div id="consoleOutput" class="border rounded p-2 small" style="height: 200px; overflow-y: auto; background: #f8f9fa; font-family: monospace;">
                            <!-- Console output will appear here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consoleOutput = document.getElementById('consoleOutput');
    const systemStatus = document.getElementById('systemStatus');

    // Override console.log to display in the page
    const originalLog = console.log;
    console.log = function(...args) {
        originalLog.apply(console, args);
        const message = args.join(' ');
        consoleOutput.innerHTML += `<div>[${new Date().toLocaleTimeString()}] ${message}</div>`;
        consoleOutput.scrollTop = consoleOutput.scrollHeight;
    };

    // Check system status
    function updateSystemStatus() {
        const status = {
            echo: typeof window.Echo !== 'undefined' && window.Echo.connector,
            notificationSound: typeof window.notificationSound !== 'undefined',
            unifiedNotifications: typeof window.unifiedNotifications !== 'undefined',
            userId: document.querySelector('meta[name="user-id"]')?.getAttribute('content'),
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        };

        systemStatus.innerHTML = `
            <div class="row small">
                <div class="col-sm-6">Echo: <span class="text-${status.echo ? 'success' : 'danger'}">${status.echo ? '✓' : '✗'}</span></div>
                <div class="col-sm-6">Sound: <span class="text-${status.notificationSound ? 'success' : 'danger'}">${status.notificationSound ? '✓' : '✗'}</span></div>
                <div class="col-sm-6">Unified: <span class="text-${status.unifiedNotifications ? 'success' : 'danger'}">${status.unifiedNotifications ? '✓' : '✗'}</span></div>
                <div class="col-sm-6">User ID: <span class="text-${status.userId ? 'success' : 'danger'}">${status.userId || 'Missing'}</span></div>
            </div>
        `;
    }

    // Update status every 2 seconds
    setInterval(updateSystemStatus, 2000);
    updateSystemStatus();

    // Test basic notification
    document.getElementById('testNotificationBtn').addEventListener('click', function() {
        console.log('🧪 Testing basic notification...');
        if (window.unifiedNotifications) {
            window.unifiedNotifications.testNotification();
        } else if (typeof testNotifications === 'function') {
            testNotifications();
        } else {
            console.error('❌ No notification system available');
        }
    });

    // Test appointment notification
    document.getElementById('testAppointmentBtn').addEventListener('click', function() {
        console.log('🧪 Testing appointment notification...');
        const appointmentNotification = {
            id: 'test-appointment-' + Date.now(),
            type: 'appointment_booked',
            title: 'New Appointment Booked',
            message: 'Dr. Smith has a new appointment scheduled for tomorrow at 2:00 PM',
            data: {
                appointment_id: 123,
                doctor_name: 'Dr. Smith',
                appointment_date: new Date().toISOString()
            },
            created_at: new Date().toISOString()
        };

        if (window.unifiedNotifications) {
            window.unifiedNotifications.handleNewNotification(appointmentNotification);
        } else {
            console.error('❌ Unified notification system not available');
        }
    });

    // Test sound only
    document.getElementById('testSoundBtn').addEventListener('click', function() {
        console.log('🧪 Testing notification sound...');
        if (window.notificationSound) {
            window.notificationSound.play();
            console.log('🔊 Sound played via notificationSound');
        } else if (window.unifiedNotifications?.sound) {
            window.unifiedNotifications.sound.play();
            console.log('🔊 Sound played via unifiedNotifications');
        } else {
            // Fallback sound
            const audio = new Audio('/sounds/notification.mp3');
            audio.play().then(() => {
                console.log('🔊 Fallback sound played');
            }).catch(e => {
                console.error('❌ Failed to play sound:', e);
            });
        }
    });

    // Test toast only
    document.getElementById('testToastBtn').addEventListener('click', function() {
        console.log('🧪 Testing toast notification...');
        const testNotification = {
            id: 'test-toast-' + Date.now(),
            type: 'test',
            title: 'Test Toast',
            message: 'This is a test toast notification to verify the display is working.',
            data: {},
            created_at: new Date().toISOString()
        };

        if (window.unifiedNotifications) {
            window.unifiedNotifications.showToastNotification(testNotification);
        } else {
            console.error('❌ Unified notification system not available');
        }
    });

    // Send real Laravel notification
    document.getElementById('sendRealNotificationBtn').addEventListener('click', async function() {
        console.log('🧪 Sending real Laravel notification...');
        try {
            const response = await fetch('/notifications/test', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                console.log('✅ Real notification sent successfully');
            } else {
                console.error('❌ Failed to send real notification, status:', response.status);
            }
        } catch (error) {
            console.error('❌ Error sending real notification:', error);
        }
    });

    console.log('🚀 Test page loaded and ready');
});
</script>
@endsection
