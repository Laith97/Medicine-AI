/**
 * Backend Notification System Diagnosis
 * Checks if the issue is with Laravel/Pusher backend
 */
window.backendDiagnosis = {

    runFullDiagnosis() {
        console.log('🔍 Running Backend Notification Diagnosis...\n');

        // Test 1: Check if Pusher is receiving ANY events
        this.testPusherConnection();

        // Test 2: Check if we can force send a notification
        this.testForceNotification();

        // Test 3: Check notification preferences
        this.checkNotificationPreferences();

        // Test 4: Test queue system
        this.testQueueSystem();

        console.log('\n📋 Check results above and run suggested fixes');
    },

    testPusherConnection() {
        console.log('📡 Testing Pusher Connection...');

        if (!window.Echo || !window.Echo.connector) {
            console.error('❌ Echo not available');
            return;
        }

        const pusher = window.Echo.connector.pusher;
        if (!pusher) {
            console.error('❌ Pusher connector not available');
            return;
        }

        console.log('✅ Pusher connector available');
        console.log('📊 Connection state:', pusher.connection.state);
        console.log('📊 Socket ID:', pusher.connection.socket_id);

        if (pusher.connection.state !== 'connected') {
            console.error('❌ Pusher not connected!');
            console.log('💡 Check your .env PUSHER_* settings');
            return;
        }

        console.log('✅ Pusher is connected');

        // Start monitoring for ANY events
        console.log('🔍 Monitoring for ANY Pusher events (10 seconds)...');
        let eventCount = 0;

        const globalHandler = (eventName, data) => {
            eventCount++;
            console.log(`📨 [EVENT ${eventCount}] ${eventName}`, data);
        };

        pusher.bind_global(globalHandler);

        setTimeout(() => {
            pusher.unbind_global(globalHandler);
            if (eventCount === 0) {
                console.error('❌ No Pusher events received in 10 seconds');
                console.log('💡 This suggests notifications are not being broadcast from Laravel');
            } else {
                console.log(`✅ Received ${eventCount} Pusher events`);
            }
        }, 10000);
    },

    async testForceNotification() {
        console.log('🧪 Testing Force Notification...');

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!userId || !csrfToken) {
            console.error('❌ User ID or CSRF token missing');
            return;
        }

        try {
            // Try to trigger a test notification via API
            const response = await fetch('/api/test-notification', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type: 'test',
                    message: 'Backend diagnosis test notification'
                })
            });

            if (response.ok) {
                const result = await response.json();
                console.log('✅ Test notification API call successful:', result);
                console.log('🔍 Watch for Pusher events in the next 5 seconds...');
            } else {
                console.error('❌ Test notification API failed:', response.status, response.statusText);
                console.log('💡 The /api/test-notification endpoint might not exist');
            }
        } catch (error) {
            console.error('❌ Test notification failed:', error);
            console.log('💡 We need to create a test notification endpoint');
        }
    },

    async checkNotificationPreferences() {
        console.log('⚙️ Checking Notification Preferences...');

        try {
            const response = await fetch('/api/notification-preferences', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (response.ok) {
                const prefs = await response.json();
                console.log('✅ Notification preferences:', prefs);

                if (prefs.appointment_booked === false) {
                    console.error('❌ Appointment notifications are DISABLED in preferences!');
                    console.log('💡 Enable appointment notifications in user settings');
                } else {
                    console.log('✅ Appointment notifications are enabled');
                }
            } else {
                console.warn('⚠️ Could not check notification preferences');
            }
        } catch (error) {
            console.warn('⚠️ Error checking preferences:', error);
        }
    },

    async testQueueSystem() {
        console.log('📋 Testing Queue System...');

        try {
            // Check if queue is running
            const response = await fetch('/api/queue-status', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (response.ok) {
                const status = await response.json();
                console.log('✅ Queue status:', status);
            } else {
                console.warn('⚠️ Could not check queue status');
                console.log('💡 Make sure Laravel queue worker is running: php artisan queue:work');
            }
        } catch (error) {
            console.warn('⚠️ Queue status check failed:', error);
            console.log('💡 Queue worker might not be running - notifications could be stuck in queue');
        }
    },

    suggestFixes() {
        console.log('\n🔧 SUGGESTED FIXES:\n');

        console.log('1. CHECK PUSHER CONFIGURATION:');
        console.log('   • Verify .env PUSHER_* settings are correct');
        console.log('   • Check Pusher dashboard for API key/secret');
        console.log('   • Ensure BROADCAST_DRIVER=pusher in .env');
        console.log('');

        console.log('2. CHECK QUEUE SYSTEM:');
        console.log('   • Run: php artisan queue:work');
        console.log('   • Check: php artisan queue:failed');
        console.log('   • Clear failed: php artisan queue:flush');
        console.log('');

        console.log('3. CHECK NOTIFICATION PREFERENCES:');
        console.log('   • Go to user profile settings');
        console.log('   • Enable "Appointment Notifications"');
        console.log('   • Save settings');
        console.log('');

        console.log('4. MANUAL TEST:');
        console.log('   • Run: php artisan make:command TestNotificationCommand');
        console.log('   • Then: php artisan test:notification [user-id]');
        console.log('');

        console.log('5. CHECK LOGS:');
        console.log('   • Check: storage/logs/laravel.log');
        console.log('   • Look for broadcast/pusher errors');
    }
};

// Add convenient method
window.diagnoseBackend = () => window.backendDiagnosis.runFullDiagnosis();
