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
	    input: 
                [
                    'resources/js/app.ts', 
                    'resources/js/inertia.ts', 
                    'resources/css/app.scss',
                    'resources/js/helpers/constants.helper.ts',
                    'resources/js/helpers/image.helper.ts',
                    'resources/js/helpers/pagination.helper.ts',
                    'resources/js/helpers/roles.helper.ts',
                    'resources/js/helpers/types.helper.ts',
                    'resources/js/helpers/utils.helper.ts'
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
    ],
    base: process.env.ASSET_URL || '/build/',	
    resolve: {
        alias: {
             //'@helper': 'resources/js/helpers'
            // 'vue2': 'vue/dist/vue.esm-bundler.js'
        },
    },
});
