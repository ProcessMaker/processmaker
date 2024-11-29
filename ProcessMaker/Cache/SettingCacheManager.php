<?php

namespace ProcessMaker\Cache;

use Illuminate\Cache\CacheManager;

class SettingCacheManager implements CacheInterface
{
    protected CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function __call($method, $arguments)
    {
        return $this->cacheManager->$method(...$arguments);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cacheManager->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->cacheManager->put($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cacheManager->forget($key);
    }

    public function clear(): bool
    {
        return $this->cacheManager->flush();
    }

    public function has(string $key): bool
    {
        return $this->cacheManager->has($key);
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }
}
