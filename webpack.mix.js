const {mix} = require('laravel-mix');

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

mix.js('resources/assets/js/app-layout.js', 'public/js')
    .js('resources/assets/js/designer/main.js', 'public/js/designer')
    .js('resources/assets/js/management/users.js', 'public/js/management')
    // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
    // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
    .js('resources/assets/js/app.js', 'public/js')
    .extract(['vue', 'jquery', 'bootstrap-vue', 'axios', 'popper.js', 'lodash', 'bootstrap','imports-loader?this=>window,fix=>module.exports=0!snapsvg/dist/snap.svg.js'])

    .sass('resources/assets/sass/layouts-app.scss', 'public/css')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .copy('resources/assets/img/processmaker-login-logo.png', 'public/img')
    //.copy('resources/assets/designer/img', 'public/images')
    .copy('resources/assets/js/designer/skins', 'public/js/designer/skins')
    .copy('resources/assets/js/designer/plugins', 'public/js/designer/plugins')
    .copy('node_modules/snapsvg/dist/snap.svg.js', 'public/js')
    .copy('resources/assets/img/processmaker-icon-white-sm.png', 'public/img')
    .copy('resources/assets/img/processmaker-logo-white-sm.png', 'public/img')
    .copy('resources/assets/img/processmaker_icon_logo-md.png', 'public/img')
    .copy('resources/assets/img/processmaker_fulllogo_white-md.png', 'public/img')
    .copy('resources/assets/js/components/inbox.vue', 'public/js')
    .copy('resources/assets/js/components/CustomActions.vue', 'public/js')
    .copy('resources/assets/js/components/DetailRow.vue', 'public/js')
    .copy('resources/assets/js/components/FilterBar.vue', 'public/js')
    .copy('resources/assets/img/favicon.ico', 'public/img')
    .version()
