<?php

namespace ProcessMaker\Http\Resources\Caching;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EtagManager
{
    /**
     * The callback used to generate the ETag.
     */
    protected static ?Closure $etagGenerateCallback = null;

    /**
     * Set a callback that should be used when generating the ETag.
     */
    public static function etagGenerateUsing(?Closure $callback): void
    {
        static::$etagGenerateCallback = $callback;
    }

    /**
     * Get ETag value for this request and response.
     */
    public static function getEtag(Request $request, Response $response, bool $includeUser = false): string
    {
        $etag = static::$etagGenerateCallback
            ? call_user_func(static::$etagGenerateCallback, $request, $response, $includeUser)
            : static::defaultGetEtag($response, $includeUser);

        return (string) Str::of($etag)->start('"')->finish('"');
    }

    /**
     * Generate an ETag, optionally including user-specific data.
     */
    private static function defaultGetEtag(Response $response, bool $includeUser = false): string
    {
        if ($includeUser) {
            return md5(auth()->id() . $response->getContent());
        }

        return md5($response->getContent());
    }
}
