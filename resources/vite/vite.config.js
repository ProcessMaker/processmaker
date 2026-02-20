import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel([
            '../../resources/js/admin/script-executors/index.js',
        ], {
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
    build: {
        // Output to the root public/build directory
        outDir: 'public/vite-builds',
    },
    root: path.resolve(__dirname, '../../'),
    resolve: {
        alias: {
            // Force Vite to use Vue from this folder's node_modules, not the root
            // 'vue': path.resolve(__dirname, 'node_modules/vue'),
            'vue': path.resolve(__dirname, 'node_modules/@vue/compat'),
            'i18next-vue': path.resolve(__dirname, 'node_modules/i18next-vue'),
        },
        // Prefer resolving from this directory's node_modules
        dedupe: ['vue'],
        extensions: ['.vue'],
    },
});