const { mix } = require('laravel-mix');

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
   .extract(['vue','jquery','bootstrap-vue','axios','popper.js','lodash','bootstrap'])
   .sass('resources/assets/sass/app.scss', 'public/css');
