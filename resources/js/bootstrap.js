import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Echo configuration
try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || window.userId;

    if (!csrfToken) {
        console.error('❌ CSRF token is missing');
    }

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2',
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        },
        logToConsole: true,
        disableStats: true,
        error: function(error) {
            console.error('❌ Echo error:', error);
            if (error.type === 'AuthError') {
                console.log('🔄 Attempting to reconnect after auth error...');
                setTimeout(() => {
                    window.Echo.connector.connect();
                }, 1000);
            }
        }
    });

    console.log('✅ Echo initialized successfully');
} catch (error) {
    console.error('❌ Echo initialization failed:', error);
}
