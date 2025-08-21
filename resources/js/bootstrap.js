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

// Proper Echo configuration with authentication
try {
    // 确保CSRF令牌存在
    if (!csrfToken) {
        console.error('❌ CSRF token is missing');
        // 尝试从meta标签获取
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('❌ Could not find CSRF token');
        }
    }
    
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2',
        forceTLS: false, // 在本地开发环境中禁用TLS
        wsHost: window.location.hostname,
        wsPort: 6001, // Laravel WebSocket默认端口
        wssPort: 6001,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            // 改进授权函数
            always: function (channelName, socketId) {
                console.log('🔒 Authorizing channel:', channelName);
                return {
                    'auth': csrfToken,
                    'socket_id': socketId,
                    'info': JSON.stringify({userId: window.userId || document.querySelector('meta[name="user-id"]')?.getAttribute('content')})
                };
            }
        },
        enabledTransports: ['ws', 'wss'], // 优先使用WebSocket
        disabledTransports: [], // 允许所有传输方式
        // 添加调试选项
        logToConsole: true,
        disableStats: true,
        // 禁用自动重连
        reconnect: false,
        // 添加错误处理
        error: function(error) {
            console.error('❌ Echo error:', error);
            // 如果是认证错误，尝试重新连接
            if (error.type === 'AuthError') {
                console.log('🔄 Attempting to reconnect after auth error...');
                setTimeout(() => {
                    window.Echo.connector.connect();
                }, 1000);
            }
        }
    });

    console.log('✅ Echo initialized successfully');
    console.log('📡 Echo object:', window.Echo);
    console.log('🔒 CSRF token:', csrfToken ? 'Found' : 'Missing');
    console.log('🌐 Environment - Key:', import.meta.env.VITE_PUSHER_APP_KEY, 'Cluster:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
} catch (error) {
    console.error('❌ Echo initialization failed:', error);
}
