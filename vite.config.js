import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue2 from '@vitejs/plugin-vue2';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: [
                'resources/js/vite-entries/example-page.js',
            ],
            refresh: true,
            hotFile: path.resolve(__dirname, 'public/vite-hot'), // to differentiate it from laravel mix's hot file
        }),
        vue2({
            vueTemplateOptions: {
                compilerOptions: {
                    compatConfig: { MODE: 2 },
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        minify: mode === 'production',
    },
}));
