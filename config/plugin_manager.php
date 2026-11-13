<?php

return [
    /*
    | After install, uninstall, reinstall, or toggle, PluginManager runs
    | supervisorctl restart so Horizon and other workers load new plugin code.
    */
    'supervisorctl_bin' => env('PLUGIN_SUPERVISORCTL_BIN', 'supervisorctl'),

    /*
    | Optional supervisord config (-c). Leave empty on macOS after running
    | dev/supervisor/link-homebrew-default.sh so `supervisorctl restart all` works.
    | On Linux servers, set to e.g. /etc/supervisord.conf.
    */
    'supervisorctl_config' => env('PLUGIN_SUPERVISORCTL_CONFIG'),

    /*
    | supervisorctl restart target: "all" or a program name (e.g. processmaker-horizon).
    */
    'supervisor_restart_target' => env('PLUGIN_SUPERVISOR_RESTART_TARGET', 'all'),

    /*
    | Allow install() to treat repo argument as a local directory (CLI operator use).
    | Keep false in production; API install always requires https URLs regardless.
    */
    'allow_local_install_path' => env('PLUGIN_INSTALL_ALLOW_LOCAL_PATH', false),

    /*
    | When MULTITENANCY is true, publish plugin public assets onto the tenant
    | public disk (storage/tenant_{id}/app/public/vendor/...). URLs stay
    | /storage/vendor/... and are served by the storage/{path} route.
    */
    'tenant_plugin_assets_enabled' => env('PLUGIN_TENANT_ASSETS_ENABLED', true),
];
