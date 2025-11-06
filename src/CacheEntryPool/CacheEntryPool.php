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
 * <b>CRITICAL</b>: 
 * this brings limitation on expiration part so take it into account for your future design
 * (expiration on an entire pool only, redis version 7.4 (not free?) has the feature to hexpire fields inside a hash)
 *
 *
 * 
 * @todo homogenize rework documentation through this package
 * --
 * @todo dig into Redict ? (or just respect Salvatore's vision, see below)
 * @todo dig into Valkey.io
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

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        try {
            $this->cache->set($this->poolName, null);
            $this->cache->delete($this->poolName);
            unset($this->deferredItems);
            $this->deferredItems = [];
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteItem(string $key): bool
    {
        if ($this->isDeferred($key)) {
            unset($this->deferredItems[$key]);

            return $this->isDeferred($key);
        }

        return $this->deleteItems([$key]);

    }

    /**
     * @todo need a better return value taking deferred items into account
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->isDeferred($key)) {
                unset($this->deferredItems[$key]);
            }
        }

        return $this->cache->deleteFromPool($keys, $this->poolName);

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

        /**
         * if item is saved in the <b>deferred</b> pool and not expired we retrieve it
         * else we try to retrieve it from the "normal" pool.
         */
        $item = null;
        if ($this->isDeferred($key)) {
            $item = clone $this->deferredItems[$key];
            if ($item->isExpiredFromDLC()) {
                /**
                 *  if item is expired return it without hit (= miss),
                 *  the item deletion (expiration) in real pool is handled in next commit
                 */
                //unset($this->deferredItems[$key]);
                //$this->deleteItem($key);
                $item->miss();
            } else {
                $item->hit();
            }
        }

        if (!$item) {
            $value = $this->cache->fetchFromPool($key, $this->poolName);
            $item = new CacheEntry($key);
            if ($this->cache->exist($value)) {
                $item->set($value);
                $item->hit();
            }
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

        foreach ($keys as $key) {
            /**
             * if item is saved in the <b>deferred</b> pool and not expired we retrieve it
             * else we try to retrieve it from the "normal" pool.
             */
            $item = null;
            if ($this->isDeferred($key)) {
                $item = clone $this->deferredItems[$key];
                if ($item->isExpiredFromDLC()) {
                    /**
                     *  if item is expired return it without hit (= miss),
                     *  the item deletion (expiration) in real pool is handled in next commit
                     */
                    //unset($this->deferredItems[$key]);
                    //$this->deleteItem($key);
                    $item->miss();
                } else {
                    $item->hit();
                }
            }
            if (!$item) {
                $item = new CacheEntry($key);
                if (isset($values[$key]) && $this->cache->exist($values[$key])) {
                    $item->set($values[$key]);
                    $item->hit();
                }
            }
            //$items[] = $item;
            /**
             * because of this code (that is wrong IMHO)
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
        if ($this->isDeferred($key)) {
            if ($this->deferredItems[$key]->isExpiredFromDLC()) {
                /**
                 *  if item is expired we should handle the item deletion (expiration)
                 *  from the real pool in a future commit
                 */
                //unset($this->deferredItems[$key]);
                //$this->deleteItem($key);
                return false;
            } else {
                return true;
            }
        }

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
        if ($this->isExpired($item)) {
            $this->deleteItem($item->getKey());

            return false;
        }

        $bln = $this->cache->storeToPool([$item->getKey() => $item->get()], $this->poolName);
        if ($bln && $item instanceof CacheEntry && $item->getTTL() > 0) {
            /**
             * <b>CRITICAL</b>: 
             * /!\/!\/!\/!\/!\/!\/!\/!\/!\/!\
             * /!\  expires entire pool!  /!\
             * /!\/!\/!\/!\/!\/!\/!\/!\/!\/!\
             *
             * @todo maybe throw a PHP warning here ?
             */
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
        /**
         * @todo handle commit history for return value
         */
        if (!$this->isExpired($item)) {
            $this->deferredItems[$item->getKey()] = clone $item;

            return true;
        } elseif (isset($this->deferredItems[$item->getKey()])) {
            unset($this->deferredItems[$item->getKey()]);
        }

        return false;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit(): bool
    {
        if (!count($this->deferredItems)) {

            return true;
        }

        foreach ($this->deferredItems as $key => $item) {
            if (!$this->isExpired($item)) {
                $deferred[$key] = $item->get();
            } else {
                // clear cache of expired item
                $this->deleteItem($item->getKey());
            }
            unset($this->deferredItems[$key]);
        }
        $this->deferredItems = [];

        /**
         * <b>CRITICAL</b>:
         * 
         * Yes we are not handling expiration on bulked save here 
         * it is because expire the entire hash (the pool)
         * is not ideal, it is a "temporary" workaround for now to pass
         * cache integration tests..
         * I may disable the feature depending on the redis version used 
         * (as newer versions of redis do support hash field expiration but are
         *  no more free to use). For now there is only a <b>disclaimer</b>.
         * 
         * I will also have to dig in, and test this package with Valkey...
         * (maybe fork this package and make it exclusive to Valkey ?)
         * 
         * @todo dig in, and test this package with Valkey
         * @link https://valkey.io/blog/hash-fields-expiration Valkey 
         */
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

    private function isExpired(CacheEntry $item): bool
    {
        return !($item instanceof CacheEntry) || $item->getTTL() === 0 || $item->isExpiredFromDLC();
    }

    private function isDeferred(string $key): bool
    {
        return isset($this->deferredItems[$key]) && ($this->deferredItems[$key] instanceof CacheEntry);
    }
}
