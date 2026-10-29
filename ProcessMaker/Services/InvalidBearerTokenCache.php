<?php

namespace ProcessMaker\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvalidBearerTokenCache
{
    private const CACHE_PREFIX = 'invalid_bearer:';

    private const ATTR_STASHED_JWT = 'pm_invalid_bearer_token_record';

    private const ATTR_ALREADY_CACHED = 'pm_invalid_bearer_failure_recorded';

    private static ?RepositoryContract $repositoryForTesting = null;

    private static ?int $ttlSecondsForTesting = null;

    public static function prepareApiBearerBeforePassport(Request $request, array $guards): void
    {
        if (!self::isApiBearerRequest($request, $guards)) {
            return;
        }

        $jwt = self::jwtBearerFromRequest($request);
        if ($jwt !== null && self::isInNegativeCache($jwt)) {
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        $rawBearer = self::bearerFromAuthorizationHeader($request);
        if ($rawBearer !== null && !self::looksLikeJwt($rawBearer)) {
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        if ($rawBearer !== null && self::looksLikeJwt($rawBearer)) {
            $request->attributes->set(self::ATTR_STASHED_JWT, $rawBearer);
        }
    }

    public static function rememberPassportJwtFailure(Request $request, array $guards = []): void
    {
        if (!self::isApiBearerRequest($request, $guards)) {
            return;
        }

        $jwt = self::jwtBearerFromRequest($request);
        if ($jwt === null || self::ttlSeconds() <= 0) {
            return;
        }

        if ($request->attributes->get(self::ATTR_ALREADY_CACHED)) {
            return;
        }

        self::cache()->put(self::cacheKey($jwt), true, self::ttlSeconds());
        $request->attributes->set(self::ATTR_ALREADY_CACHED, true);
    }

    public static function isInNegativeCache(string $token): bool
    {
        if (self::ttlSeconds() <= 0) {
            return false;
        }

        return self::cache()->has(self::cacheKey($token));
    }

    public static function setRepositoryForTesting(?RepositoryContract $repository): void
    {
        self::$repositoryForTesting = $repository;
    }

    public static function setTtlSecondsForTesting(?int $ttl): void
    {
        self::$ttlSecondsForTesting = $ttl;
    }

    private static function isApiBearerRequest(Request $request, array $guards): bool
    {
        if ($guards !== [] && !in_array('api', $guards, true)) {
            return false;
        }

        if ($guards === [] && !str_starts_with($request->path(), 'api/')) {
            return false;
        }

        return self::bearerFromAuthorizationHeader($request) !== null
            || $request->attributes->has(self::ATTR_STASHED_JWT);
    }

    private static function jwtBearerFromRequest(Request $request): ?string
    {
        $fromHeader = self::bearerFromAuthorizationHeader($request);
        if ($fromHeader !== null && self::looksLikeJwt($fromHeader)) {
            return $fromHeader;
        }

        $stashed = $request->attributes->get(self::ATTR_STASHED_JWT);
        if (is_string($stashed) && self::looksLikeJwt($stashed)) {
            return $stashed;
        }

        return null;
    }

    private static function bearerFromAuthorizationHeader(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }

    private static function looksLikeJwt(string $token): bool
    {
        return substr_count($token, '.') === 2;
    }

    private static function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX . hash('sha256', $token);
    }

    private static function ttlSeconds(): int
    {
        if (self::$ttlSecondsForTesting !== null) {
            return self::$ttlSecondsForTesting;
        }

        return (int) config('auth.invalid_bearer_cache_ttl', 60);
    }

    private static function cache(): RepositoryContract
    {
        if (self::$repositoryForTesting !== null) {
            return self::$repositoryForTesting;
        }

        /** @var Repository $store */
        $store = Cache::store();

        return $store;
    }
}
