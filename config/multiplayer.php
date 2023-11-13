<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default multiplayer broadcaster that will be used by the
    | framework when an event needs to enambe the multiplayer mode. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "socketio", "null"
    |
    */

    'default' => env('VUE_APP_WEBSOCKET_PROVIDER', 'null'),
    'url' => env('VUE_APP_WEBSOCKET_PROVIDER_URL', 'null'),
    'enabled' => env('VUE_APP_COLLABORATIVE_ENABLED', 'true'),
];
