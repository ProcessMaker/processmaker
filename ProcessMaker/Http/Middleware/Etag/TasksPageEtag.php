<?php

namespace ProcessMaker\Http\Middleware\Etag;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\Caching\TasksPageEtag as TasksPageEtagResource;
use Symfony\Component\HttpFoundation\Response;

class TasksPageEtag
{
    public function __construct(
        private TasksPageEtagResource $tasksPageEtag
    ) {
    }

    /**
     * Handle Tasks page validation before rendering the full page shell.
     *
     * The ETag is computed from stable page context before controller execution, so a
     * matching conditional request can return 304 without paying the render cost.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('etag.enabled') || (!$request->isMethod('GET') && !$request->isMethod('HEAD'))) {
            return $next($request);
        }

        $etag = $this->tasksPageEtag->getEtag($request);

        if ($this->buildResponseWithEtag($etag)->isNotModified($request)) {
            return $this->withPrivateCacheHeaders($this->buildNotModifiedResponse($etag, $request));
        }

        $response = $next($request);
        $response->setEtag($etag);

        return $this->withPrivateCacheHeaders($response);
    }

    /**
     * Build a framework-compatible 304 response for a matched Tasks page ETag.
     */
    private function buildNotModifiedResponse(string $etag, Request $request): Response
    {
        $response = $this->buildResponseWithEtag($etag);
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Create a response carrying the Tasks page validator.
     *
     * Weak ETags are used because the HTML may be transformed by gzip while the
     * rendered representation remains equivalent for browser revalidation.
     */
    private function buildResponseWithEtag(string $etag): Response
    {
        $response = new Response();
        $response->setEtag($etag, true);

        return $response;
    }

    /**
     * Apply browser-cache headers that allow private conditional revalidation.
     */
    private function withPrivateCacheHeaders(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, must-revalidate');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');

        return $response;
    }
}
