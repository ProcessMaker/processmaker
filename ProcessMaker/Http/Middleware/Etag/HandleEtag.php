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
        // Process only GET and HEAD methods.
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        // Handle response.
        $response = $next($request);

        // Check if the response is cacheable.
        if (!$this->isCacheableResponse($response)) {
            return $response; // Skip ETag for non-cacheable responses.
        }

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

        return $response;
    }

    /**
     * Remove the weak indicator (W/) from an ETag.
     */
    private function stripWeakTags(string $etag): string
    {
        return str_replace('W/', '', $etag);
    }

    /**
     * Determine if a response is cacheable.
     */
    private function isCacheableResponse(Response $response): bool
    {
        $cacheableStatusCodes = [200, 203, 204, 206, 304];
        $cacheControl = $response->headers->get('Cache-Control', '');

        // Verify if the status code is cacheable and does not contain "no-store".
        return in_array($response->getStatusCode(), $cacheableStatusCodes)
            && !str_contains($cacheControl, 'no-store');
    }
}
