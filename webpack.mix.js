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
  ], "public/js/vue-vendor.js")
  .copy("resources/js/timeout.js", "public/js");

mix
  .js("resources/js/print-layout.js", "public/js")
  .js("resources/js/app-layout.js", "public/js")
  .js("resources/js/process-map-layout.js", "public/js")
  .js("resources/js/processes/modeler/process-map.js", "public/js/processes/modeler")
  .js("resources/js/processes/modeler/initialLoad.js", "public/js/processes/modeler")

  .js("resources/js/processes/scripts/preview.js", "public/js/processes/scripts")
  .js("resources/js/processes/screens/preview.js", "public/js/processes/screens")

  // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
  // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
  .js("resources/js/initialLoad.js", "public/js")

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
  .sass("resources/sass/sidebar/sidebar.scss", "public/css")
  .sass("resources/sass/collapseDetails.scss", "public/css")
  .sass("resources/sass/app.scss", "public/css")
  .postCss("resources/sass/tailwind.css", "public/css", [
    require("tailwindcss"),
  ])
  .version();

mix.vue({ version: 2 });
