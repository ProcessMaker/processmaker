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


 mix.js([
 'resources/assets/js/app-layout.js',
 'resources/assets/js/app.js'
 ],
 'public/js').extract([
 'jquery',
 'vue',
 'bootstrap-vue',
 'pusher-js',
 'popper.js',
 'laravel-echo',
 'fontawesome',
 'axios'
 ])
 .sass('resources/assets/sass/base.scss', 'public/css')
 .sass('resources/assets/sass/layouts-app.scss', 'public/css')
 .copy('node_modules/font-awesome/css/font-awesome.css', 'public/css');
 */
mix.js('resources/assets/js/app.js', 'public/js')
    .js('resources/assets/designer/AppDesigner.js', 'public/js')
    .extract(['vue', 'jquery', 'bootstrap-vue', 'axios', 'popper.js', 'lodash', 'bootstrap', 'imports-loader?this=>window,fix=>module.exports=0!snapsvg/dist/snap.svg.js'])
    .sass('resources/assets/sass/layouts-app.scss', 'public/css')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .copy('resources/assets/img/processmaker-login-logo.png', 'public/img')
    .copy('resources/assets/designer/img', 'public/images')
    .copy('node_modules/snapsvg/dist/snap.svg.js', 'public/js')
    .copy('resources/assets/img/processmaker-icon-white-sm.png', 'public/img')
    .copy('resources/assets/img/processmaker-logo-white-sm.png', 'public/img')
    .copy('resources/assets/img/processmaker_icon_logo-md.png', 'public/img')
    .copy('resources/assets/img/processmaker_fulllogo_white-md.png', 'public/img')
    .copy('/Users/milaendo/dev/bpm/resources/assets/js/components/inbox.vue', 'public/js')
    .copy('/Users/milaendo/dev/bpm/resources/assets/js/components/CustomActions.vue', 'public/js')
    .copy('/Users/milaendo/dev/bpm/resources/assets/js/components/DetailRow.vue', 'public/js')
    .copy('/Users/milaendo/dev/bpm/resources/assets/js/components/FilterBar.vue', 'public/js')
