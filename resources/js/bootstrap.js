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

// Debug environment variables
console.log('🔑 VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY);
console.log('🌍 VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER);

// Get CSRF token for authentication
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Get user ID for authentication
const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content') || window.userId;

// Proper Echo configuration with authentication
try {
    // 确保CSRF令牌存在
    if (!csrfToken) {
        console.error('❌ CSRF token is missing');
        // Don't return here, just log the error and continue
        console.log('⚠️ Continuing without Echo initialization due to missing CSRF token');
    } else {
        console.log('🔒 CSRF token found:', csrfToken.substring(0, 10) + '...');
    }

    console.log('🔒 CSRF token found:', csrfToken.substring(0, 10) + '...');
    console.log('👤 User ID found:', userId);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        }
    });

    console.log('✅ Echo initialized successfully');
    console.log('📡 Echo object:', window.Echo);
    console.log('🔒 CSRF token:', csrfToken ? 'Found' : 'Missing');
    console.log('👤 User ID:', userId ? 'Found' : 'Missing');
    console.log('🌐 Environment - Key:', import.meta.env.VITE_PUSHER_APP_KEY, 'Cluster:', import.meta.env.VITE_PUSHER_APP_CLUSTER);

    // Log connector details
    if (window.Echo.connector) {
        console.log('🔌 Echo connector:', window.Echo.connector);
        console.log('📡 Pusher object:', window.Echo.connector.pusher);
        console.log('🔗 Connection state:', window.Echo.connector.pusher?.connection?.state);
    } else {
        console.error('❌ Echo connector is missing');
    }
} catch (error) {
    console.error('❌ Echo initialization failed:', error);
}
