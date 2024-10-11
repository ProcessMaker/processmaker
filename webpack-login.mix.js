const mix = require("laravel-mix");
const path = require("path");
const fs = require("fs");

const manifestPath = path.resolve(__dirname, "public/mix-manifest.json");
let existingContent = {};

require("laravel-mix-polyfill");

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
  externals: [],
  resolve: {
    extensions: [".*", ".js", ".ts", ".mjs", ".vue", ".json"],
    symlinks: false,
    alias: {
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
  ], "public/builds/login/js/vendor.js");

mix
  .js("resources/js/admin/auth/passwords/change.js", "public/js/admin/auth/passwords/change.js")
  .js("resources/js/translations/index.js", "public/js/translations")
  .js("resources/js/app-login.js", "public/builds/login/js");

mix
  .sass("resources/sass/sidebar/sidebar.scss", "public/css")
  .sass("resources/sass/app.scss", "public/css")
  .sass("resources/sass/admin/queues.scss", "public/css/admin")
  .version();

mix.vue({ version: 2 })
  .before(() => {
    // Check if the manifest file already exists and get the current content
    if (fs.existsSync(manifestPath)) {
      existingContent = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
    }
  })
  .then(() => {
    // Reload the generated manifest content
    const newContent = JSON.parse(fs.readFileSync(manifestPath, "utf8"));

    // Merge the existing content with the newly generated content
    const mergedContent = { ...existingContent, ...newContent };

    // Output the result as formatted JSON
    fs.writeFileSync(manifestPath, JSON.stringify(mergedContent, null, 4));
  });
