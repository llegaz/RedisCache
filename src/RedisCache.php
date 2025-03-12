<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use DateTimeImmutable;
use LLegaz\Cache\Exception\InvalidKeyException;
use LLegaz\Cache\Exception\InvalidKeysException;
use LLegaz\Cache\Exception\InvalidValuesException;
use LLegaz\Redis\RedisAdapter;
use LLegaz\Redis\RedisClientInterface;
use Predis\Response\Status;
use Psr\SimpleCache\CacheInterface;

/**
  * Class RedisCache
 * PSR-16 implementation - Underlying Redis data type used is STRING
 *
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

    public function __construct(
        string $host = RedisClientInterface::DEFAULTS['host'],
        int $port = RedisClientInterface::DEFAULTS['port'],
        ?string $pwd = null,
        string $scheme = RedisClientInterface::DEFAULTS['scheme'],
        int $db = RedisClientInterface::DEFAULTS['database'],
        bool $persistent = false,
        ?RedisClientInterface $client = null
    ) {
        parent::__construct($host, $port, $pwd, $scheme, $db, $persistent, $client);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @param bool $allDBs
     * @return bool True on success and false on failure.
     * @throws LocalIntegrityException
     */
    public function clear(bool $allDBs = false): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        try {
            if ($allDBs) {
                $redisResponse = $this->getRedis()->flushAll();
            } else {
                if (!$this->checkIntegrity()) {
                    $this->throwLIEx();
                }
                $redisResponse = $this->getRedis()->flushdb();
            }
        } catch (\Throwable $t) {
            $redisResponse = null;
            if ($t instanceof \LLegaz\Redis\Exception\LocalIntegrityException) {
                throw $t;
            }
            $this->formatException($t);
        } finally {
            return (
                $redisResponse === true ||
                ($redisResponse instanceof Status && $redisResponse->getPayload() === 'OK')
            ) ? true : false;
        }
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
        $this->checkKeyValidity($key);
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        try {
            $redisResponse = $this->getRedis()->del($key);
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            return ($redisResponse >= 0) ? true : false;
        }
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->checkKeysValidity($keys);
        if (!count($keys)) {
            return true;
        }
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        try {
            $redisResponse = $this->getRedis()->del($keys);
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            return ($redisResponse >= 0) ? true : false;
        }
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
        $this->checkKeyValidity($key);
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        try {
            $value = $this->getRedis()->get($key);
            if (!is_string($value)) {
                $toReturn = $default;
            } else {
                $toReturn = @unserialize($value);
                if ($toReturn === false && $value !== 'b:0;') {
                    $toReturn = $value;
                }
            }
        } catch (\Throwable $t) {
            $toReturn = $default;
            $this->formatException($t);
        } finally {
            return $toReturn;
        }
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys    A list of keys that can be obtained in a single operation.
     * @param mixed            $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        /** handle keys as in setMultiple **/
        $keys = $this->checkKeysValidity($keys);
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        $values = [];

        try {
            $values = $this->getRedis()->mget($keys);
            foreach ($values as &$value) {
                if (!is_string($value)) {
                    $value = $default;
                } else {
                    $tmp = @unserialize($value);
                    if ($tmp !== false || $value === 'b:0;') {
                        $value = $tmp;
                    }
                }
            }
        } catch (\Throwable $t) {
            if (!count($values)) {
                array_fill(0, count($keys), $default);
            }
            $this->formatException($t);
        } finally {
            return array_combine(array_values($keys), array_values($values));
        }
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        $this->checkKeyValidity($key);
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        try {
            $redisResponse = $this->getRedis()->exists($key);
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            return ($redisResponse === 1) ? true : false;
        }


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
        $this->checkKeyValuePair($key, $value);
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        if ($ttl instanceof \DateInterval) {
            $ttl = $this->dateIntervalToSeconds($ttl);
        }

        try {
            if ($ttl < 0) {
                $ttl = 0;
            }
            $redisResponse = $this->getRedis()->set($key, $value);
            if ($ttl !== null && $ttl >= 0) {
                /** @todo maybe test return value here too */
                $this->getRedis()->expire($key, $ttl);
            }
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            return (
                $redisResponse === true ||
                ($redisResponse instanceof Status && $redisResponse->getPayload() === 'OK')
            ) ? true : false;
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }

        if (!is_array($values) && !($values instanceof \Traversable)) {
            throw new InvalidValuesException('RedisCache says "invalid keys/values set"');
        }

        /** @todo - refactor this (maybe use Predis/Redis Clients) */
        $newValues = [];
        foreach ($values as $key => $value) {
            if (is_scalar($key)) {
                $key = (string) $key;
            } elseif (is_object($key) || is_array($key)) {
                $key = spl_object_hash($key);
            } elseif (!is_string($key)) {
                throw new InvalidKeyException();
            }
            $this->checkKeyValuePair($key, $value);
            $newValues[$key] = $value;
        }
        if (!count($newValues)) {
            throw new InvalidValuesException('RedisCache says "empty keys/values set"');
        }

        if ($ttl instanceof \DateInterval) {
            $ttl = $this->dateIntervalToSeconds($ttl);
        }

        try {
            $redisResponse = false;
            if ($this->getRedis()->toString() === RedisClientInterface::PHP_REDIS) {
                $this->getRedis()->multi(\Redis::PIPELINE); // begin transaction
                $redisResponse = $this->getRedis()->mset($newValues);
                if ($ttl !== null && $ttl >= 0) {
                    foreach ($newValues as $key => $value) {
                        $this->getRedis()->expire($key, $ttl);
                    }
                }
                $this->getRedis()->exec();
            } else { // predis fallback
                $options = [
                    'cas' => true, // Initialize with support for CAS operations
                    'retry' => 3, // Number of retries on aborted transactions, after
                        // which the client bails out with an exception.
                ];
                $this->getRedis()->transaction($options, function ($t) use ($newValues, $ttl, &$redisResponse) {
                    $redisResponse = $t->mset($newValues);

                    if ($ttl !== null && $ttl >= 0) {
                        foreach ($newValues as $key => $value) {
                            $t->expire($key, $ttl);
                        }
                    }
                });
            }
        } catch (\Throwable $t) {
            $this->formatException($t);
        } finally {
            if ($redisResponse instanceof Status) {
                return $redisResponse->getPayload() === 'OK';
            } else {
                return $redisResponse !== false;
            }
        }
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    private function checkKeyValuePair(string $key, mixed &$value): void
    {
        $this->checkKeyValidity($key);
        if (!is_string($value)) {
            $value = serialize($value);
        }
    }

    /**
     *
     * @param string $key
     * @return void
     * @throws InvalidArgumentException
     */
    private function checkKeyValidity(string $key): void
    {
        $len = strlen($key);
        if (!$len) {
            throw new InvalidKeyException('RedisCache says "Empty Key is forbidden"');
        }

        if ($len < 2) {
            throw new InvalidKeyException('RedisCache says "Key is too small"');
        }

        //4 MB maximum key size
        if ($len > 4194304) {
            throw new InvalidKeyException('RedisCache says "Key is too big"');
        }
    }

    private function checkKeysValidity(iterable $keys): array
    {
        if (!is_array($keys) && !($keys instanceof \Traversable)) {
            throw new InvalidKeysException('RedisCache says "invalid keys"');
        }

        $newKeys = [];
        foreach ($keys as $key) {
            if (is_scalar($key)) {
                $key = (string) $key;
            } elseif (is_object($key)) {
                $key = spl_object_hash($key);
            } elseif (!is_string($key)) {
                throw new InvalidKeyException();
            }
            $this->checkKeyValidity($key);
            $newKeys[] = $key;
        }

        return $newKeys;
    }

    /**
     *
     * @param \DateInterval $ttl
     * @return int
     */
    private function dateIntervalToSeconds(\DateInterval $ttl): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($ttl);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }
}
