<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for CSP. These rules will be added
    | to the response header and allows web site administrators to control
    | resources the user agent is allowed to load for a given page.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
    |
    */

    'rules' => [
        'connect-src' => "*",
        'script-src' => "* 'unsafe-inline' 'unsafe-eval' blob:",
        'object-src' => "'self' 'unsafe-inline' blob: data:",
        'child-src' => "'self' blob:",
        'worker-src' => "'self' blob:",
    ]
];
