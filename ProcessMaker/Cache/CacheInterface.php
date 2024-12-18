<?php

namespace ProcessMaker\Cache;

interface CacheInterface
{
    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Fetches a value from the cache, or stores the value from the callback if the key exists.
     *
     * @param string $key The unique key of this item in the cache.
     * @param callable $callback The callback that will return the value to store in the cache.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \InvalidArgumentException
     */
    public function getOrCache(string $key, callable $callback): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool;

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key The unique cache key of the item to check for.
     *
     * @return bool True if the item is present in the cache, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Determines whether an item is missing from the cache.
     *
     * @param string $key The unique cache key of the item to check for.
     *
     * @return bool True if the item is missing from the cache, false otherwise.
     */
    public function missing(string $key): bool;

    /**
     * Creates a cache key based on provided parameters
     *
     * @param array $params Key parameters
     * @return string Generated cache key
     */
    public function createKey(array $params): string;
}
