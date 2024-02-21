import { defineConfig } from "vite";
import { resolve } from "path";
import laravel from "laravel-vite-plugin";
import ViteYaml from "@modyfi/vite-plugin-yaml";
import vue from "@vitejs/plugin-vue2";
import svgLoader from "vite-svg-loader";
import commonjs from "vite-plugin-commonjs";
import {homedir} from 'os'
import fs from "fs";

let host = 'freek.dev.test'

export default defineConfig({
    plugins: [
        laravel({
            detectTls: 'processmaker.test',
            input: [
                // 'resources/sass/admin/queues.scss',
                // 'resources/sass/app.scss',
                // 'resources/sass/sidebar/sidebar.scss',
                // 'resources/js/admin/auth-clients/index.js',
                // 'resources/js/admin/auth/passwords/change.js',
                // 'resources/js/admin/cssOverride/edit.js',
                // 'resources/js/admin/groups/edit.js',
                // 'resources/js/admin/groups/index.js',
                // 'resources/js/admin/profile/edit.js',
                // 'resources/js/admin/queues/index.js',
                // 'resources/js/admin/script-executors/index.js',
                // 'resources/js/admin/settings/index.js',
                // 'resources/js/admin/settings/ldaplogs.js',
                // 'resources/js/admin/users/edit.js',
                // 'resources/js/admin/users/index.js',
                'resources/js/app.js',
                // 'resources/js/app-layout.js',
                // 'resources/js/leave-warning.js',
                // 'resources/js/notifications/index.js',
                // 'resources/js/process-map-layout.js',
                // 'resources/js/processes-catalogue/index.js',
                // 'resources/js/processes/archived.js',
                // 'resources/js/processes/categories/index.js',
                // 'resources/js/processes/edit.js',
                // 'resources/js/processes/environment-variables/index.js',
                // 'resources/js/processes/export/index.js',
                // 'resources/js/processes/import/index.js',
                // 'resources/js/processes/index.js',
                // 'resources/js/processes/modeler/index.js',
                // 'resources/js/processes/modeler/initialLoad.js',
                // 'resources/js/processes/modeler/process-map.js',
                // 'resources/js/processes/newDesigner.js',
                // 'resources/js/processes/screen-builder/main.js',
                // 'resources/js/processes/screen-builder/typeDisplay.js',
                // 'resources/js/processes/screen-builder/typeForm.js',
                // 'resources/js/processes/screens/edit.js',
                // 'resources/js/processes/screens/index.js',
                // 'resources/js/processes/screens/preview.js',
                // 'resources/js/processes/scripts/edit.js',
                // 'resources/js/processes/scripts/editConfig.js',
                // 'resources/js/processes/scripts/index.js',
                // 'resources/js/processes/scripts/preview.js',
                // 'resources/js/processes/signals/edit.js',
                // 'resources/js/processes/signals/index.js',
                // 'resources/js/processes/translations/import.js',
                // 'resources/js/requests/index.js',
                // 'resources/js/requests/mobile.js',
                // 'resources/js/requests/preview.js',
                // 'resources/js/requests/show.js',
                // 'resources/js/tasks/index.js',
                // 'resources/js/tasks/mobile.js',
                // 'resources/js/tasks/show.js',
                // 'resources/js/templates/assets.js',
                // 'resources/js/templates/configure.js',
                // 'resources/js/templates/import/index.js',
                // 'resources/js/templates/index.js',
            ],
            refresh: ["resources/views/**"],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        svgLoader({ defaultImport: "url" }),
        commonjs(),
        ViteYaml(),
    ],
    resolve: {
        // extensions: ["*.js", "*.vue", "*.json"],
        alias: {
            "SharedComponents": resolve(__dirname, "resources/js/components/shared/index.js")
        }
    },
    build: {
        manifest: true
    },
    server: detectServerConfig(host),
});

function detectServerConfig(host) {
    let keyPath = resolve(homedir(), `.config/valet/Certificates/${host}.key`)
    let certificatePath = resolve(homedir(), `.config/valet/Certificates/${host}.crt`)

    if (!fs.existsSync(keyPath)) {
        return {}
    }

    if (!fs.existsSync(certificatePath)) {
        return {}
    }

    return {
        hmr: {host},
        host,
        https: {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certificatePath),
        },
    }
}
