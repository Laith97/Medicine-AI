/**
 * Quick Notification System Diagnostics
 * Run in browser console to check system status
 */
window.notificationDiagnostics = {
    runQuickTest: function() {
        console.log('🔍 Running Notification System Diagnostics...\n');

        const results = {
            echo: this.testEcho(),
            dropdown: this.testDropdown(),
            sound: this.testSound(),
            system: this.testUnifiedSystem(),
            meta: this.testMetaTags(),
            bootstrap: this.testBootstrap()
        };

        console.log('\n📊 DIAGNOSTIC RESULTS:');
        Object.keys(results).forEach(test => {
            const result = results[test];
            console.log(`${result.status} ${test.toUpperCase()}: ${result.message}`);
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
        console.log('\n💡 SUGGESTIONS:');

        if (results.echo.status === '❌') {
            console.log('• Check if Laravel Echo and Pusher are properly configured');
            console.log('• Verify .env PUSHER_* settings');
        }

        if (results.dropdown.status === '❌') {
            console.log('• Check if notification dropdown HTML is present in master.blade.php');
            console.log('• Verify Bootstrap classes are correct');
        }

        if (results.sound.status === '❌') {
            console.log('• Check if /sounds/notification.mp3 file exists');
            console.log('• Verify browser audio permissions');
        }

        if (results.system.status === '❌') {
            console.log('• Check if unified-notifications-v2.js is loaded');
            console.log('• Verify user authentication and meta tags');
        }

        if (results.meta.status === '❌') {
            console.log('• Check if user is authenticated');
            console.log('• Verify CSRF token is generated');
        }

        if (results.bootstrap.status === '❌') {
            console.log('• Check if Bootstrap 5 JS is properly loaded');
            console.log('• Verify no conflicts with other JS libraries');
        }
    },

    testRealTimeNotification: function() {
        console.log('🧪 Testing real-time notification...');

        if (!window.unifiedNotifications) {
            console.error('❌ Unified notification system not available');
            return;
        }

        // Send test notification through the system
        window.unifiedNotifications.testNotification();

        setTimeout(() => {
            console.log('🔍 Check if you saw:');
            console.log('  • Toast notification appeared');
            console.log('  • Sound played');
            console.log('  • Dropdown updated');
            console.log('  • Badge count increased');
        }, 1000);
    },

    testDropdownClick: function() {
        console.log('🧪 Testing dropdown click...');

        const button = document.querySelector('.notifications-dropdown [data-bs-toggle="dropdown"]');
        if (!button) {
            console.error('❌ Dropdown button not found');
            return;
        }

        // Trigger click event
        button.click();

        setTimeout(() => {
            const menu = document.querySelector('.notifications-dropdown .dropdown-menu');
            if (menu && menu.classList.contains('show')) {
                console.log('✅ Dropdown opened successfully');
            } else {
                console.error('❌ Dropdown failed to open');
                console.log('💡 Try checking Bootstrap JS loading');
            }
        }, 100);
    }
};

// Add to console
console.log('🔧 Notification Diagnostics loaded!');
console.log('📋 Available commands:');
console.log('  • notificationDiagnostics.runQuickTest() - Full system check');
console.log('  • notificationDiagnostics.testRealTimeNotification() - Test notification');
console.log('  • notificationDiagnostics.testDropdownClick() - Test dropdown');
