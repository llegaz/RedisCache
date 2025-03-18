<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use LLegaz\Cache\Exception\InvalidKeyException;

/**
 * Class RedisEnhancedCache
 * built on top of PSR-16 implementation to complete it for PSR-16 CacheEntries Pools based on Redis Hashed
 *
 * @todo test pool name parameter is given if not maybe throw error or fallback on SimpleCache PSR-16 ?
 *
 * @package RedisCache
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisEnhancedCache extends RedisCache
{
    /**
     * @todo rework this
     *
     *
     * @param int|string|array $key
     * @param string $pool the pool's name
     * @return mixed
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     * @throws LLegaz\Cache\Exception\InvalidKeyException
     */
    public function fetchFromPool(mixed $key, string $pool): mixed
    {
        $this->begin();

        /**
         * @todo rework this
         */
        switch (gettype($key)) {
            case 'integer':
            case 'string':
                return $this->getRedis()->hget($pool, $key) ?? false;
            case 'array':
                $data = $this->getRedis()->hmget($pool, $key);

                return array_combine(array_values($key), array_values($data));

            default:
                throw new InvalidKeyException('Invalid Parameter');
        }

        return false;
    }

    /**
     *
     * @param string $key
     * @param string $pool
     * @return bool
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function hasInPool(string $key, string $pool): bool
    {
        $this->checkKeyValidity($key);
        $this->begin();

        try {
            $redisResponse = $this->getRedis()->hexists($pool, $key);
        } catch (\Throwable $t) {
            $redisResponse = null;
            $this->formatException($t);
        } finally {
            return ($redisResponse === 1) ? true : false;
        }
    }

    /**
     * @todo rework this
     *
     *
     * @param string $pool the pool's name
     * @return array
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     */
    public function fetchAllFromPool(string $pool): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->hgetall($pool);
    }

    /**
     * @todo rework this
     *
     *
     * @param array $values A flat array of key => value pairs to store in GIVEN POOL name
     * @param string $pool the pool name
     * @return bool True on success
     * @throws LLegaz\Redis\Exception\ConnectionLostException
     * @throws LLegaz\Redis\Exception\LocalIntegrityException
     */
    public function storeToPool(array $values, string $pool): bool
    {
        $this->begin();

        /**
         * @todo enhance keys / values treatment (see / homogenize with RedisCache::setMultiple and RedisCache::checkKeysValidity)
         * @todo need better handling on serialization and its reverse method in fetches.
         */
        array_walk($values, function (&$value) {
            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            }
        });

        return $this->getRedis()->hmset($pool, $values) == 'OK';
    }

    /**
     * @todo rework this
     *
     * @param array $keys
     * @param string $pool the pool's name
     * @return bool True on success
     * @throws ConnectionLostException
     */
    public function deleteFromPool(mixed $keys, string $pool): bool
    {
        $cnt = 1;
        if (is_string($keys)) {
            $this->checkKeyValidity($keys);
        } else {
            $this->checkKeysValidity($keys);
            $cnt = count($keys);
        }
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        try {
            $redisResponse = $this->getRedis()->hdel($pool, $keys);
        } catch (Exception $e) {
            $redisResponse = false;
            $this->formatException($e);
        }


        return $redisResponse === $cnt;
    }

    /**
     * Here we use the Hash implementation from redis. Expiration Time is set with the setHsetPoolExpiration method
     * on the entire Hash Set HASH_DB_PREFIX. $suffix (private HSET Pool in Redis, specified with $suffix
     * with those methods you can store and retrieve specific data linked together in a separate data set)
     *
     * @todo rework this
     *
     * @param string $key
     * @param mixed  $data string, object and array are preferred
     * @param string $pool the pool's name
     * @return bool
     * @throws ConnectionLostException
     */
    public function serializeToPool(string $key, mixed $data, string $pool): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $serializeData = serialize($data);

        if (!empty($serializeData)) {
            $redisResponse = $this->getRedis()->hset($pool, $key, $serializeData);

            return ($redisResponse >= 0) ? true : false;
        }

        return false;
    }

    /**
     * Here we use the Hash implementation from redis. Expiration Time is set with the setHsetPoolExpiration method
     * on the entire Hash Set HASH_DB_PREFIX. $suffix (private HSET Pool in Redis, specified with $suffix
     * with those methods you can store and retrieve specific data linked together in a separate data set)
     *
     * @todo rework this
     *
     * @param string $key
     * @param string $pool the pool's name
     * @return mixed <p> The converted value is returned, and can be a boolean,
     * integer, float, string,
     * array or object.
     * </p>
     * <p>
     * In case the passed string is not unserializable, <b>FALSE</b> is returned and
     * <b>E_NOTICE</b> is issued.
     * @throws ConnectionLostException
     */
    public function unserializeFromPool(string $key, string $pool)
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return unserialize($this->getRedis()->hget($pool, $key));
    }

    /**
     * @todo rework this
     *
     *
     *
     * Expiration Time is set with this method on the entire Hash Set <b>HASH_DB_PREFIX</b> concatenate
     * with a private <b>$pool</b>
     *
     * <b> Caution: expired Hash SET will EXPIRE ALL SUBKEYS as well (even more recent entries)</b>
     *
     * @param string $pool the pool's name
     * @param int    $expirationTime
     * @return bool
     * @throws ConnectionLostException
     */
    public function setHsetPoolExpiration(string $pool, int $expirationTime = self::HOURS_EXPIRATION_TIME): bool
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

    /**
     * -- debug utilities methods --
     *
     *
     *
     *
     *
     *
     *
     *
     * @todo rework all these
     */
    public function printCacheHash(string $pool, $silent = false): string
    {
        $data = $this->fetchAllFromPool($pool);

        /**
         * @todo - rework this
         */
        $toReturn = '';

        foreach ($data as $key => $value) {
            if ($silent) {
                $toReturn .= sprintf('Key:  -  %s  -' . PHP_EOL . 'Value: ' . PHP_EOL . '%s' . PHP_EOL . PHP_EOL, $key, unserialize($value) ?? $value);
            } else {
                echo $key . '  -  ' . $value . PHP_EOL;
            }
        }

        return $toReturn;
    }

    /**
     * @todo rework this
     *
     * print only cache keys set
     *
     * @return null
     * @throws ConnectionLostException
     */
    public function printCacheKeys()
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        foreach ($this->getRedis()->keys('*') as $key => $value) {
            echo $key . '  -  ' . $value . PHP_EOL;
        }
    }

    /**
     * @todo rework this
     *
     * @param string $key
     * @return int
     */
    public function getTtl(string $key): int
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->ttl($key);
    }

    /**
     * @todo rework this
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getInfo(): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->info();
    }

    /**
     * @param string $pool the pool's name
     * @return array
     * @throws ConnectionLostException
     */
    public function getPoolKeys(string $pool): array
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->getRedis()->hkeys($pool);
    }


    /**
     * @todo rework this
     *
     * key => value array is returned corresponding accurately to the redis cache set
     *
     * @return array
     * @throws ConnectionLostException
     */
    public function getAllCacheStoreAsArray()
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        $keys = $this->getAllkeys();
        $keys = array_values($keys);
        $result = [];

        if (count($keys)) {
            $result = $this->getMultiple($keys);
        }

        return $result;
    }

    /**
     * @todo rework this
     *
     * print everything in Cache Store for the selected Database
     * (except HSET entries)
     *
     * @return string
     * @throws ConnectionLostException
     */
    public function getAllCacheStoreAsString(): string
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }
        $toReturn = '';
        $nl       = PHP_EOL;

        /** @todo disgusting smell to get rid of
         *       (better reusing getAllCacheStoreAsArray above method)
         */
        foreach ($this->getAllkeys() as $key) {
            try {
                $value = $this->get($key);
                $toReturn .= sprintf(
                    'Key: %s  -  Value:%sTTL = %s',
                    $key,
                    $nl . $value . $nl,
                    $this->getTtl($key) . $nl . $nl
                );
            } catch (Throwable $t) {
                continue;
            }
        }

        return $toReturn;
    }

    /**
     *
     * disclaimer: <b>DO NOT USE EXECPT IN DEBUGGING SCENARIO</b> this redis call is too intensive in O(n) complexity
     * so the more keys the more blocking it is for all redis clients trying to access the redis db
     *
     * @return array all the keys in redis (for a selected db ?)
     */
    private function getAllkeys(): array
    {
        return $this->getRedis()->keys('*');
    }
}
