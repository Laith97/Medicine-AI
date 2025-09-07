<script>
console.log('🚀 Unified Notification System will auto-initialize');

@if(config('app.debug'))
// Add debug commands to window for testing
window.testNotifications = () => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.testNotification();
        console.log('📤 Test notification sent');
    } else {
        console.error('❌ Unified notification system not available');
    }
};

window.toggleNotificationSound = (enabled) => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.enableSound(enabled);
        console.log('🔊 Notification sound ' + (enabled ? 'enabled' : 'disabled'));
    }
};

window.toggleNotificationToast = (enabled) => {
    if (window.unifiedNotifications) {
        window.unifiedNotifications.enableToast(enabled);
        console.log('📋 Notification toast ' + (enabled ? 'enabled' : 'disabled'));
    }
};

// Additional debug info
setTimeout(() => {
    console.log('🧪 System Status:');
    console.log('  • Echo available:', typeof window.Echo !== 'undefined');
    console.log('  • NotificationSound available:', typeof window.notificationSound !== 'undefined');
    console.log('  • UnifiedNotifications available:', typeof window.unifiedNotifications !== 'undefined');
    console.log('  • User ID:', document.querySelector('meta[name="user-id"]')?.getAttribute('content'));
}, 3000);

console.log('🛠️ Debug commands available:');
console.log('  • testNotifications() - Send a test notification');
console.log('  • toggleNotificationSound(true/false) - Enable/disable sounds');
console.log('  • toggleNotificationToast(true/false) - Enable/disable toasts');
@endif
</script>
