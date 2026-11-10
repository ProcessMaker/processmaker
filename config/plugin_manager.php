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
    | Writable HOME and npm cache for plugin :install (npm in package postinstall).
    | CI/pods without /home/nginx: e.g. PLUGIN_NPM_HOME=/tmp, PLUGIN_NPM_CACHE=/tmp/.npm
    */
    'npm_home' => env('PLUGIN_NPM_HOME'),
    'npm_cache' => env('PLUGIN_NPM_CACHE'),
];
