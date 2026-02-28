/**
 * Quick Notification System Diagnostics
 * Run in browser console to check system status
 */
window.notificationDiagnostics = {
    runQuickTest: function() {
        

        const results = {
            echo: this.testEcho(),
            dropdown: this.testDropdown(),
            sound: this.testSound(),
            system: this.testUnifiedSystem(),
            meta: this.testMetaTags(),
            bootstrap: this.testBootstrap()
        };

        
        Object.keys(results).forEach(test => {
            const result = results[test];
        });

        this.provideSuggestions(results);
        return results;
    },

    testEcho: function() {
        if (typeof window.Echo === 'undefined') {
            return { status: '❌', message: 'Echo not loaded' };
        }
        if (!window.Echo.connector) {
            return { status: '❌', message: 'Echo connector not available' };
        }
        if (window.Echo.connector.pusher && window.Echo.connector.pusher.connection.state === 'connected') {
            return { status: '✅', message: 'Echo connected and ready' };
        }
        return { status: '⚠️', message: 'Echo loaded but not fully connected' };
    },

    testDropdown: function() {
        const dropdown = document.querySelector('.notifications-dropdown');
        if (!dropdown) {
            return { status: '❌', message: 'Notification dropdown not found' };
        }

        const button = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        const menu = dropdown.querySelector('.dropdown-menu');
        const list = dropdown.querySelector('#notification-list');

        if (!button || !menu || !list) {
            return { status: '❌', message: 'Dropdown structure incomplete' };
        }

        return { status: '✅', message: 'Dropdown structure complete' };
    },

    testSound: function() {
        if (window.notificationSound && typeof window.notificationSound.play === 'function') {
            return { status: '✅', message: 'NotificationSound system available' };
        }

        try {
            const audio = new Audio('/sounds/notification.mp3');
            return { status: '⚠️', message: 'Fallback audio system available' };
        } catch (error) {
            return { status: '❌', message: 'No sound system available' };
        }
    },

    testUnifiedSystem: function() {
        if (!window.unifiedNotifications) {
            return { status: '❌', message: 'Unified notification system not initialized' };
        }

        const status = window.unifiedNotifications.getSystemStatus ?
            window.unifiedNotifications.getSystemStatus() :
            { initialized: window.unifiedNotifications.isInitialized };

        if (status.initialized) {
            return { status: '✅', message: `System initialized (User: ${status.userId})` };
        }

        return { status: '❌', message: 'System not properly initialized' };
    },

    testMetaTags: function() {
        const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!userId) {
            return { status: '❌', message: 'User ID meta tag missing' };
        }
        if (!csrfToken) {
            return { status: '❌', message: 'CSRF token meta tag missing' };
        }

        return { status: '✅', message: 'Required meta tags present' };
    },

    testBootstrap: function() {
        if (typeof bootstrap === 'undefined') {
            return { status: '❌', message: 'Bootstrap JS not loaded' };
        }

        if (typeof bootstrap.Dropdown === 'undefined') {
            return { status: '❌', message: 'Bootstrap Dropdown not available' };
        }

        return { status: '✅', message: 'Bootstrap properly loaded' };
    },

    provideSuggestions: function(results) {
        

        if (results.echo.status === '❌') {
            
            
        }

        if (results.dropdown.status === '❌') {
            
            
        }

        if (results.sound.status === '❌') {
            
            
        }

        if (results.system.status === '❌') {
            
            
        }

        if (results.meta.status === '❌') {
            
            
        }

        if (results.bootstrap.status === '❌') {
            
            
        }
    },

    testRealTimeNotification: function() {
        

        if (!window.unifiedNotifications) {
            ;
            return;
        }

        // Send test notification through the system
        window.unifiedNotifications.testNotification();

        setTimeout(() => {
            
            
            
            
            
        }, 1000);
    },

    testDropdownClick: function() {
        

        const button = document.querySelector('.notifications-dropdown [data-bs-toggle="dropdown"]');
        if (!button) {
            ;
            return;
        }

        // Trigger click event
        button.click();

        setTimeout(() => {
            const menu = document.querySelector('.notifications-dropdown .dropdown-menu');
            if (menu && menu.classList.contains('show')) {
                
            } else {
                ;
                
            }
        }, 100);
    }
};

// Add to console
