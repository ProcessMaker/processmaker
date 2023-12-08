import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";
import ViteYaml from "@modyfi/vite-plugin-yaml";
import svgLoader from "vite-svg-loader";
import commonjs from "vite-plugin-commonjs";

export default defineConfig({
  plugins: [
    ViteYaml(),
    svgLoader({ defaultImport: "url" }),
    commonjs(),
    laravel({
      input: [
        // "resources/sass/admin/queues.scss",
        "resources/sass/app.scss",
        // "resources/sass/sidebar/sidebar.scss",
        // "resources/js/admin/auth-clients/index.js",
        // "resources/js/admin/auth/passwords/change.js",
        // "resources/js/admin/cssOverride/edit.js",
        // "resources/js/admin/groups/edit.js",
        // "resources/js/admin/groups/index.js",
        // "resources/js/admin/profile/edit.js",
        // "resources/js/admin/script-executors/index.js",
        // "resources/js/admin/settings/index.js",
        // "resources/js/admin/settings/ldaplogs.js",
        // "resources/js/admin/users/edit.js",
        // "resources/js/admin/users/index.js",
        // "resources/js/app-layout.js",
        "resources/js/app.js",
        // "resources/js/leave-warning.js",
        // "resources/js/notifications/index.js",
        // "resources/js/process-map-layout.js",
        // "resources/js/processes-catalogue/index.js",
        // "resources/js/processes-catalogue/open.js",
        // "resources/js/processes/archived.js",
        // "resources/js/processes/categories/index.js",
        // "resources/js/processes/edit.js",
        // "resources/js/processes/environment-variables/index.js",
        // "resources/js/processes/export/index.js",
        // "resources/js/processes/import/index.js",
        // "resources/js/processes/index.js",
        // "resources/js/processes/modeler/index.js",
        // "resources/js/processes/modeler/initialLoad.js",
        // "resources/js/processes/modeler/process-map.js",
        // "resources/js/processes/newDesigner.js",
        // "resources/js/processes/screen-builder/main.js",
        // "resources/js/processes/screen-builder/typeDisplay.js",
        // "resources/js/processes/screen-builder/typeForm.js",
        // "resources/js/processes/screens/edit.js",
        // "resources/js/processes/screens/index.js",
        // "resources/js/processes/screens/preview.js",
        // "resources/js/processes/scripts/edit.js",
        // "resources/js/processes/scripts/editConfig.js",
        // "resources/js/processes/scripts/index.js",
        // "resources/js/processes/scripts/preview.js",
        // "resources/js/processes/signals/edit.js",
        // "resources/js/processes/signals/index.js",
        // "resources/js/processes/translations/import.js",
        // "resources/js/requests/index.js",
        // "resources/js/requests/mobile.js",
        // "resources/js/requests/preview.js",
        // "resources/js/requests/show.js",
        // "resources/js/tasks/index.js",
        // "resources/js/tasks/mobile.js",
        // "resources/js/tasks/show.js",
        // "resources/js/templates/assets.js",
        // "resources/js/templates/configure.js",
        // "resources/js/templates/import/index.js",
        // "resources/js/templates/index.js",
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
  build: {
    manifest: true
  }
});
