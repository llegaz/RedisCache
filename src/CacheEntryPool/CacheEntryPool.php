<?php

declare(strict_types=1);

namespace LLegaz\Cache\Pool;

use LLegaz\Cache\Entry\CacheEntry;
use LLegaz\Cache\RedisEnhancedCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 implementation - Underlying Redis data type used is <b>HASH</b>
 *
 * Our Redis <code>CacheEntryPool</code> will typically be a <a href="https://redis.io/glossary/redis-hashes/">redis hash</a>
 *
 * this brings limitation on expiration part so take it into account for your future design
 * (expiration on an entire pool only, redis version 7.4 (not free?) has the feature to hexpire fields inside a hash)
 *
 *
 * --
 * @todo dig into Redict ? (or just respect Salvatore's vision, see below)
 * --
 * https://groups.google.com/g/redis-db/c/IqA3O8Fq494?pli=1
 *
 *    v.s
 *
 * https://redis.io/blog/hash-field-expiration-architecture-and-benchmarks
 *
 */
class CacheEntryPool implements CacheItemPoolInterface
{
    /**
     *
     * @var Psr\SimpleCache\CacheInterface
     */
    private RedisEnhancedCache $cache;

    /**
     * <b>Immutable</b>
     *
     * @var string|null
     */
    private ?string $poolName = null;

    /**
     *
     * @var array
     */
    private array $deferredItems = [];

    protected const HASH_DB_PREFIX = 'Cache_Pool';

    /**
     * @todo use Psr\SimpleCache\CacheInterface ?
     *
     * @param Psr\SimpleCache\CacheInterface $cache
     * @param string|null $pool
     */
    public function __construct(RedisEnhancedCache $cache, ?string $pool = null)
    {
        $this->cache = $cache;
        $this->poolName = $this->getPoolName($pool ?? '');
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $e = new \Exception();
        dump('pool cleared by : ', $e->getTrace()[1]['function']);

        try {
            $this->cache->set($this->poolName, null);
            $this->cache->delete($this->poolName);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteItem(string $key): bool
    {
        return $this->deleteItems([$key]);

    }

    public function deleteItems(array $keys): bool
    {
        $bln = $this->cache->deleteFromPool($keys, $this->poolName);
        dump('deleteItems', $keys, $bln);

        return  $bln;/*$this->cache->deleteFromPool($keys, $this->poolName);*/

    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem(string $key): CacheItemInterface
    {
        $value = $this->cache->fetchFromPool($key, $this->poolName);
        //dump('getItem', $key, $value);
        /** @todo handle hit, ttl */
        $item = new CacheEntry($key);
        dump('getItem: ' . $key . ' - ' . (is_string($value) ? $value : print_r($value)));
        if ($this->cache->exist($value)) {
            $item->set($value);
            $item->hit();
        }

        return $item;

    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return iterable
     *   An iterable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        $values = $this->cache->fetchFromPool($keys, $this->poolName);
        //dump($values);
        foreach ($keys as $key) {
            /** @todo handle hit, ttl */
            $item = new CacheEntry($key);
            if (isset($values[$key]) && $this->cache->exist($values[$key])) {
                $item->set($values[$key]);
                $item->hit();
            }
            //$items[] = $item;
            /**
             * because of his code (wrong)
             * https://github.com/php-cache/integration-tests/blob/fb7d78718f1e5bbfd7c63e5c5734000999ac7366/src/CachePoolTest.php#L208C40-L208C44
             */
            $items[$key] = $item;
        }

        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem(string $key): bool
    {
        return $this->cache->hasInPool($key, $this->poolName);

    }

    /**
     * Persists a cache item immediately.
     *
     * <b>CRITICAL</b>: if an expiration time, a ttl or expiration date is set, THEN the
     * <b>ENTIRE pool </b> will be expired ! Thus deleting all the pool's key values (hash fields).
     * @caution @warning
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item): bool
    {
        //dump("save", $item);
        $bln = $this->cache->storeToPool([$item->getKey() => $item->get()], $this->poolName);
        if ($bln && $item instanceof CacheEntry && $item->getTTL() > 0) {
            /**
             * /!\/!\/!\/!\/!\/!\/!\/!\/!\/!\
             * /!\  expires entire pool!  /!\
             * /!\/!\/!\/!\/!\/!\/!\/!\/!\/!\
             */
            //dump('/!\  expires entire pool!  /!\\', $this->poolName, $item->getTTL());
            $bln = $this->cache->setHsetPoolExpiration($this->poolName, $item->getTTL());
        }

        return $bln;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferredItems[] = $item;

        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit(): bool
    {
        $deferred = [];
        foreach ($this->deferredItems as $item) {
            $deferred[$item->getKey()] = $item->get();
        }

        return $this->cache->storeToPool($deferred, $this->poolName);
    }


    public function printCachePool(): string
    {
        return $this->cache->printCacheHash($this->poolName);
    }

    /**
     *
     *
     * @param mixed $poolSuffix
     * @return string
     */
    protected function getPoolName(string $poolSuffix): string
    {
        return strlen($poolSuffix) ?
            self::HASH_DB_PREFIX . "_{$poolSuffix}" :
            'DEFAULT_' . self::HASH_DB_PREFIX
        ;
    }
}
