<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use LLegaz\Cache\Exception\InvalidKeyException;
use LLegaz\Cache\Exception\InvalidKeysException;
use LLegaz\Cache\Exception\InvalidValuesException;
use LLegaz\Redis\RedisAdapter;
use LLegaz\Redis\RedisClientInterface;
use Predis\Response\Status;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class RedisCache
 * PSR-16 implementation - Underlying Redis data type used is STRING
 *
 *
 * @todo I think we will have to refactor here too in order to pass the RedisAdapter as a parameter
 *       or keep it as a class attribute ?
 *
 *
 * @todo refactor to hide those predis Status and logic bound either to predis and php-redis
 *      (use adapter project client classes like mset => multipleSet method)
 * @todo and also clean and harmonize all those <code>$redisResponse</code>
 *
 *
 * @note I have also have some concerns on keys because redis can handle Bytes and we are only handling
 * strings (contracts from Psr\SimpleCache v3.0.0 interface) which is totally fine for my own use cases but...
 *
 * @package RedisCache
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisCache extends RedisAdapter implements CacheInterface
{
    /**
     * Expiration Time Constants named by duration
     */
    public const FOREVER = null;

    public const SHORT_EXPIRATION_TIME = 180;     // 3 minutes

    public const TM_EXPIRATION_TIME = 1800;    // 30 minutes

    public const FM_EXPIRATION_TIME = 2400;    // 40 minutes

    public const HOUR_EXPIRATION_TIME = 3600;    // 1 hour

    public const HOURS_EXPIRATION_TIME = 14400;   // 4 hours (in seconds)

    public const DAY_EXPIRATION_TIME = 86400;   // 1 day

    public const TWO_DAYS_EXPIRATION_TIME = 172800;  // 2 days

    public const LONG_EXPIRATION_TIME = 2592000; // 30 days

    public const VERY_LONG_EXPIRATION_TIME = 7776000; // 90 days

    public const DOES_NOT_EXIST = '%=%=% item does not exist %=%=%';

    /**
    * Maximum key length: 8KB
    *
    * Permissive by design. We trust developers to make appropriate choices.
    *
    * PSR-16 permits extended lengths ("MAY support longer lengths").
    * 8KB accommodates URL-based keys and other realistic scenarios while
    * remaining reasonable for Redis performance.
    *
    * If you need stricter validation, implement it at your application layer.
    */
    private const MAX_KEY_LENGTH = 8192;

    public function __construct(
        string $host = RedisClientInterface::DEFAULTS['host'],
        int $port = RedisClientInterface::DEFAULTS['port'],
        ?string $pwd = null,
        string $scheme = RedisClientInterface::DEFAULTS['scheme'],
        int $db = RedisClientInterface::DEFAULTS['database'],
        bool $persistent = false,
        ?RedisClientInterface $client = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($host, $port, $pwd, $scheme, $db, $persistent, $client, $logger);
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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function delete(string $key): bool
    {
        $this->checkKeyValidity($key);
        $this->begin();

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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->checkKeysValidity($keys);
        if (!count($keys)) {
            return true;
        }
        $this->begin();

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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->checkKeyValidity($key);
        $this->begin();

        try {
            $value = $this->getRedis()->get($key);
            if (!is_string($value)) {
                $toReturn = $default;
            } else {
                $toReturn = $this->setCorrectValue($value);
            }
        } catch (\Throwable $t) {
            $toReturn = $default;
            $this->formatException($t);
        } finally {
            return $toReturn;
        }
    }

    /**
     * @todo remove this
     * @todo No, no, no ! Nein, nein, nein, don't do that here !
     * we need another layer (another project ?) but it is NOT priority
     *
     *
     * Getter for non-string key variant
     * (nope, not accessible just here for documentation purpose)
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    private function getWithNonStrKey(mixed $key, mixed $default = null): mixed
    {
        return $this->get($this->checkKeyValidity($key), $default);
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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        /** handle keys as in setMultiple **/
        $keys = $this->checkKeysValidity($keys);
        $this->begin();

        $values = [];

        try {
            $values = $this->getRedis()->mget($keys);
            foreach ($values as &$value) {
                if (!is_string($value)) {
                    $value = $default;
                } else {
                    $value = $this->setCorrectValue($value);
                    if (!$this->exist($value)) {
                        $value = $default;
                    }
                }
            }
        } catch (\Throwable $t) {
            if (!count($values)) {
                $values = array_fill(0, count($keys), $default);
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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function has(string $key): bool
    {
        $this->checkKeyValidity($key);
        $this->begin();

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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = self::FOREVER): bool
    {
        $this->checkKeyValuePair($key, $value);
        $this->begin();

        if ($ttl instanceof \DateInterval) {
            $ttl = Utils::dateIntervalToSeconds($ttl);
        }

        try {
            if ($ttl < 0) {
                $ttl = 0;
            }
            $redisResponse = $this->getRedis()->set($key, $value);
            if ($ttl !== self::FOREVER && $ttl >= 0) {
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
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = self::FOREVER): bool
    {
        $this->begin();
        if (!is_array($values) && !($values instanceof \Traversable)) {
            throw new InvalidValuesException('RedisCache says "invalid keys/values set"');
        }

        $newValues = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                $key = $this->keyToString($key);
            }
            $this->checkKeyValuePair($key, $value);
            $newValues[$key] = $value;
        }
        if (!count($newValues)) {
            throw new InvalidValuesException('RedisCache says "empty keys/values set"');
        }

        if ($ttl instanceof \DateInterval) {
            $ttl = Utils::dateIntervalToSeconds($ttl);
        }

        try {
            $redisResponse = $this->getRedis()->multipleSet($newValues, $ttl);
        } catch (\Throwable $t) {
            $this->formatException($t);
            $redisResponse = false;
        } finally {
            return $redisResponse;
        }
    }

    /**
     * @todo maybe test this serialization process more in depth
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    protected function checkKeyValuePair(string $key, mixed &$value): void
    {
        $this->checkKeyValidity($key);
        if (!is_string($value)) {
            $value = serialize($value);
        }
    }

    /**
     * passing by reference here is only needed when the key given isn't already a string
     *
     * @todo check special cases (or special implementation) when key isn't a string ? (or not)
     *
     * @param string $key
     * @return void
     * @throws InvalidArgumentException
     */
    protected function checkKeyValidity(mixed &$key): void
    {
        if (!is_string($key)) {
            $key = $this->keyToString($key);
        }

        $len = strlen($key);

        // Empty keys are ambiguous
        if ($len === 0) {
            throw new InvalidKeyException('Cache key cannot be empty');
        }

        // Reasonable upper limit for performance
        if ($len > self::MAX_KEY_LENGTH) {
            throw new InvalidKeyException(
                sprintf(
                    'Cache key exceeds maximum length of %d characters (got %d)',
                    self::MAX_KEY_LENGTH,
                    $len
                )
            );
        }

        // Whitespace causes issues in Redis CLI and debugging
        if (preg_match('/\s/', $key)) {
            throw new InvalidKeyException('Cache key cannot contain whitespace');
        }

        // That's it. Redis handles everything else.
        // We trust you to know what you're doing.
    }

    protected function checkKeysValidity(iterable $keys): array
    {
        if (!is_array($keys) && !($keys instanceof \Traversable)) {
            throw new InvalidKeysException('RedisCache says "invalid keys"');
        }

        $newKeys = [];
        foreach ($keys as $key) {
            $this->checkKeyValidity($key);
            $newKeys[] = $key;
        }

        return $newKeys;
    }

    private function keyToString(mixed $key): string
    {
        if (is_scalar($key)) {
            $key = (string) $key;
        } elseif (is_object($key)) {
            $key = spl_object_hash($key);
        }

        if (!is_string($key)) {
            throw new InvalidKeyException();
        }

        return $key;
    }

    /**
     * value is either a serialized thing as a string or directly a string
     * or false if not set correctly(empty or null string) but this case
     * <b>SHOULD</b> be handled beforehand
     *
     * @param mixed $value
     * @return bool
     */
    protected function setCorrectValue(string &$value): mixed
    {
        try {
            $tmp = @unserialize(trim($value));
        } catch (\Throwable $t) {
            $this->formatException($t);

            return self::DOES_NOT_EXIST;
        } finally {
            if ($tmp !== false || ($tmp === false && $value === 'b:0;')) {
                $value = $tmp; // if value var wasn't a string affect its original value type to it
            }

            return $value;
        }
    }

    public function exist(mixed $value): bool
    {
        if (is_string($value) && strlen($value) && $value === self::DOES_NOT_EXIST) {
            return false;
        }

        return true;
    }

    /**
     * begin redis communication
     *
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    protected function begin(): void
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        if (!$this->checkIntegrity()) {
            $this->throwLIEx();
        }
    }
}
