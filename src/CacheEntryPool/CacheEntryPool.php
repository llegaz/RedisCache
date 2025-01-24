<?php

declare(strict_types=1);

namespace LLegaz\Cache\Pool;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Our Redis <code>CacheEntryPool</code> will typically be a <a href="https://redis.io/glossary/redis-hashes/">redis hash</a>
 *
 */
class CacheEntryPool implements CacheItemPoolInterface
{
    private SimpleCache $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
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
