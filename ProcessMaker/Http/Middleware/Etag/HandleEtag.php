<?php

namespace ProcessMaker\Http\Middleware\Etag;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        if (!config('etag.enabled')) {
            return $next($request);
        }

        // Process only GET and HEAD methods.
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        // Check if specific tables are defined for the route and calculate ETag.
        $etag = $this->generateEtagFromTablesIfNeeded($request);

        // If the client has a matching ETag, return a 304 response.
        // Otherwise, continue with the controller execution.
        $response = $etag && $this->etagMatchesRequest($etag, $request)
            ? $this->buildNotModifiedResponse($etag)
            : $next($request);

        // Add the pre-calculated ETag to the response if available.
        if ($etag) {
            $response = $this->setEtagOnResponse($response, $etag);
        }

        // If no ETag was calculated from tables, generate it based on the response.
        if (!$etag && $this->isCacheableResponse($response)) {
            $etag = EtagManager::getEtag($request, $response);
            if ($etag) {
                $response = $this->setEtagOnResponse($response, $etag);

                // If the client has a matching ETag, set the response to 304.
                if ($this->etagMatchesRequest($etag, $request)) {
                    $response = $this->buildNotModifiedResponse($etag);
                }
            }
        }

        // Detect if the ETag changes frequently for dynamic responses.
        $this->logEtagChanges($request, $etag);

        return $response;
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

    /**
     * Generate an ETag based on the tables defined in the route, if applicable.
     */
    private function generateEtagFromTablesIfNeeded(Request $request): ?string
    {
        $tables = $request->route()->defaults['etag_tables'] ?? null;

        return $tables ? EtagManager::generateEtagFromTables(explode(',', $tables)) : null;
    }

    /**
     * Check if the ETag matches the request.
     */
    private function etagMatchesRequest(string $etag, Request $request): bool
    {
        $noneMatch = array_map([$this, 'stripWeakTags'], $request->getETags());

        return in_array($etag, $noneMatch);
    }

    /**
     * Build a 304 Not Modified response with the given ETag.
     */
    private function buildNotModifiedResponse(string $etag): Response
    {
        $response = new Response();
        $response->setNotModified();
        $response->setEtag($etag);

        return $response;
    }

    /**
     * Set the ETag on a given response.
     */
    private function setEtagOnResponse(Response $response, string $etag): Response
    {
        $response->setEtag($etag);

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
     * Log ETag changes to detect highly dynamic responses.
     */
    private function logEtagChanges(Request $request, ?string $etag): void
    {
        if (!config('etag.enabled') || !config('etag.log_dynamic_endpoints')) {
            return;
        }

        if (!$etag) {
            return;
        }

        // Retrieve the history of ETags for this endpoint.
        $url = $request->fullUrl();
        $cacheKey = 'etag_history:' . md5($url);
        $etagHistory = Cache::get($cacheKey, []);

        // If the ETag is already in the history, it is not considered dynamic.
        if (in_array($etag, $etagHistory, true)) {
            return;
        }

        // Add the new ETag to the history.
        $etagHistory[] = $etag;

        // Keep the history limited to the last n ETags.
        $etagHistoryLimit = config('etag.history_limit', 10);
        if (count($etagHistory) > $etagHistoryLimit) {
            array_shift($etagHistory); // Remove the oldest ETag.
        }

        // Save the updated history in the cache, valid for 30 minutes.
        $cacheExpirationMinute = config('etag.history_cache_expiration');
        Cache::put($cacheKey, $etagHistory, now()->addMinutes($cacheExpirationMinute));

        // If the history is full and all ETags are unique, log this as a highly dynamic endpoint.
        if (count(array_unique($etagHistory)) === $etagHistoryLimit) {
            Log::info('ETag Dynamic endpoint detected', [
                'url' => $url,
            ]);
        }
    }
}
