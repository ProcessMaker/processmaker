<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supervisor program directory
    |--------------------------------------------------------------------------
    |
    | tenants:octane-sync writes octane.ini / octane-edge.ini /
    | octane-tenant-{id}.ini here (e.g. /etc/supervisor.d on the server).
    |
    */

    'supervisor_dir' => env('OCTANE_SUPERVISOR_DIR', '/etc/supervisor.d'),

    /*
    |--------------------------------------------------------------------------
    | Edge Caddyfile (FrankenPHP reverse proxy on the public Octane port)
    |--------------------------------------------------------------------------
    */

    'edge_caddyfile' => env('OCTANE_EDGE_CADDYFILE', base_path('Caddyfile.edge')),

    'frankenphp_binary' => env('OCTANE_FRANKENPHP_BINARY', base_path('frankenphp')),

    /*
    |--------------------------------------------------------------------------
    | Paths / process identity
    |--------------------------------------------------------------------------
    */

    'app_directory' => env('OCTANE_APP_DIRECTORY', base_path()),

    'supervisor_user' => env('OCTANE_SUPERVISOR_USER', 'nginx'),

    'php_binary' => env('OCTANE_PHP_BINARY', 'php'),

    /*
    |--------------------------------------------------------------------------
    | Ports
    |--------------------------------------------------------------------------
    |
    | nginx keeps proxy_pass http://127.0.0.1:{edge_port}.
    | Each tenant Octane listens on tenant_port_base + tenant_id
    | (admin on tenant_admin_port_base + tenant_id).
    |
    */

    'edge_host' => env('OCTANE_EDGE_HOST', '127.0.0.1'),

    'edge_port' => (int) env('OCTANE_EDGE_PORT', 8001),

    'tenant_host' => env('OCTANE_TENANT_HOST', '127.0.0.1'),

    'tenant_port_base' => (int) env('OCTANE_TENANT_PORT_BASE', 8100),

    'tenant_admin_port_base' => (int) env('OCTANE_TENANT_ADMIN_PORT_BASE', 9100),

    /*
    |--------------------------------------------------------------------------
    | Supervisorctl (optional --apply)
    |--------------------------------------------------------------------------
    */

    'supervisorctl_bin' => env('OCTANE_SUPERVISORCTL_BIN', env('PLUGIN_SUPERVISORCTL_BIN', 'supervisorctl')),

    'supervisorctl_config' => env('OCTANE_SUPERVISORCTL_CONFIG', env('PLUGIN_SUPERVISORCTL_CONFIG')),

];
