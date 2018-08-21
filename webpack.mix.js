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
    .js('resources/assets/js/management/users/index.js', 'public/js/management/users')
    .js('resources/assets/js/management/profile/index.js', 'public/js/management/profile')
    .js('resources/assets/js/management/roles/index.js', 'public/js/management/roles')
    .js('resources/assets/js/management/groups/index.js', 'public/js/management/groups')
    .js('resources/assets/js/processes/tasks/index.js', 'public/js/processes/tasks')
    .js('resources/assets/js/processes/index.js', 'public/js/processes')
    .js('resources/assets/js/processes/categories/index.js', 'public/js/processes/categories')
    .js('resources/assets/js/requests/index.js', 'public/js/requests')

    .js('resources/assets/js/nayra/start.js', 'public/js/nayra')

    .js('resources/assets/js/request/status.js', 'public/js/request')
    .js('resources/assets/js/tasks/index.js', 'public/js/tasks/index.js')
    .js('resources/assets/js/tasks/show.js', 'public/js/tasks/show.js')
    .js('resources/assets/js/designer/formBuilder/main.js', 'public/js/formBuilder')





    // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
    // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
    .js('resources/assets/js/app.js', 'public/js')

    .extract(['vue', 'jquery', 'bootstrap-vue', 'axios', 'popper.js', 'lodash', 'bootstrap', 'imports-loader?this=>window,fix=>module.exports=0!snapsvg/dist/snap.svg.js'])
    .copy('resources/assets/img/*', 'public/img')
    .sass('resources/assets/sass/sidebar/sidebar.scss', 'public/css')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .copy('resources/assets/js/designer/skins', 'public/js/designer/skins')
    .copy('resources/assets/js/designer/plugins', 'public/js/designer/plugins')
    .copy('node_modules/snapsvg/dist/snap.svg.js', 'public/js')
    .copy('resources/assets/js/components/inbox.vue', 'public/js')
    .copy('resources/assets/js/components/CustomActions.vue', 'public/js')
    .copy('resources/assets/js/components/DetailRow.vue', 'public/js')
    .copy('resources/assets/js/components/FilterBar.vue', 'public/js')

    .version()
