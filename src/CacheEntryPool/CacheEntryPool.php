<?php

declare(strict_types=1);

namespace LLegaz\Cache\Pool;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * PSR-6 implementation - Underlying Redis data type used is HASH
 *
 * Our Redis <code>CacheEntryPool</code> will typically be a <a href="https://redis.io/glossary/redis-hashes/">redis hash</a>
 *
 */
class CacheEntryPool implements CacheItemPoolInterface
{
    private CacheInterface $cache;
    private ?string $poolName = null;

    public function __construct(CacheInterface $cache, ?string $poolName)
    {
        $this->cache = $cache;
        if ($poolName) {
            $this->poolName = $poolName;
        }
    }

    public function clear(): bool
    {

    }

    public function commit(): bool
    {

    }

    public function deleteItem(string $key): bool
    {

    }

    public function deleteItems(string $keys): bool
    {

    }

    public function getItem(string $key): \Psr\Cache\CacheItemInterface
    {

    }

    public function getItems(string $keys = []): iterable
    {

    }

    public function hasItem(string $key): bool
    {

    }

    public function save(\Psr\Cache\CacheItemInterface $item): bool
    {

    }

    public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool
    {

    }

}
