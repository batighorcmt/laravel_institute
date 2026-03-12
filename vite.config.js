import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        proxy: {
            // Proxy font requests in dev to the Laravel app (127.0.0.1:8000)
            '/fonts': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
            },
            // Proxy build asset requests (so /build/assets/* served by Laravel during dev)
            '/build/assets': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
            },
        },
    },
});
