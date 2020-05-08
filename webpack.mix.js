const {
  mix
} = require("laravel-mix");
const MonacoEditorPlugin = require("monaco-editor-webpack-plugin");
const path = require("path");

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
  plugins: [
    new MonacoEditorPlugin()
  ],
  resolve: {
    modules: [
      path.resolve(__dirname, "node_modules"),
      "node_modules"
    ],
    symlinks: false,
    alias: {
      // This is so we can override some of Laravel Horizon's javascript with our own so we can embed in our UI
      Horizon: path.resolve(__dirname, "vendor/laravel/horizon/resources/assets/js/")
    }
  },
  resolveLoader: {
    modules: [
      path.resolve(__dirname, "node_modules"),
      "node_modules"
    ]
  },
  node: {fs: "empty"}
});

mix.extract([
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
  "@fortawesome/vue-fontawesome"
])
  .copy("resources/img/*", "public/img")
  .copy("node_modules/snapsvg/dist/snap.svg.js", "public/js")
  .copy("resources/js/components/CustomActions.vue", "public/js")
  .copy("resources/js/components/DetailRow.vue", "public/js")
  .copy("resources/fonts/Open_Sans/", "public/fonts")
  .copy("resources/js/components/FilterBar.vue", "public/js")
  .copy("resources/js/timeout.js", "public/js")
  // Copy files necessary for images for the designer/modeler to it's own img directory
  .copy("node_modules/@processmaker/modeler/dist/img", "public/js/processes/modeler/img")
  .copy("node_modules/@processmaker/vue-form-elements/dist", "public/js");

mix.js("resources/js/app-layout.js", "public/js")
  .js("resources/js/processes/modeler/index.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/initialLoad.js", "public/js/processes/modeler")
  .js("resources/js/admin/users/index.js", "public/js/admin/users")
  .js("resources/js/admin/users/edit.js", "public/js/admin/users/edit.js")
  .js("resources/js/admin/groups/index.js", "public/js/admin/groups")
  .js("resources/js/admin/groups/edit.js", "public/js/admin/groups/edit.js")
  .js("resources/js/admin/auth-clients/index.js", "public/js/admin/auth-clients/index.js")
  .js("resources/js/admin/queues/index.js", "public/js/admin/queues")
  .js("resources/js/admin/profile/edit.js", "public/js/admin/profile/edit.js")
  .js("resources/js/admin/cssOverride/edit.js", "public/js/admin/cssOverride/edit.js")
  .js("resources/js/admin/script-executors/index.js", "public/js/admin/script-executors/index.js")

  .js("resources/js/processes/index.js", "public/js/processes")
  .js("resources/js/processes/edit.js", "public/js/processes")
  .js("resources/js/processes/archived.js", "public/js/processes")
  .js("resources/js/processes/categories/index.js", "public/js/processes/categories")
  .js("resources/js/processes/scripts/index.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/edit.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/editConfig.js", "public/js/processes/scripts")
  .js("resources/js/processes/environment-variables/index.js", "public/js/processes/environment-variables")
  .js("resources/js/processes/screens/index.js", "public/js/processes/screens")
  .js("resources/js/processes/screens/edit.js", "public/js/processes/screens")
  .js("resources/js/processes/screen-builder/main.js", "public/js/processes/screen-builder")
  .js("resources/js/processes/screen-builder/typeForm.js", "public/js/processes/screen-builder")
  .js("resources/js/processes/screen-builder/typeDisplay.js", "public/js/processes/screen-builder")
  .js("resources/js/leave-warning.js", "public/js")
  .js("resources/js/requests/index.js", "public/js/requests")
  .js("resources/js/requests/show.js", "public/js/requests")
  .js("resources/js/requests/preview.js", "public/js/requests")

  .js("resources/js/tasks/index.js", "public/js/tasks/index.js")
  .js("resources/js/tasks/show.js", "public/js/tasks/show.js")

  .js("resources/js/notifications/index.js", "public/js/notifications/index.js")

  // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
  // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
  .js("resources/js/app.js", "public/js");

mix.sass("resources/sass/sidebar/sidebar.scss", "public/css")
  .sass("resources/sass/app.scss", "public/css")
  .sass("resources/sass/admin/queues.scss", "public/css/admin")
  .version();
