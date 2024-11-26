<?php

namespace ProcessMaker\Http\Middleware\Etag;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\Caching\EtagManager;
use Symfony\Component\HttpFoundation\Response;

class IfNoneMatch
{
    public string $middleware = 'etag.if-none-match';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle request.
        $method = $request->getMethod();

        // Support using HEAD method for checking If-None-Match.
        if ($request->isMethod('HEAD')) {
            $request->setMethod('GET');
        }

        //Handle response.
        $response = $next($request);

        // If the response is not modified, return it.
        if ($response->isNotModified($request)) {
            return $response;
        }

        // Get the ETag value.
        $etag = EtagManager::getEtag($request, $response);

        // Check if the ETag matches the If-None-Match header.
        $noneMatch = array_map('trim', $request->getETags());
        if (in_array($etag, $noneMatch)) {
            $response->setNotModified();
        }

        $request->setMethod($method);

        return $response;
    }
}
