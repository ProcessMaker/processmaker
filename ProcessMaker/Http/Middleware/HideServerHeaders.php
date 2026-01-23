<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HideServerHeaders
{
    /**
     * Headers that reveal server information and should be removed
     *
     * @var array
     */
    private $headersToRemove = [
        // Server identification
        'Server',
        'X-Powered-By',
        'X-AspNet-Version',
        'X-AspNetMvc-Version',

        // Web technologies and frameworks
        'X-Generator',
        'X-Drupal-Cache',
        'X-Varnish',
        'X-Cache',
        'X-Cache-Hits',
        'X-Framework',

        // Load balancer and proxy information
        'X-Forwarded-For',
        'X-Real-IP',
        'X-Forwarded-Proto',
        'X-Forwarded-Host',
        'X-Forwarded-Server',
        'X-Forwarded-Port',

        // Additional server information
        'X-Served-By',
        'X-Cache-Status',
        'X-Served-From',
        'X-Content-Source',

        // PHP specific headers
        'X-PHP-Version',
        'X-PHP-Originating-Script',

        // Development and debugging headers
        'X-Debug-Token',
        'X-Debug-Token-Link',
        'X-Symfony-Cache',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only remove headers in production or when explicitly configured
        if ($this->shouldHideHeaders()) {
            // Remove all server-revealing headers
            foreach ($this->headersToRemove as $header) {
                $response->headers->remove($header);
            }

            // Set a generic server header to avoid revealing the absence
            $response->headers->set('Server', 'ProcessMaker Server');
        }

        return $response;
    }

    /**
     * Determine if headers should be hidden based on environment
     *
     * @return bool
     */
    private function shouldHideHeaders(): bool
    {
        // Hide headers in production or when explicitly configured
        return app()->environment('production') ||
               config('app.hide_server_headers', false);
    }
}
