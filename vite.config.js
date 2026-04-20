import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

const backendProxyTarget = process.env.BACKEND_URL || 'http://127.0.0.1:8080';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5174,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: 5174,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/api': { target: backendProxyTarget, changeOrigin: true, secure: false },
            '/auth': { target: backendProxyTarget, changeOrigin: true, secure: false },
        },
    },
});
