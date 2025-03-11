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
 * this brings limitation on expiration part so take it into account for your future design
 * (expiration on an entire pool only, thus til redis v-xx on paid version only as of now)
 *
 */
class CacheEntryPool implements CacheItemPoolInterface
{
    private CacheInterface $cache;

    private ?string $poolName = null;

    private array $deferredItems = [];

    protected const HASH_DB_PREFIX = 'DEFAULT_Cache_Pool';

    public function __construct(CacheInterface $cache, ?string $poolName)
    {
        $this->cache = $cache;
        $this->poolName = $this->getPoolName($poolName);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {

    }

    public function commit(): bool
    {
        $deferred = [];
        foreach ($this->deferredItems as $item) {
            $deferred[$item->getKey()] = $item->get();
        }
        $this->cache->setMultiple($deferred);

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

    /**
     * @param mixed $poolSuffix
     * @return string
     */
    protected function getPoolName($poolSuffix): string
    {
        if ($poolSuffix !== null && strlen($poolSuffix)) {
            return self::HASH_DB_PREFIX . "_{$poolSuffix}";
        }

        // else return default pool name
        return self::HASH_DB_PREFIX;
    }

}
