<?php

declare(strict_types=1);

namespace Kalakotra\Dashboard\Services;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * DashboardCacheService
 *
 * Thin wrapper around SilverStripe's PSR-16 cache.
 * Widgets use this service via DashboardWidget::getCachedData().
 *
 * Configuration:
 *
 *   Kalakotra\Dashboard\Services\DashboardCacheService:
 *     default_lifetime: 300
 */
class DashboardCacheService
{
    use Configurable;
    use Injectable;

    /** @config */
    private static int $default_lifetime = 300;

    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = Injector::inst()->get(CacheInterface::class . '.dashboard');
    }

    /**
     * Retrieve a cached value.
     *
     * @param string $key
     * @return array<string, mixed>|null  null on cache miss
     */
    public function get(string $key): ?array
    {
        $raw = $this->cache->get($key);

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Store a value in the cache.
     *
     * @param string               $key
     * @param array<string, mixed> $data
     * @param int|null             $lifetime  Seconds; null = default
     */
    public function set(string $key, array $data, ?int $lifetime = null): void
    {
        $ttl = $lifetime ?? $this->config()->get('default_lifetime');
        $this->cache->set($key, json_encode($data), $ttl);
    }

    /**
     * Remove a specific cache entry.
     */
    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Flush all dashboard cache entries.
     */
    public function flush(): void
    {
        $this->cache->clear();
    }
}
