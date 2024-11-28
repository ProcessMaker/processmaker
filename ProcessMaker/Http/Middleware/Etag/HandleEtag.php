<?php

namespace ProcessMaker\Http\Middleware\Etag;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\Caching\EtagManager;
use Symfony\Component\HttpFoundation\Response;

class HandleEtag
{
    public string $middleware = 'etag';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Save original method and support HEAD requests.
        $originalMethod = $request->getMethod();
        if ($request->isMethod('HEAD')) {
            $request->setMethod('GET');
        }

        // Handle response.
        $response = $next($request);

        // Generate ETag for the response.
        $etag = EtagManager::getEtag($request, $response);
        if ($etag) {
            // Set the ETag header.
            $response->setEtag($etag);

            // Get and strip weak ETags from request headers.
            $noneMatch = array_map([$this, 'stripWeakTags'], $request->getETags());

            // Compare ETags and set response as not modified if applicable.
            if (in_array($etag, $noneMatch)) {
                $response->setNotModified();
            }
        }

        // Restore original method and return the response.
        $request->setMethod($originalMethod);

        return $response;
    }

    /**
     * Remove the weak indicator (W/) from an ETag.
     */
    private function stripWeakTags(string $etag): string
    {
        return str_replace('W/', '', $etag);
    }
}
