import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';

/**
 * Vite runs in parallel with Laravel Mix.
 * Mix continues to own the main app assets; Vite owns isolated entrypoints under resources/{js,css}/vite.
 */
export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/vite/sample/app.js',
        'resources/css/vite/app.css',
        'resources/js/vite/auth/login.js',
      ],
      refresh: [
        'resources/views/vite/**',
        'resources/js/vite/**',
        'resources/css/vite/**',
      ],
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
      vue: path.resolve(__dirname, 'node_modules/vue/dist/vue.esm.js'),
      '@vite': path.resolve(__dirname, 'resources/js/vite'),
    },
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: 'manifest.json',
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
  },
});
