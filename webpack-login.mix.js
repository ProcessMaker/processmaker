const mix = require("laravel-mix");
const path = require("path");
const fs = require("fs");
const crypto = require("crypto");
const webpack = require("webpack");

const manifestPath = path.resolve(__dirname, "public/mix-manifest.json");
const loginVendorPublicPath = "/builds/login/js/vendor.js";
const loginVendorFilePath = path.resolve(__dirname, `public${loginVendorPublicPath}`);
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
  plugins: [
    new webpack.DefinePlugin({
      __VUE_OPTIONS_API__: JSON.stringify(true),
      __VUE_PROD_DEVTOOLS__: JSON.stringify(false),
      __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
    }),
  ],
  externals: [],
  resolve: {
    extensions: [".*", ".js", ".ts", ".mjs", ".vue", ".json"],
    symlinks: false,
    alias: {
      vue$: path.resolve(__dirname, "node_modules/@vue/compat/dist/vue.esm-bundler.js"),
      styles: path.resolve(__dirname, "resources/sass"),
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
  .js("resources/js/translations/index.js", "public/js/translations")
  .js("resources/js/app-login.js", "public/builds/login/js");

mix
  .sass("resources/sass/sidebar/sidebar.scss", "public/css")
  .sass("resources/sass/app.scss", "public/css")
  .sass("resources/sass/admin/queues.scss", "public/css/admin")
  .version();

mix.vue({
  version: 3,
  options: {
    compilerOptions: {
      compatConfig: {
        MODE: 2,
        COMPILER_V_FOR_TEMPLATE_KEY_PLACEMENT: true,
        COMPILER_V_IF_V_FOR_PRECEDENCE: true,
        COMPILER_NATIVE_TEMPLATE: true,
      },
    },
  },
});

function injectVueLoaderCompatConfigLogin(rules) {
  if (!rules) {
    return;
  }
  rules.forEach((rule) => {
    if (rule.oneOf) {
      injectVueLoaderCompatConfigLogin(rule.oneOf);
    }
    if (Array.isArray(rule.use)) {
      rule.use.forEach((use) => {
        const loaderPath = use.loader ? String(use.loader) : "";
        if (loaderPath.includes("vue-loader")) {
          // eslint-disable-next-line no-param-reassign
          use.options = use.options || {};
          // eslint-disable-next-line no-param-reassign
          use.options.compilerOptions = {
            ...(use.options.compilerOptions || {}),
            compatConfig: {
              MODE: 2,
              COMPILER_V_FOR_TEMPLATE_KEY_PLACEMENT: true,
              COMPILER_V_IF_V_FOR_PRECEDENCE: true,
              COMPILER_NATIVE_TEMPLATE: true,
            },
          };
        }
      });
    }
  });
}

mix.webpackConfig((webpack, webpackConfig) => {
  if (webpackConfig.module?.rules) {
    injectVueLoaderCompatConfigLogin(webpackConfig.module.rules);
  }
  return {};
})
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

    // Some Mix runs emit login vendor.js but don't add it to the manifest.
    // Ensure the key exists so Blade mix('builds/login/js/vendor.js') always resolves.
    if (!mergedContent[loginVendorPublicPath] && fs.existsSync(loginVendorFilePath)) {
      const vendorHash = crypto
        .createHash("md5")
        .update(fs.readFileSync(loginVendorFilePath))
        .digest("hex");
      mergedContent[loginVendorPublicPath] = `${loginVendorPublicPath}?id=${vendorHash}`;
    }

    // Output the result as formatted JSON
    fs.writeFileSync(manifestPath, JSON.stringify(mergedContent, null, 4));
  });
