import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';
import { pathToFileURL } from 'url';
import yaml from '@rollup/plugin-yaml';

const stylesPath = path.resolve(__dirname, 'resources/sass');

/**
 * Resolve Webpack-style `~styles/...` imports used in Vue SFC <style lang="scss">.
 * Mirrors webpack.mix.js alias: styles -> resources/sass
 */
const stylesImporter = {
  findFileUrl(url) {
    if (url.startsWith('~styles/') || url.startsWith('styles/')) {
      const relative = url.replace(/^~?styles\//, '');
      return pathToFileURL(path.resolve(stylesPath, relative));
    }
    if (url === '~styles' || url === 'styles') {
      return pathToFileURL(stylesPath);
    }
    return null;
  },
};

/**
 * Vite runs in parallel with Laravel Mix.
 * Mix continues to own the main app assets; Vite owns isolated entrypoints under resources/{js,css}/vite.
 */
export default defineConfig({
  plugins: [
    yaml(),
    laravel({
      input: [
        'resources/js/vite/sample/app.js',
        'resources/css/vite/app.css',
        'resources/js/vite/auth/login.js',
        'resources/js/vite/tasks/loaderTasks.js',
        'resources/js/vite/tasks/tasks.js',
      ],
      refresh: [
        'resources/views/vite/**',
        'resources/js/vite/**',
        'resources/css/vite/**',
      ],
    }),
    vue({
      template: {
        transformAssetUrls: true,
        transformAssetUrlsOptions: {
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
      styles: stylesPath,
      '~styles': stylesPath,
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        importers: [stylesImporter],
      },
      sass: {
        importers: [stylesImporter],
      },
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
