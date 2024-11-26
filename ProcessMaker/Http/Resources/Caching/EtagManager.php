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
    public static function getEtag(Request $request, Response $response): string
    {
        $etag = static::$etagGenerateCallback
            ? call_user_func(static::$etagGenerateCallback, $request, $response)
            : static::defaultGetEtag($response);

        return (string) Str::of($etag)->start('"')->finish('"');
    }

    /**
     * Get default ETag value.
     */
    private static function defaultGetEtag(Response $response): string
    {
        return md5($response->getContent());
    }
}
