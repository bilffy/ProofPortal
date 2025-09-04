import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
	target: 'esnext',
        minify: true,
        sourcemap: true,    
        esbuild: {
            keepNames: true, // Preserve variable names
        },
        rollupOptions: {
	    preserveEntrySignatures: 'exports-only',
        },
    },	    
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
                    'resources/css/app.scss',
                    'resources/js/helpers/constants.helper.ts',
                    'resources/js/helpers/image.helper.ts',
                    'resources/js/helpers/pagination.helper.ts',
                    'resources/js/helpers/roles.helper.ts',
                    'resources/js/helpers/types.helper.ts',
                    'resources/js/helpers/utils.helper.ts',
                    'resources/js/helpers/encryption.helper.ts',
                    'resources/js/helpers/session.helper.ts',
                    'resources/js/helpers/js-aes-php.ts',
                ], 
            refresh: true,
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
