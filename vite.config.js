import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        hmr: {
            host: "0.0.0.0",
            overlay: false
        },
        port: 3000,
        host: true,
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/app.scss'],
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
    base: process.env.ASSET_URL || '/',	
    // resolve: {
    //     alias: {
    //         'vue2': 'vue/dist/vue.esm-bundler.js'
    //     },
    // },
});
