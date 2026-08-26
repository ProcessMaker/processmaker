import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";
import path from "path";
import { pathToFileURL } from "url";
import yaml from "@rollup/plugin-yaml";
import { viteStaticCopy } from "vite-plugin-static-copy";

const stylesPath = path.resolve(__dirname, "resources/sass");
const nm = (...segments) => path.resolve(__dirname, "node_modules", ...segments);

// Monaco Editor static-copy targets (mirrors webpack.mix.js lines 119-138)
// dest is relative to outDir (public/build), so '../vendor/...' resolves to public/vendor/...
const monacoSrc = "node_modules/monaco-editor/min/vs";
const monacoDest = "../vendor/monaco-editor/min/vs";
const monacoFiles = [
  "loader.js",
  "editor/editor.main.js",
  "editor/editor.main.css",
  "editor/editor.main.nls.js",
  "base/browser/ui/codicons/codicon/codicon.ttf",
  "base/worker/workerMain.js",
  "base/common/worker/simpleWorker.nls.js",
];
const monacoLanguages = ["php", "css", "lua", "javascript", "csharp", "java", "python", "r", "html", "xml", "typescript", "sql"];
const monacoTargets = [
  ...monacoFiles.map((file) => ({
    src: `${monacoSrc}/${file}`,
    dest: path.join(monacoDest, path.dirname(file)).replace(/\\/g, "/"),
  })),
  ...monacoLanguages.map((lang) => ({
    src: `${monacoSrc}/basic-languages/${lang}/${lang}.js`,
    dest: `${monacoDest}/basic-languages/${lang}`,
  })),
  // mirrors mix.copyDirectory(`${monacoSource}language`, `${monacoDestination}language`)
  { src: `${monacoSrc}/language`, dest: monacoDest },
];

/**
 * @processmaker/modeler (Vue CLI lib) resolves SVG icons as:
 *   __webpack_require__.p + "img/start-event....svg"
 * Mix loads modeler-vendor from /js/, so setPublicPath sets p="/js/" and icons
 * hit public/js/img (copied in webpack.mix.js). Vite ESM has no script URL, so p
 * stays "" and relative img/... resolves against the page (/cases/img/...).
 * Rewrite to absolute /js/img/ paths Mix already publishes.
 */
const modelerPublicPathPlugin = {
  name: "processmaker-modeler-public-path",
  enforce: "pre",
  transform(code, id) {
    const normalized = id.replace(/\\/g, "/");
    if (!normalized.includes("/@processmaker/modeler/") || !normalized.includes("modeler.common")) {
      return null;
    }
    if (!code.includes("__webpack_require__.p + \"img/")) {
      return null;
    }
    return {
      code: code.replace(/__webpack_require__\.p \+ "img\//g, "\"/js/img/"),
      map: null,
    };
  },
};

/**
 * Resolve Webpack-style `~styles/...` imports used in Vue SFC <style lang="scss">.
 * Mirrors webpack.mix.js alias: styles -> resources/sass
 */
const stylesImporter = {
  findFileUrl(url) {
    if (url.startsWith("~styles/") || url.startsWith("styles/")) {
      const relative = url.replace(/^~?styles\//, "");
      return pathToFileURL(path.resolve(stylesPath, relative));
    }
    if (url === "~styles" || url === "styles") {
      return pathToFileURL(stylesPath);
    }
    return null;
  },
};

/**
 * vue-monaco's ESM build (dist/vue-monaco.es.js) has a CJS fallback:
 *   var monaco = require('monaco-editor');
 * that fires when `amdRequire` prop is falsy. In browser ESM context there is
 * no global `require`, so we stub it out here. The real loading is always done
 * via the `amdRequire` prop (set to `window.require` in vue-monaco-amd.js).
 */
const vueMonacoRequireShim = {
  name: "pm:vue-monaco-require-shim",
  transform(code, id) {
    if (!id.includes("node_modules/vue-monaco")) return null;
    if (!code.includes("require('monaco-editor')")) return null;
    // Replace the CJS require with AMD window.require (synchronous single-arg form,
    // works if vs/editor/editor.main is already registered by loader.js).
    // This branch is only reached when amdRequire prop is falsy; with shared.monaco
    // loaded, window.require is always truthy so this is a safety net.
    return {
      code: code.replace(
        "require('monaco-editor')",
        "(window.require && window.require('monaco-editor'))",
      ),
      map: null,
    };
  },
};

/**
 * Tailwind's `content` globs make every SFC a PostCSS dependency of
 * tailwind.css, so Vite registers an extra `type: 'asset'` node under each
 * SFC's path. plugin-vue2 treats the first node without a `type=` query as the
 * SFC's main module and picks that asset node, sending the hot update to
 * tailwind.css instead of the component.
 */
const vue2HmrMainModuleFix = {
  name: "pm:vue2-hmr-main-module-fix",
  enforce: "post",
  hotUpdate({ file, modules }) {
    if (!file.endsWith(".vue") || !modules.some((mod) => mod.type === "asset")) {
      return;
    }

    const candidates = this.environment.moduleGraph.getModulesByFile(file) ?? [];
    const mainModule = [...candidates].find(
      (mod) => mod.type !== "asset" && !/type=/.test(mod.url),
    );

    return mainModule ? [...modules, mainModule] : undefined;
  },
};

/**
 * Vite runs in parallel with Laravel Mix.
 * Mix continues to own public/css/* for legacy layouts; Vite compiles the same
 * SCSS/CSS sources for Vite pages via @vite([...]).
 */
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  const appUrl = (env.APP_URL || "http://127.0.0.1:8092").replace(/\/$/, "");

  // Fonts live in Laravel public/. CSS from vite:dev is served from :5173, so
  // root-relative /css/... would hit Vite unless proxied. Absolute APP_URL fonts
  // break when the page origin differs (localhost vs LAN IP) — browser CORS.
  // Always use root-relative paths; proxy /css to Laravel during serve.
  const openSansPath = "/css/precompiled/npm-font-open-sans/fonts";
  const faPath = "/css/precompiled/fontawesome-free/webfonts";

  const fontAdditionalData = `
    $FontPathOpenSans: "${openSansPath}";
    $fa-font-path: "${faPath}";
  `;

  return {
    plugins: [
      modelerPublicPathPlugin,
      yaml(),
      laravel({
        // Keep Vite hot file away from public/hot so Laravel Mix's mix() helper
        // does not rewrite Mix assets (typeForm.js, etc.) to the Vite origin.
        hotFile: "storage/vite.hot",
        input: [
          "resources/js/vite/auth/login.js",
          "resources/js/translations/index.js",
          "resources/js/vite/tasks/loaderTasks.js",
          "resources/js/vite/tasks/tasks.js",

          "resources/js/tasks/loaderEdit.js",
          "resources/js/tasks/edit.js",
          "resources/js/tasks/loaderPreview.js",
          "resources/js/tasks/loaderOnlyPreview.js",
          "resources/js/tasks/preview.js",
          "resources/js/tasks/mobile.js",

          "resources/js/processes/screen-builder/loaderScreen.js",
          "resources/js/processes/screen-builder/typeForm.js",
          "resources/js/processes/screen-builder/typeDisplay.js",
          "resources/js/processes/screen-builder/main.js",

          "resources/js/notifications/loaderNotifications.js",
          "resources/js/notifications/index.js",

          "resources/js/templates/loaderTemplates.js",
          "resources/js/templates/import/index.js",

          "resources/js/tasks/loaderTasks.js",
          "resources/js/tasks/show.js",
          "resources/js/inbox-rules/index.js",

          "resources/js/processes/loaderProcesses.js",

          "resources/js/leave-warning.js",
          "resources/js/processes/processes.js",
          "resources/js/processes/edit.js",
          "resources/js/processes/newDesigner.js",
          "resources/js/processes/export/index.js",
          "resources/js/processes/import/index.js",
          "resources/js/templates/index.js",
          "resources/js/processes/categories/index.js",
          "resources/js/processes/archived.js",
          "resources/js/admin/loaderAdmin.js",
          "resources/js/admin/users/loaderUsers.js",
          "resources/js/admin/users/index.js",
          "resources/js/admin/users/edit.js",
          "resources/js/admin/groups/loaderGroups.js",
          "resources/js/admin/groups/index.js",
          "resources/js/admin/groups/edit.js",
          "resources/js/admin/auth-clients/loaderAuthClients.js",
          "resources/js/admin/auth-clients/index.js",
          "resources/js/admin/settings/loaderSettings.js",
          "resources/js/admin/settings/index.js",
          "resources/js/admin/settings/ldaplogs.js",
          "resources/js/admin/cssOverride/loaderCssOverride.js",
          "resources/js/admin/cssOverride/edit.js",
          "resources/js/admin/script-executors/index.js",
          "resources/js/admin/tenant-queues/index.js",
          "resources/js/admin/devlink/index.js",
          "resources/js/admin/cases-retention/index.js",
          "resources/js/admin/logs/index.js",
          "resources/js/admin/auth/passwords/change.js",
          "resources/js/vite/auth/auth.js",

          "resources/js/admin/profile/loaderProfile.js",
          "resources/js/admin/profile/edit.js",

          "resources/js/processes/environment-variables/loaderEnvironment.js",
          "resources/js/processes/environment-variables/index.js",
          "resources/js/processes/environment-variables/edit.js",
          "resources/js/processes/screens/loaderScreens.js",
          "resources/js/processes/screens/index.js",
          "resources/js/processes/screen-templates/myTemplates.js",
          "resources/js/processes/screen-templates/publicTemplates.js",
          "resources/js/processes/screens/edit.js",
          "resources/js/processes/scripts/loaderScripts.js",
          "resources/js/processes/scripts/index.js",
          "resources/js/processes/scripts/editConfig.js",
          "resources/js/processes/scripts/edit.js",

          "resources/js/processes/scripts/loaderScriptsPreview.js",
          "resources/js/processes/scripts/preview.js",

          "resources/js/processes/modeler/loaderModeler.js",
          "resources/js/processes/modeler/index.js",
          "resources/js/processes/modeler/initialLoad.js",
          "resources/js/processes/modeler/loaderInflight.js",
          "resources/js/processes/modeler/process-map.js",
          "resources/js/process-map-layout.js",

          "resources/js/requests/loaderRequests.js",
          "resources/js/requests/loaderRequestsShow.js",
          "resources/js/requests/loaderRequestsPreview.js",
          "resources/js/requests/mobile.js",
          "resources/js/requests/index.js",

          "resources/js/notifications/loaderNotifications.js",
          "resources/js/notifications/index.js",

          "resources/js/templates/loaderTemplates.js",
          "resources/js/templates/import/index.js",
          "resources/js/templates/configure.js",
          "resources/js/templates/assets.js",

          "resources/js/processes/signals/loaderSignals.js",
          "resources/js/processes/signals/index.js",
          "resources/js/processes/signals/edit.js",

          "resources/js/processes-catalogue/loaderProcessesCatalogue.js",
          "resources/js/processes-catalogue/processesCatalogue.js",
          "resources/jscomposition/cases/casesMain/loaderCasesMain.js",
          "resources/jscomposition/cases/casesMain/casesMain.js",
          "resources/jscomposition/cases/casesDetail/loaderCasesDetail.js",
          "resources/jscomposition/cases/casesDetail/casesDetail.js",
          "resources/js/initialLoad.js",
          // Same style entrypoints as webpack.mix.js
          "resources/sass/app.scss",
          "resources/sass/sidebar/sidebar.scss",
          "resources/sass/collapseDetails.scss",
          "resources/sass/tailwind.css",
        ],
        refresh: [
          "resources/views/**",
          "resources/sass/**",
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
      vue2HmrMainModuleFix,
      vueMonacoRequireShim,
      // dest paths are relative to outDir (public/build), so '../' escapes to public/
      viteStaticCopy({
        targets: [
          // Static images (mirrors webpack.mix.js .copy() calls)
          { src: "resources/img/*", dest: "../img" },
          { src: "resources/img/launchpad-images/*", dest: "../img/launchpad-images" },
          { src: "resources/img/launchpad-images/icons/*", dest: "../img/launchpad-images/icons" },
          { src: "resources/img/connected-account-images/*", dest: "../img/connected-account-images" },
          { src: "resources/img/smartinbox-images/*", dest: "../img/smartinbox-images" },
          { src: "resources/img/pagination-images/*", dest: "../img/pagination-images" },
          { src: "resources/img/script_lang/*", dest: "../img/script_lang" },
          // Vendor JS / Vue files served as plain scripts by Mix pages
          { src: "node_modules/snapsvg/dist/snap.svg.js", dest: "../js" },
          { src: "resources/js/components/CustomActions.vue", dest: "../js" },
          { src: "resources/js/components/DetailRow.vue", dest: "../js" },
          { src: "resources/js/components/FilterBar.vue", dest: "../js" },
          { src: "resources/js/timeout.js", dest: "../js" },
          // Modeler SVG icons (same target as Mix; referenced by modeler's __webpack_require__.p)
          { src: "node_modules/@processmaker/modeler/dist/img", dest: "../js/img" },
          // BPMN font symbols
          { src: "node_modules/bpmn-font/dist", dest: "../css/bpmn-symbols" },
          // Monaco Editor (mirrors webpack.mix.js lines 119-138)
          ...monacoTargets,
        ],
      }),
    ],
    resolve: {
      extensions: [".mjs", ".js", ".ts", ".jsx", ".tsx", ".json", ".vue"],
      dedupe: ["vue"],
      alias: {
        vue: nm("vue/dist/vue.esm.js"),
        // Bare peers imported by CI-linked screen-builder ESM
        // (/opt/packages/screen-builder/dist/vue-form-builder.es.js)
        "@processmaker/vue-form-elements": nm("@processmaker/vue-form-elements"),
        lodash: nm("lodash"),
        vuex: nm("vuex"),
        moment: nm("moment"),
        "moment-timezone": nm("moment-timezone"),
        "vue-monaco": path.resolve(__dirname, "resources/js/vue-monaco-amd.js"),
        styles: stylesPath,
        "~styles": stylesPath,
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
    // Let Vite transform modeler.common.js (plugin above) instead of esbuild prebundle.
    optimizeDeps: {
      exclude: ["@processmaker/modeler"],
    },
    build: {
      outDir: "public/build",
      emptyOutDir: true,
      manifest: "manifest.json",
    },
    server: {
      host: "127.0.0.1",
      port: 5173,
      strictPort: true,
      origin: "http://127.0.0.1:5173",
      cors: {
        origin: true,
      },
      proxy: {
        "/css": {
          target: appUrl,
          changeOrigin: true,
        },
      },
      hmr: {
        host: "127.0.0.1",
        protocol: "ws",
      },
    },
  };
});
