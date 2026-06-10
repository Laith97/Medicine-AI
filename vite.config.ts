import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig(({ mode }) => {
    // Load all env files in order: .env, .env.local, .env.{mode}, .env.{mode}.local
    const envDir = path.resolve(__dirname, '.');
    const env = loadEnv(mode, envDir, '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/css/dashboard-enhancements.css',
                    'resources/js/app.js',
                    'resources/js/dashboard-enhancements.js',
                    'resources/js/analytics-dashboard/main.tsx',
                    'resources/js/voice-assistant-main.jsx'
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
        envDir: envDir,
        envPrefix: 'VITE_',
    };
});
