<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Get the cache store instance.
     */
    public static function getStore(?string $store = null)
    {
        return $store ? Cache::store($store) : Cache::store(config('cache.default'));
    }

    /**
     * Normalize TTL (Time To Live)
     */
    public static function normalizeTtl($ttl)
    {
        return $ttl ?? now()->addMinutes(60); // Default to 1 hour if TTL is null
    }

    /**
     * Generate a cache key with optional unique identifier.
     */
    public static function getCacheKey(string $key, $uniqueId = null): string
    {
        $appName = strtolower(str_replace(" ", "", env('APP_NAME')));
        return "{$appName}:{$key}:" . app()->getLocale() . ($uniqueId ? ":{$uniqueId}" : '');
    }

    /**
     * Set a single cache value.
     */
    public static function setCache(string $key, $data, $ttl = null, ?string $store = null)
    {
        $cache = self::getStore($store);
        $ttl = self::normalizeTtl($ttl);
        return $cache->put($key, $data, $ttl);
    }

    /**
     * Get a single cache value.
     */
    public static function getCache(string $key, ?string $store = null, $default = null)
    {
        return self::getStore($store)->get($key, $default);
    }

    /**
     * Delete a single cache key.
     */
    public static function delCache(string $key, ?string $store = null)
    {
        return self::getStore($store)->forget($key);
    }

    /**
     * Set multiple cache values at once (bulk caching).
     */
    public static function bulkSetCache(array $items, $ttl = null, ?string $store = null)
    {
        $cache = self::getStore($store);
        $ttl = self::normalizeTtl($ttl);

        foreach ($items as $key => $value) {
            $cache->put($key, $value, $ttl);
        }
    }

    /**
     * Get multiple cache values at once.
     */
    public static function bulkGetCache(array $keys, ?string $store = null): array
    {
        return self::getStore($store)->many($keys);
    }

    /**
     * Delete multiple cache keys at once.
     */
    public static function bulkDelCache(array $keys, ?string $store = null)
    {
        return self::getStore($store)->forget($keys);
    }

    /**
     * Get or Set cache (Lazy Loading Pattern)
     */
    public static function getOrSetCached(string $key, callable $fnQuery, ?string $store = null, $uniqueId = null, $ttl = null)
    {
        $cacheKey = self::getCacheKey($key, $uniqueId);
        $cache = self::getStore($store);
        $data = $cache->get($cacheKey);

        if (filled($data)) return $data;

        $data = $fnQuery();

        if (filled($data)) {
            $cache->put($cacheKey, $data, self::normalizeTtl($ttl));
        }

        return $data;
    }
}
