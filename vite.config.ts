import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
<<<<<<< HEAD
                'resources/js/analytics-dashboard/main.tsx',
                'resources/js/voice-assistant-main.jsx'
=======
                'resources/js/analytics-dashboard/main.tsx'
>>>>>>> origin/main
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js/analytics-dashboard',
        },
    },
});
