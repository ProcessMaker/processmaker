<?php

namespace ProcessMaker\Http\Middleware\Etag;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\Caching\EtagManager;
use Symfony\Component\HttpFoundation\Response;

class SetEtag
{
    public string $middleware = 'etag.set';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle request.
        $method = $request->getMethod();

        // Support using HEAD method for checking If-None-Match.
        if ($request->isMethod('HEAD')) {
            $request->setMethod('GET');
        }

        // Handle response.
        $response = $next($request);

        // Setting ETag.
        $etag = EtagManager::getEtag($request, $response);
        $response->setEtag($etag);
        $request->setMethod($method);

        return $response;
    }
}
