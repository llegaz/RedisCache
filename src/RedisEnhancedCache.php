<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use LLegaz\Cache\Exception\InvalidKeyException;

/**
 * ------------------------------------------------------------------------------------------------------------
 * | Class RedisEnhancedCache                                                                                 |
 * ------------------------------------------------------------------------------------------------------------
 * This is built on top of PSR-16 implementation to complete it for PSR-6 CacheEntries Pools.
 * My implementation is based on Redis Hashes implying some technical limitations.
 *
 *
 *<b>CRITICAL</b>:
 * Here we use the Hash implementation from redis. Expiration Time is set with the setHsetPoolExpiration method
 * on the entire Hash Set HASH_DB_PREFIX. $suffix (private HSET Pool in Redis, specified with $suffix
 * with those methods you can store and retrieve specific data linked together in a separate data set)
 * THUS THE ENTIRE POOL (redis hash) is EXPIRED as there is no way to expire a hash field per field only
 * the firsts redis server versions.
 *
 * @todo test valkey and reddict
 *
 * @todo and also clean and harmonize all those <code>$redisResponse</code>
 *
 *
 * It is to be noted that we use different terminology here  from Redis project in the case of a HASH.
 * for us : pool = key and key = field, but it is only semantic differences...
 * ------------------------------------------------------------------------------------------------------------
 *
 * <b>Architecture Overview:</b>
 * This class extends RedisCache to provide pool-based caching functionality using Redis Hash data structures.
 * Each pool represents a Redis Hash where multiple key-value pairs can be grouped together and managed
 * as a logical unit with shared expiration time.
 *
 * <b>Key Concepts:</b>
 * - Pool: A Redis Hash that groups related cache entries together (Redis terminology: "key")
 * - Key: An individual field within a pool (Redis terminology: "field")
 * - Value: The data associated with a key within a pool
 *
 * <b>Terminology Mapping:</b>
 * <pre>
 * This Class    | Redis Native  | Description
 * --------------|---------------|------------------------------------------
 * Pool          | Key           | The Hash structure name
 * Key           | Field         | A field within the Hash
 * Value         | Value         | The data stored in a Hash field
 * </pre>
 *
 * <b>Usage Example:</b>
 * <code>
 * $cache = new RedisEnhancedCache($redis);
 *
 * // Store multiple values in a pool
 * $cache->storeToPool([
 *     'user:123:name' => 'John Doe',
 *     'user:123:email' => 'john@example.com'
 * ], 'user_data');
 *
 * // Set expiration for the entire pool
 * $cache->setHsetPoolExpiration('user_data', 3600);
 *
 * // Fetch single or multiple values
 * $name = $cache->fetchFromPool('user:123:name', 'user_data');
 * $userData = $cache->fetchFromPool(['user:123:name', 'user:123:email'], 'user_data');
 * </code>
 *
 * <b>Limitations:</b>
 * - Expiration applies to entire pools, not individual keys within a pool
 * - All values are serialized for storage consistency
 * - Keys and values must pass validation checks defined in parent class
 *
 * @package RedisCache
 * @author Laurent LEGAZ <laurent@legaz.eu>
 * @version 1.0
 * @see RedisCache Parent class providing base Redis functionality
 * @see https://redis.io/docs/data-types/hashes/ Redis Hash documentation
 */
class RedisEnhancedCache extends RedisCache
{
    /**
     * Default pool name used when no pool is specified in method calls.
     *
     * This constant defines the fallback pool name that will be used throughout
     * the application when methods are called without explicitly specifying a pool.
     *
     * @caution - modify this ONLY if you modify symetrically the default pool name
     *           returned in <code>CacheEntryPool::getPoolName</code> protected method
     *
     * @var string
     */
    private const DEFAULT_POOL = 'DEFAULT_Cache_Pool';

    /**
     * Stores multiple key-value pairs in a specified Redis Hash pool.
     *
     * This method allows batch insertion of cache entries into a named pool using Redis Hash operations.
     * All values are automatically serialized before storage to ensure consistency. The method uses
     * HMSET for multiple values and HSET for single values to optimize Redis operations.
     *
     * <b>Behavior:</b>
     * - For multiple values (count > 1): Uses Redis HMSET command
     * - For single value (count === 1): Uses Redis HSET command
     * - For empty array: Returns false without Redis operation
     *
     * <b>Data Processing:</b>
     * - Keys are validated against PSR compliance rules
     * - Values are serialized using internal serialization mechanism
     * - Both keys and values undergo validation before storage
     *
     * <b>Usage Examples:</b>
     * <code>
     * // Store user session data
     * $cache->storeToPool([
     *     'session_id' => 'abc123',
     *     'user_id' => 456,
     *     'login_time' => time()
     * ], 'user_sessions');
     *
     * // Store single configuration value
     * $cache->storeToPool(['app_version' => '2.1.0'], 'app_config');
     * </code>
     *
     * @todo rework this ?
     * @todo document this
     * @todo enhance keys / values treatment (see / homogenize with RedisCache::setMultiple and RedisCache::checkKeysValidity)
     * @todo need better handling on serialization and its reverse method in fetches.
     * @todo maybe we can do something cleaner
     * @todo rework exception handling and returns
     * @todo test this specific scenario (maybe apply it to hmset ?)
     *
     * @param array $values A flat array of key => value pairs to store in GIVEN POOL name
     *                      Keys must be strings or integers, values can be any serializable type
     * @param string $pool the pool name - defaults to DEFAULT_POOL if not specified
     *
     * @return bool True on success, false on failure or when values array is empty
     *
     * @throws LLegaz\Redis\Exception\ConnectionLostException When Redis connection is lost during operation
     * @throws LLegaz\Redis\Exception\LocalIntegrityException When data integrity checks fail
     * @throws InvalidKeyException When key validation fails
     *
     * @see RedisCache::setMultiple() Similar method in parent class for non-pool operations
     * @see RedisCache::checkKeysValidity() Key validation method
     */
    public function storeToPool(array $values, string $pool = self::DEFAULT_POOL): bool
    {
        $this->begin();

        /**
         * @todo enhance keys / values treatment (see / homogenize with RedisCache::setMultiple and RedisCache::checkKeysValidity)
         * @todo need better handling on serialization and its reverse method in fetches.
         * @todo maybe we can do something cleaner
         *
         *
         * check keys arguments are valid, and values are all stored as <b>strings</b>
         */
        foreach ($values as $key => $value) {
            $this->checkKeyValuePair($key, $value);
            $values[$key] = $value; // I mean.. complexity is hidden here (data value are serialized)
        }

        /**
         * @todo rework exception handling and returns
         */
        $cnt = count($values);
        if ($cnt > 1) {
            return $this->getRedis()->hmset($pool, $values) == 'OK';
        } elseif ($cnt === 1) {
            $key = array_keys($values)[0];
            $value = isset($key) ? $values[$key] : (isset($values[0]) ? $values[0] : null);
            if (!$this->exist($value)) {
                /**
                 * @todo test this specific scenario (maybe apply it to hmset ?)
                 */
                $this->throwUEx('The value: ' . $value . ' isn\'t accepted'); // because all values are authorized except this predefined value to sort actual exisiting values internally...
            }
            if ($value) {
                //hset should returns the number of fields stored for a single key (always one here)
                return $this->getRedis()->hset($pool, $key, $value) >= 0;
            }
        }

        return false;
    }

    /**
     * Retrieves one or more values from a specified Redis Hash pool.
     *
     * This method provides flexible retrieval of cache entries from a pool. It can fetch:
     * - A single value when given a string or integer key
     * - Multiple values when given an array of keys
     *
     * All retrieved values are automatically unserialized to restore their original data types.
     *
     * <b>Return Behavior:</b>
     * - Single key (string/int): Returns the value or DOES_NOT_EXIST constant
     * - Multiple keys (array): Returns associative array of key => value pairs
     * - Non-existent keys: Replaced with DOES_NOT_EXIST constant in results
     * - Invalid parameters: Throws InvalidKeyException
     *
     * <b>Usage Examples:</b>
     * <code>
     * // Fetch single value
     * $username = $cache->fetchFromPool('user:123:name', 'user_data');
     *
     * // Fetch multiple values
     * $userData = $cache->fetchFromPool([
     *     'user:123:name',
     *     'user:123:email',
     *     'user:123:role'
     * ], 'user_data');
     *
     * // Check if value exists
     * if ($username !== RedisCache::DOES_NOT_EXIST) {
     *     echo "Username: $username";
     * }
     * </code>
     *
     * <b>Data Processing:</b>
     * - Values are automatically deserialized upon retrieval
     * - String values are converted back to their original types
     * - Missing values are marked with DOES_NOT_EXIST constant
     * - Array results maintain key association from input
     *
     * @todo document this (and the rest)
     *
     * @param int|string|array $key Single key (string/int) or array of keys to retrieve from the pool
     * @param string $pool the pool's name - defaults to DEFAULT_POOL if not specified
     *
     * @return mixed Single value, associative array of values, or DOES_NOT_EXIST constant
     *               - For single key: Returns the value or DOES_NOT_EXIST
     *               - For multiple keys: Returns array with keys mapped to values/DOES_NOT_EXIST
     *
     * @throws LLegaz\Redis\Exception\ConnectionLostException When Redis connection is lost during operation
     * @throws LLegaz\Redis\Exception\LocalIntegrityException When data integrity checks fail
     * @throws LLegaz\Cache\Exception\InvalidKeyException When key format is invalid or unsupported type provided
     *
     * @see storeToPool() Method to store values in a pool
     * @see hasInPool() Method to check if a key exists without retrieving value
     * @see RedisCache::setCorrectValue() Internal deserialization method
     */
    public function fetchFromPool(mixed $key, string $pool = self::DEFAULT_POOL): mixed
    {
        switch (gettype($key)) {
            case 'integer':
            case 'string':
                $this->checkKeyValidity($key);
                $this->begin();
                $value = $this->getRedis()->hget($pool, $key);
                if (is_string($value)) {
                    $value = $this->setCorrectValue($value);

                    return $value;
                }

                break;
            case 'array':
                if (count($key)) {
                    $this->checkKeysValidity($key);
                    $this->begin();
                    $data = array_combine(
                        array_values($key),
                        array_values($this->getRedis()->hmget($pool, $key))
                    );

                    foreach ($data as $key => $value) {
                        if (is_string($value)) {
                            $data[$key] = $this->setCorrectValue($value);
                        } else {
                            $data[$key] = self::DOES_NOT_EXIST;
                        }
                    }
                    if (count($data)) {

                        return $data;
                    }
                }

                break;
            default:
                throw new InvalidKeyException('Invalid Parameter');
        }

        return self::DOES_NOT_EXIST;
    }

    /**
     * Checks whether a specific key exists in a Redis Hash pool.
     *
     * This method provides efficient existence checking without retrieving the actual value,
     * which is useful for conditional logic and validation operations. It uses Redis HEXISTS
     * command for optimal performance.
     *
     * <b>Implementation Notes:</b>
     * - Uses native Redis HEXISTS command for efficiency
     * - Handles adapter differences between php-redis and predis clients
     * - php-redis returns boolean true/false
     * - predis returns integer 1/0
     * - Both are normalized to boolean return value
     *
     * <b>Usage Examples:</b>
     * <code>
     * // Check before fetching
     * if ($cache->hasInPool('user:123:name', 'user_data')) {
     *     $name = $cache->fetchFromPool('user:123:name', 'user_data');
     * }
     *
     * // Conditional storage
     * if (!$cache->hasInPool('config:version', 'app_config')) {
     *     $cache->storeToPool(['config:version' => '1.0'], 'app_config');
     * }
     * </code>
     *
     * @todo document this
     *
     * @param string $key The key to check for existence in the pool
     * @param string $pool The pool name - defaults to DEFAULT_POOL if not specified
     *
     * @return bool True if the key exists in the pool, false otherwise
     *
     * @throws LLegaz\Redis\Exception\ConnectionLostException When Redis connection is lost during operation
     * @throws LLegaz\Redis\Exception\LocalIntegrityException When data integrity checks fail
     * @throws InvalidKeyException When key validation fails
     *
     * @see fetchFromPool() Method to retrieve values if they exist
     * @see storeToPool() Method to store values in a pool
     * @see PredisClient Adapter class handling predis-specific behavior
     * @see RedisClient Adapter class handling php-redis-specific behavior
     */
    public function hasInPool(string $key, string $pool = self::DEFAULT_POOL): bool
    {
        $this->checkKeyValidity($key);
        $this->begin();

        try {
            $redisResponse = $this->getRedis()->hexists($pool, $key);
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            /**
             * php-redis hexists returns true while predis returns 1
             *
             * @see adapter classes in adapter (or gateway, or facade) package,
             *      namely <code>PredisClient</code> and <code>RedisClient</code>
             *
             * @todo in order to simplify and unify those returns mechanisms properly
             */
            return ($redisResponse === true || $redisResponse === 1) ? true : false;
        }
    }

    /**
     * Deletes one or more keys from a specified Redis Hash pool.
     *
     * This method removes cache entries from a pool using Redis HDEL command. It supports
     * batch deletion of multiple keys in a single operation for efficiency. The method
     * validates all keys before attempting deletion.
     *
     * <b>Behavior:</b>
     * - Uses Redis HDEL for atomic deletion
     * - All keys are validated before deletion attempt
     * - Non-existent keys are silently ignored (not treated as errors)
     * - Returns true if Redis operation succeeds (even if some keys didn't exist)
     *
     * <b>Performance Considerations:</b>
     * - Batch deletion is more efficient than individual deletions
     * - Single Redis command for all keys reduces network overhead
     * - Atomic operation ensures consistency
     *
     * <b>Usage Examples:</b>
     * <code>
     * // Delete single entry
     * $cache->deleteFromPool(['user:123:name'], 'user_data');
     *
     * // Delete multiple entries
     * $cache->deleteFromPool([
     *     'user:123:name',
     *     'user:123:email',
     *     'user:123:role'
     * ], 'user_data');
     *
     * // Clear specific cache entries after update
     * $keysToInvalidate = ['product:456:price', 'product:456:stock'];
     * $cache->deleteFromPool($keysToInvalidate, 'product_cache');
     * </code>
     *
     * @todo document this
     *
     * @param array $keys Array of key names to delete from the pool (must be strings or integers)
     * @param string $pool the pool's name - defaults to DEFAULT_POOL if not specified
     *
     * @return bool True on success (returns true even if keys didn't exist), false on failure
     *
     * @throws ConnectionLostException When Redis connection is lost during operation
     * @throws InvalidKeyException When any key validation fails
     *
     * @see storeToPool() Method to store values in a pool
     * @see hasInPool() Method to check existence before deletion
     * @see setHsetPoolExpiration() Method to expire entire pool instead of individual keys
     */
    public function deleteFromPool(array $keys, string $pool = self::DEFAULT_POOL): bool
    {
        $keys = $this->checkKeysValidity($keys);
        $params = array_merge([$pool], $keys);
        $this->begin();

        try {
            $redisResponse = call_user_func_array([$this->getRedis(), 'hdel'], $params);
        } catch (Exception $e) {
            $redisResponse = false;
            $this->formatException($e);
        }

        return $redisResponse >= 0;
    }

    /**
     * Sets an expiration time (TTL) for an entire Redis Hash pool.
     *
     * This method applies a Time To Live (TTL) to a complete pool using Redis EXPIRE command.
     * When the TTL expires, the entire pool and all keys within it are automatically deleted by Redis.
     *
     * <b>CRITICAL WARNING:</b>
     * Expiration applies to the ENTIRE pool as a single Redis Hash structure. All keys/fields
     * within the pool will expire simultaneously, regardless of when individual entries were added.
     * Redis Hash structures do not support per-field expiration in early Redis versions.
     *
     * <b>Implications:</b>
     * - Setting expiration on a pool affects ALL current and future entries until expiration
     * - Adding new entries to an expiring pool does NOT reset or extend the expiration time
     * - Newer entries added to a pool will expire with older entries
     * - To maintain different TTLs, use separate pools for entries with different lifetimes
     *
     * <b>Usage Examples:</b>
     * <code>
     * // Set 1 hour expiration on user session pool
     * $cache->storeToPool(['session_id' => 'abc123'], 'user_sessions');
     * $cache->setHsetPoolExpiration('user_sessions', 3600);
     *
     * // Set 24 hour expiration on cache pool
     * $cache->storeToPool(['data' => $value], 'daily_cache');
     * $cache->setHsetPoolExpiration('daily_cache', 86400);
     *
     * // Using class constant for default expiration
     * $cache->setHsetPoolExpiration('temp_data', self::HOURS_EXPIRATION_TIME);
     * </code>
     *
     * <b>Best Practices:</b>
     * - Group entries with similar TTL requirements in the same pool
     * - Set expiration immediately after creating/populating a pool
     * - Use separate pools for data with different expiration requirements
     * - Consider using standard Redis keys instead of hashes if per-key expiration is needed
     *
     * Expiration Time is set with this method on the entire Redis Hash : <b>the pool $pool argument given</b>.
     *
     * <b> Caution: expired Hash SET will EXPIRE ALL SUBKEYS as well (even more recent entries)</b>
     *
     * @todo investigate hash field expiration (valkey.io)
     *
     * @param string $pool the pool's name - defaults to DEFAULT_POOL if not specified
     * @param int $expirationTime Time in seconds until the pool expires (must be > 0)
     *                            Defaults to HOURS_EXPIRATION_TIME constant from parent class
     *
     * @return bool True if expiration was set successfully, false otherwise
     *              Returns false if expirationTime <= 0 or if Redis connection fails
     *
     * @throws ConnectionLostException When Redis connection is not available
     *
     * @see storeToPool() Method to add entries to a pool before setting expiration
     * @see https://redis.io/commands/expire/ Redis EXPIRE command documentation
     * @see https://valkey.io/topics/hash-expiration/ Valkey field-level expiration feature
     */
    public function setHsetPoolExpiration(string $pool = self::DEFAULT_POOL, int $expirationTime = self::HOURS_EXPIRATION_TIME): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $redisResponse = -1;

        if ($expirationTime > 0) {
            $redisResponse = $this->getRedis()->expire($pool, $expirationTime);
        }

        return ($redisResponse === 1) ? true : false;
    }
}
