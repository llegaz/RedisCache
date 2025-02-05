<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use LLegaz\Redis\RedisAdapter;
use Psr\SimpleCache\CacheInterface;

/**
  * Class RedisCache
 * @package RedisCache
 * @author Laurent LEGAZ <laurent@legaz.eu>

 */
class RedisCache extends RedisAdapter implements CacheInterface
{
    /**
     * Expiration Time Constants named by duration
     */
    public const FOREVER = -1;

    public const SHORT_EXPIRATION_TIME = 180;     // 3 minutes

    public const TM_EXPIRATION_TIME = 1800;    // 30 minutes

    public const FM_EXPIRATION_TIME = 2400;    // 40 minutes

    public const HOUR_EXPIRATION_TIME = 3600;    // 1 hour

    public const HOURS_EXPIRATION_TIME = 14400;   // 4 hours (in seconds)

    public const DAY_EXPIRATION_TIME = 86400;   // 1 day

    public const TWO_DAYS_EXPIRATION_TIME = 172800;  // 2 days

    public const LONG_EXPIRATION_TIME = 2592000; // 30 days

    public const VERY_LONG_EXPIRATION_TIME = 7776000; // 90 days

    protected const HASH_DB_PREFIX = 'DEFAULT_Cache_Pool';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @param bool $allDBs
     * @return bool True on success and false on failure.
     */
    public function clear(bool $allDBs = false): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        if ($allDBs) {
            $redisResponse = $this->client->flushAll();
        } else {
            $redisResponse = $this->client->flushdb();
        }

        return ($redisResponse instanceof Status && $redisResponse->getPayload() === 'OK') ? true : false;

    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {

    }

    public function deleteMultiple($keys): bool
    {

    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed
    {

    }

    public function getMultiple($keys, mixed $default = null): iterable
    {

    }

    public function has(string $key): bool
    {

    }

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
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {

    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {

    }

}
