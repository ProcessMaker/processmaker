import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel([
            '../../resources/js/admin/script-executors/index.js',
        ], {
            // hotFile: 'public/hottt',
            // publicDirectory: '../../public',
            // buildsDirectory: '../../public/builds',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
            // compilerOptions: {
            //     compatConfig: {
            //         MODE: 2, // Vue 2 compatibility mode
            //     },
            // },
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
            'vue-i18n': path.resolve(__dirname, 'node_modules/vue-i18n'),
        },
        // Prefer resolving from this directory's node_modules
        dedupe: ['vue'],
        extensions: ['.vue'],
    },
});