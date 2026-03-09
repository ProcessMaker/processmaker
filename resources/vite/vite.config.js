import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel([
            '../../resources/js/admin/script-executors/index.js',
            '../../resources/js/admin/plugins/index.js',
        ], {
        }),
        vue({
            template: {
                compilerOptions: {
                    whitespace: 'condense',
                },
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
    build: {
        outDir: 'public/vite-builds',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', 'i18next-vue'],
                },
            },
        },
    },
    root: path.resolve(__dirname, '../../'),
    resolve: {
        alias: {
            // Force Vite to use Vue from this folder's node_modules, not the root
            // 'vue': path.resolve(__dirname, 'node_modules/vue'),
            'vue': path.resolve(__dirname, 'node_modules/@vue/compat'),
            'i18next-vue': path.resolve(__dirname, 'node_modules/i18next-vue'),
            'bootstrap-vue-next': path.resolve(__dirname, 'node_modules/bootstrap-vue-next'),
        },
        // Prefer resolving from this directory's node_modules
        dedupe: ['vue'],
        extensions: ['.vue'],
    },
});