<?php

declare(strict_types=1);

namespace LLegaz\Cache\Pool;

use LLegaz\Cache\Entry\CacheEntry;
use Psr\Cache\CacheItemInterface;
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

    /**
     * <b>Immutable</b>
     *
     * @var string|null
     */
    private ?string $poolName = null;

    private array $deferredItems = [];

    protected const HASH_DB_PREFIX = 'DEFAULT_Cache_Pool';

    public function __construct(CacheInterface $cache, ?string $pool)
    {
        $this->cache = $cache;
        $this->poolName = $this->getPoolName($pool);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $this->cache->set($this->poolName, null);
        $this->cache->delete($this->poolName);

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
        return $this->cache->deleteFromPool($key);

    }

    public function deleteItems(array $keys): bool
    {
        return $this->cache->deleteFromPool($keys);

    }

    public function getItem(string $key): CacheItemInterface
    {
        $value = $this->cache->fetchFromPool($key, $this->poolName);
        /** @todo handle hit, ttl */
        $item = new CacheEntry($key);
        $item->set($value);

        return $item;

    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        $values = $this->cache->fetchFromPool($keys, $this->poolName);
        foreach ($values as $key => $value) {
            /** @todo handle hit, ttl */
            $item = new CacheEntry($key);
            $item->set($value);
            $items[] = $item;
        }

        return $items;
    }

    public function hasItem(string $key): bool
    {

    }

    public function save(CacheItemInterface $item): bool
    {

    }

    public function saveDeferred(CacheItemInterface $item): bool
    {

    }

    /**
     * @param mixed $poolSuffix
     * @return string
     */
    protected function getPoolName(string $poolSuffix): string
    {
        return strlen($poolSuffix) ?
            self::HASH_DB_PREFIX . "_{$poolSuffix}" :
            self::HASH_DB_PREFIX
        ;
    }

}
