import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';
import { pathToFileURL } from 'url';
import yaml from '@rollup/plugin-yaml';

const stylesPath = path.resolve(__dirname, 'resources/sass');
const nm = (...segments) => path.resolve(__dirname, 'node_modules', ...segments);

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
 * Mix continues to own public/css/* for legacy layouts; Vite compiles the same
 * SCSS/CSS sources for Vite pages via @vite([...]).
 */
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const appUrl = (env.APP_URL || 'http://127.0.0.1:8092').replace(/\/$/, '');

  // Fonts live in Laravel public/. CSS from vite:dev is served from :5173, so
  // root-relative /css/... would hit Vite unless proxied. Absolute APP_URL fonts
  // break when the page origin differs (localhost vs LAN IP) — browser CORS.
  // Always use root-relative paths; proxy /css to Laravel during serve.
  const openSansPath = '/css/precompiled/npm-font-open-sans/fonts';
  const faPath = '/css/precompiled/fontawesome-free/webfonts';

  const fontAdditionalData = `
    $FontPathOpenSans: "${openSansPath}";
    $fa-font-path: "${faPath}";
  `;

  return {
    plugins: [
      yaml(),
      laravel({
        // Keep Vite hot file away from public/hot so Laravel Mix's mix() helper
        // does not rewrite Mix assets (typeForm.js, etc.) to the Vite origin.
        hotFile: 'storage/vite.hot',
        input: [
          'resources/js/vite/auth/login.js',
          'resources/js/translations/index.js',
          'resources/js/vite/tasks/loaderTasks.js',
          'resources/js/vite/tasks/tasks.js',
          // Same style entrypoints as webpack.mix.js
          'resources/sass/app.scss',
          'resources/sass/sidebar/sidebar.scss',
          'resources/sass/collapseDetails.scss',
          'resources/sass/tailwind.css',
        ],
        refresh: [
          'resources/views/vite/**',
          'resources/js/**/*.js',
          'resources/js/**/*.vue',
          'resources/sass/**',
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
      extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
      alias: {
        vue: nm('vue/dist/vue.esm.js'),
        // Bare peers imported by CI-linked screen-builder ESM
        // (/opt/packages/screen-builder/dist/vue-form-builder.es.js)
        '@processmaker/vue-form-elements': nm('@processmaker/vue-form-elements'),
        lodash: nm('lodash'),
        vuex: nm('vuex'),
        moment: nm('moment'),
        'moment-timezone': nm('moment-timezone'),
        'vue-monaco': nm('vue-monaco'),
        styles: stylesPath,
        '~styles': stylesPath,
      },
    },
    css: {
      preprocessorOptions: {
        scss: {
          loadPaths: [stylesPath],
          importers: [stylesImporter],
          additionalData: fontAdditionalData,
        },
        sass: {
          loadPaths: [stylesPath],
          importers: [stylesImporter],
          additionalData: fontAdditionalData,
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
      origin: 'http://127.0.0.1:5173',
      cors: {
        origin: true,
      },
      proxy: {
        '/css': {
          target: appUrl,
          changeOrigin: true,
        },
      },
      hmr: {
        host: '127.0.0.1',
        protocol: 'ws',
      },
    },
  };
});
