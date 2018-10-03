const {
    mix
} = require('laravel-mix');
const MonocoEditorPlugin = require('monaco-editor-webpack-plugin')

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
            new MonocoEditorPlugin()
        ],
        resolve: {
            alias: {
                Horizon: path.resolve(__dirname, 'vendor/laravel/horizon/resources/js/')
            }
        }
    }).js('resources/js/app-layout.js', 'public/js')
    .js('resources/js/designer/main.js', 'public/js/designer')
    .js('resources/js/management/users/index.js', 'public/js/management/users')
    .js('resources/js/management/environment-variables/index.js', 'public/js/management/environment-variables')
    .js('resources/js/management/profile/index.js', 'public/js/management/profile')
    .js('resources/js/management/roles/index.js', 'public/js/management/roles')
    .js('resources/js/management/groups/index.js', 'public/js/management/groups')
    .js('resources/js/management/queues/index.js', 'public/js/management/queues')

    .js('resources/js/management/preferences/index.js', 'public/js/management/preferences')
    .js('resources/js/processes/tasks/index.js', 'public/js/processes/tasks')
    .js('resources/js/processes/index.js', 'public/js/processes')
    .js('resources/js/processes/categories/index.js', 'public/js/processes/categories')
    .js('resources/js/requests/index.js', 'public/js/requests')

    .js('resources/js/nayra/start.js', 'public/js/nayra')

    .js('resources/js/request/status.js', 'public/js/request')
    .js('resources/js/tasks/index.js', 'public/js/tasks/index.js')
    .js('resources/js/tasks/show.js', 'public/js/tasks/show.js')
    .js('resources/js/designer/formBuilder/main.js', 'public/js/formBuilder')
    .js('resources/js/designer/ScriptEditor/main.js', 'public/js/designer/ScriptEditor')
    .js('resources/js/admin/users/index.js', 'public/js/admin/users')




    // Note, that this should go last for the extract to properly put the manifest and vendor in the right location
    // See: https://github.com/JeffreyWay/laravel-mix/issues/1118
    .js('resources/js/app.js', 'public/js')

    .extract(['vue', 'jquery', 'bootstrap-vue', 'axios', 'popper.js', 'lodash', 'bootstrap'])
    .copy('resources/img/*', 'public/img')
    .sass('resources/sass/sidebar/sidebar.scss', 'public/css')
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/management/queues.scss', 'public/css/management')
    .copy('resources/js/designer/skins', 'public/js/designer/skins')
    .copy('resources/js/designer/plugins', 'public/js/designer/plugins')
    .copy('node_modules/snapsvg/dist/snap.svg.js', 'public/js')
    .copy('resources/js/components/inbox.vue', 'public/js')
    .copy('resources/js/components/CustomActions.vue', 'public/js')
    .copy('resources/js/components/DetailRow.vue', 'public/js')
    .copy('resources/fonts/Open_Sans/', 'public/fonts')
    .copy('resources/js/components/FilterBar.vue', 'public/js')

    .version()
