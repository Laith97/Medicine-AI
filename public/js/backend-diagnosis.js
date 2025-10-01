/**
 * Backend Notification System Diagnosis
 * Checks if the issue is with Laravel/Pusher backend
 */
window.backendDiagnosis = {

    runFullDiagnosis() {
        

        // Test 1: Check if Pusher is receiving ANY events
        this.testPusherConnection();

        // Test 2: Check if we can force send a notification
        this.testForceNotification();

        // Test 3: Check notification preferences
        this.checkNotificationPreferences();

        // Test 4: Test queue system
        this.testQueueSystem();

        
    },

    testPusherConnection() {
        

        if (!window.Echo || !window.Echo.connector) {
            ;
            return;
        }

        const pusher = window.Echo.connector.pusher;
        if (!pusher) {
            ;
            return;
        }

        
        
        

        if (pusher.connection.state !== 'connected') {
            ;
            
            return;
        }

        

        // Start monitoring for ANY events
        let eventCount = 0;

        const globalHandler = (eventName, data) => {
            eventCount++;
            
        };

        pusher.bind_global(globalHandler);

        setTimeout(() => {
            pusher.unbind_global(globalHandler);
            if (eventCount === 0) {
                ;
                
            } else {
                
            }
        }, 10000);
    },

    async testForceNotification() {
        

        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!userId || !csrfToken) {
            ;
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
                
                
            } else {
                ;
                
            }
        } catch (error) {
            ;
            
        }
    },

    async checkNotificationPreferences() {
        

        try {
            const response = await fetch('/api/notification-preferences', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (response.ok) {
                const prefs = await response.json();
                

                if (prefs.appointment_booked === false) {
                    ;
                    
                } else {
                    
                }
            } else {
                ;
            }
        } catch (error) {
            ;
        }
    },

    async testQueueSystem() {
        

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
                
            } else {
                ;
                
            }
        } catch (error) {
            ;
            
        }
    },

    suggestFixes() {
        

        
        
        
        
        

        
        
        
        
        

        
        
        
        
        

        
        
        
        

        
        
        
    }
};

// Add convenient method
window.diagnoseBackend = () => window.backendDiagnosis.runFullDiagnosis();
