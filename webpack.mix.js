const mix = require("laravel-mix");
const path = require("path");
require("laravel-mix-polyfill");
// const packageJson = require("./package.json");
const { BundleAnalyzerPlugin } = require("webpack-bundle-analyzer");

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
    new BundleAnalyzerPlugin({
      analyzerMode: process.env.STATS ? "server" : "disabled",
    }),
  ],
  externals: ["SharedComponents", "ModelerInspector"],
  resolve: {
    extensions: [".*", ".js", ".ts", ".mjs", ".vue", ".json"],
    symlinks: false,
    alias: {
      "vue-monaco": path.resolve(__dirname, "resources/js/vue-monaco-amd.js"),
      styles: path.resolve(__dirname, "resources/sass"),
    },
  },
  module: {
    rules: [
      {
        test: /\.ya?ml$/,
        use: "js-yaml-loader",
      },
    ],
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
    "jquery",
    "bootstrap-vue",
    "popper.js",
    "bootstrap",
  ], "public/js/bootstrap-vendor.js")
  .extract([
    "@fortawesome/fontawesome-free",
    "@fortawesome/fontawesome-svg-core",
    "@fortawesome/free-brands-svg-icons",
    "@fortawesome/free-solid-svg-icons",
    "@fortawesome/vue-fontawesome",
  ], "public/js/fortawesome-vendor.js")
  .extract([
    "jointjs",
    "luxon",
    "bpmn-moddle",
    "@processmaker/modeler",
  ], "public/js/modeler-vendor.js")
  .extract([
    "vue",
    "vue-router",
    "axios",
    "lodash",
  ], "public/js/vue-vendor.js");

mix
  .js("resources/js/print-layout.js", "public/js")
  .js("resources/js/app-layout.js", "public/js")
  .js("resources/js/process-map-layout.js", "public/js")
  .js("resources/js/processes/modeler/index.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/process-map.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/initialLoad.js", "public/js/processes/modeler")
  .js("resources/js/admin/auth/passwords/change.js", "public/js/admin/auth/passwords/change.js")
  // .js("resources/js/admin/queues/index.js", "public/js/admin/queues")

  .js("resources/js/processes/scripts/edit.js", "public/js/processes/scripts")
  .js("resources/js/processes/scripts/preview.js", "public/js/processes/scripts")
  .js("resources/js/processes/screens/preview.js", "public/js/processes/screens")

  .js("resources/js/requests/mobile.js", "public/js/requests/mobile.js")
  .js("resources/js/requests/show.js", "public/js/requests")
  .js("resources/js/requests/preview.js", "public/js/requests")
  .js("resources/js/processes/translations/import.js", "public/js/processes/translations")

  .js("resources/js/tasks/mobile.js", "public/js/tasks/mobile.js")
  .js("resources/js/tasks/router.js", "public/js/tasks/router.js")

  // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
  // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
  .js("resources/js/initialLoad.js", "public/js")

  .js("resources/js/tasks/loaderMain.js", "public/js/tasks")
  .js("resources/js/tasks/loaderPreview.js", "public/js/tasks")
  .js("resources/js/tasks/preview.js", "public/js/tasks/preview.js")

  .js("resources/js/app.js", "public/js");
// .polyfill({
//   enabled: true,
//   useBuiltIns: false,
//   targets: "> 0.25%, not dead"
// });

// sidebar.scss, collapseDetails.scss, app.scss, tailwind.css → moved to vite.config.js
// queues.scss stays here: CompileUI.php compiles it at runtime to public/css/admin/queues.css
mix
  .sass("resources/sass/admin/queues.scss", "public/css/admin")
  .version();

mix.vue({ version: 2 });
