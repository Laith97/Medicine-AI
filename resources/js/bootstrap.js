import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * LARAVEL ECHO - Standard configuration that works
 */

import Pusher from 'pusher-js';
import Echo from 'laravel-echo';

window.Pusher = Pusher;

// Get Pusher credentials from environment
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || '57bd15962a354114cb5e';
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2';

// Initialize Laravel Echo with standard Pusher configuration
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }
});

console.log('✅ Laravel Echo initialized with Pusher');