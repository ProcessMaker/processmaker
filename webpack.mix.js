const mix = require("laravel-mix");
const path = require("path");
require("laravel-mix-polyfill");
// const packageJson = require("./package.json");

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
*/

mix.webpackConfig({
  plugins: [],
  externals: ["monaco-editor", "SharedComponents", "ModelerInspector"],
  resolve: {
    extensions: [".*", ".js", ".ts", ".mjs", ".vue", ".json"],
    symlinks: false,
    alias: {
      "vue-monaco": path.resolve(__dirname, "resources/js/vue-monaco-amd.js"),
      "styles": path.resolve(__dirname, "resources/sass"),
    },
  },
});

mix.options({
  legacyNodePolyfills: false,
  terser: {
    parallel: true,
  },
});

mix
  .extract([
    "vue",
    "vue-router",
    "jquery",
    "bootstrap-vue",
    "axios",
    "popper.js",
    "lodash",
    "bootstrap",
    "jointjs",
    "luxon",
    "bpmn-moddle",
    "@fortawesome/fontawesome-free",
    "@fortawesome/fontawesome-svg-core",
    "@fortawesome/free-brands-svg-icons",
    "@fortawesome/free-solid-svg-icons",
    "@fortawesome/vue-fontawesome",
  ])
  .copy("resources/img/*", "public/img")
  .copy("resources/img/launchpad-images/*", "public/img/launchpad-images")
  .copy("resources/img/launchpad-images/icons/*", "public/img/launchpad-images/icons")
  .copy("resources/img/smartinbox-images/*", "public/img/smartinbox-images")
  .copy("resources/img/pagination-images/*", "public/img/pagination-images")
  .copy("resources/img/script_lang/*", "public/img/script_lang")
  .copy("node_modules/snapsvg/dist/snap.svg.js", "public/js")
  .copy("resources/js/components/CustomActions.vue", "public/js")
  .copy("resources/js/components/DetailRow.vue", "public/js")
  // .copy("resources/fonts/Open_Sans/", "public/fonts")
  .copy("resources/js/components/FilterBar.vue", "public/js")
  .copy("resources/js/timeout.js", "public/js")
  // Copy files necessary for images for the designer/modeler to it's own img directory
  .copy("node_modules/@processmaker/modeler/dist/img", "public/js/img")
  // .copy("node_modules/@processmaker/screen-builder/dist/img", "public/js/img")
  // .copy("node_modules/@processmaker/vue-form-elements/dist", "public/js")
  .copy("node_modules/bpmn-font/dist", "public/css/bpmn-symbols");

mix
  .js("resources/js/print-layout.js", "public/js")
  .js("resources/js/app-layout.js", "public/js")
  .js("resources/js/process-map-layout.js", "public/js")
  .js("resources/js/processes/modeler/index.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/process-map.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/initialLoad.js", "public/js/processes/modeler")
  .js("resources/js/admin/auth/passwords/change.js", "public/js/admin/auth/passwords/change.js")
  .js("resources/js/admin/settings/index.js", "public/js/admin/settings")
  .js("resources/js/admin/settings/ldaplogs.js", "public/js/admin/settings")
  .js("resources/js/admin/users/index.js", "public/js/admin/users")
  .js("resources/js/admin/users/edit.js", "public/js/admin/users/edit.js")
  .js("resources/js/admin/groups/index.js", "public/js/admin/groups")
  .js("resources/js/admin/groups/edit.js", "public/js/admin/groups/edit.js")
  .js("resources/js/admin/auth-clients/index.js", "public/js/admin/auth-clients/index.js")
  // .js("resources/js/admin/queues/index.js", "public/js/admin/queues")
  .js("resources/js/admin/profile/edit.js", "public/js/admin/profile/edit.js")
  .js("resources/js/admin/cssOverride/edit.js", "public/js/admin/cssOverride/edit.js")
  .js("resources/js/admin/script-executors/index.js", "public/js/admin/script-executors/index.js")

  .js("resources/js/processes/index.js", "public/js/processes")
  .js("resources/js/processes/edit.js", "public/js/processes")
  .js("resources/js/processes/archived.js", "public/js/processes")
  .js("resources/js/processes/newDesigner.js", "public/js/processes")
  .js("resources/js/templates/index.js", "public/js/templates")
  .js("resources/js/templates/import/index.js", "public/js/templates/import")
  .js("resources/js/templates/configure.js", "public/js/templates")
  .js("resources/js/templates/assets.js", "public/js/templates")
  .js("resources/js/processes/categories/index.js", "public/js/processes/categories")
  .js("resources/js/processes/scripts/index.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/edit.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/editConfig.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/preview.js", "public/js/processes/scripts")
  .js("resources/js/processes/export/index.js", "public/js/processes/export")
  .js("resources/js/processes/environment-variables/index.js", "public/js/processes/environment-variables")
  .js("resources/js/processes/import/index.js", "public/js/processes/import")
  .js("resources/js/processes/screens/index.js", "public/js/processes/screens")
  .js("resources/js/processes/screens/edit.js", "public/js/processes/screens")
  .js("resources/js/processes/screens/preview.js", "public/js/processes/screens")
  .js("resources/js/processes/screen-templates/myTemplates.js", "public/js/processes/screen-templates")
  .js("resources/js/processes/screen-templates/publicTemplates.js", "public/js/processes/screen-templates")
  .js("resources/js/processes/signals/index.js", "public/js/processes/signals")
  .js("resources/js/processes/signals/edit.js", "public/js/processes/signals")
  .js("resources/js/processes/screen-builder/main.js", "public/js/processes/screen-builder")
  .js("resources/js/processes/screen-builder/typeForm.js", "public/js/processes/screen-builder")
  .js("resources/js/processes/screen-builder/typeDisplay.js", "public/js/processes/screen-builder")
  .js("resources/js/leave-warning.js", "public/js")
  .js("resources/js/requests/index.js", "public/js/requests")
  .js("resources/js/requests/mobile.js", "public/js/requests/mobile.js")
  .js("resources/js/requests/show.js", "public/js/requests")
  .js("resources/js/requests/preview.js", "public/js/requests")

  .js("resources/js/processes/translations/import.js", "public/js/processes/translations")

  .js("resources/js/processes-catalogue/index.js", "public/js/processes-catalogue/index.js")

  .js("resources/js/tasks/index.js", "public/js/tasks/index.js")
  .js("resources/js/tasks/mobile.js", "public/js/tasks/mobile.js")
  .js("resources/js/tasks/show.js", "public/js/tasks/show.js")

  .js("resources/js/notifications/index.js", "public/js/notifications/index.js")
  .js('resources/js/inbox-rules/index.js', 'public/js/inbox-rules')
  .js('resources/js/inbox-rules/show.js', 'public/js/inbox-rules')

  // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
  // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
  .js("resources/js/app.js", "public/js");
  // .polyfill({
  //   enabled: true,
  //   useBuiltIns: false,
  //   targets: "> 0.25%, not dead"
  // });

// Monaco AMD modules. Copy only the files we need to make the build faster.
const monacoSource = "node_modules/monaco-editor/min/vs/";
const monacoDestination = "public/vendor/monaco-editor/min/vs/";
const monacoLanguages = ["php", "css", "lua", "javascript", "csharp", "java", "python", "r", "html", "xml", "typescript", "sql"];
const monacoFiles = [
  "loader.js",
  "editor/editor.main.js",
  "editor/editor.main.css",
  "editor/editor.main.nls.js",
  "base/browser/ui/codicons/codicon/codicon.ttf",
  "base/worker/workerMain.js",
  "base/common/worker/simpleWorker.nls.js",
];
monacoFiles.forEach((file) => {
  mix.copy(monacoSource + file, monacoDestination + file);
});
monacoLanguages.forEach((lang) => {
  const path = `basic-languages/${lang}/${lang}.js`;
  mix.copy(monacoSource + path, monacoDestination + path);
});
mix.copyDirectory(`${monacoSource}language`, `${monacoDestination}language`);

mix
  .sass("resources/sass/sidebar/sidebar.scss", "public/css")
  .sass("resources/sass/app.scss", "public/css")
  .sass("resources/sass/admin/queues.scss", "public/css/admin")
  .version();

mix.vue({ version: 2 });
