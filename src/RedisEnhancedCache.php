<?php

declare(strict_types=1);

namespace LLegaz\Cache;

/**
 * Class RedisEnhancedCache
 * built on top of PSR-16 implementation to complete it for PSR-16 CacheEntries Pools based on Redis Hashed
 *
 *
 * @package RedisCache
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisEnhancedCache extends RedisCache
{
    /**
     * clear a pool entirely
      *
      * @param string $pool the pool's name
      */
    public function clearPool(string $pool)
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $this->set($pool, null);
        $this->delete($pool);
    }
    /**
     * @todo rework this
     *
     *
     * @param string|array $key
     * @param string $pool the pool's name
     * @return array|bool|string
     * @throws NotConnectedException | LogicException
     */
    public function fetchFromPool(mixed $key, string $pool)
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        switch (gettype($key)) {
            case 'integer':
            case 'string':
                return $this->redis->hget($pool, $key) ?? false;
            case 'array':
                $data = $this->redis->hmget($pool, $key);

                return array_combine(array_values($key), array_values($data));

            default:
                throw new \LogicException('Invalid Parameter');
        }

        return false;
    }

    /**
     * @todo rework this
     *
     *
     * @param string $pool
     * @return array
     * @throws NotConnectedException
     */
    public function fetchAllFromPool(string $pool)
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->redis->hgetall($pool);
    }

    /**
     * @todo rework this
     *
     *
     * @param array $values A flat array of key => value pairs to store
     * in GIVEN POOL or DEFAULT
     * @param string $pool
     * @return bool True on success
     * @throws NotConnectedException
     */
    public function storeToPool(array $values, string $pool = ''): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        array_walk($values, function (&$value) {
            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            }
        });

        return $this->redis->hmset(
            $pool,
            $values
        ) == 'OK';
    }

    /**
     * @todo rework this
     *
     * @param array $keys
     * @param string $pool
     * @return bool True on success
     * @throws NotConnectedException
     */
    public function deleteFromPool(mixed $keys, string $pool = ''): bool
    {
        if (is_string($keys)) {
            $this->checkKeyValidity($keys);
        } else {
            $this->checkKeysValidity($keys);
        }
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return $this->redis->hdel($pool, $keys) == count($keys);
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
     * @param string $pool
     * @return bool
     * @throws NotConnectedException
     */
    public function serializeToPool(string $key, mixed $data, string $pool = ''): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $serializeData = serialize($data);

        if (!empty($serializeData)) {
            $redisResponse = $this->redis->hset($pool, $key, $serializeData);

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
     * @param string $pool
     * @return mixed <p> The converted value is returned, and can be a boolean,
     * integer, float, string,
     * array or object.
     * </p>
     * <p>
     * In case the passed string is not unserializable, <b>FALSE</b> is returned and
     * <b>E_NOTICE</b> is issued.
     * @throws NotConnectedException
     */
    public function unserializeFromPool(string $key, string $pool = '')
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        return unserialize($this->redis->hget($pool, $key));
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
     * @param string $pool
     * @param int    $expirationTime
     * @return bool
     * @throws NotConnectedException
     */
    public function setHsetPoolExpiration(string $pool = '', int $expirationTime = self::HOURS_EXPIRATION_TIME): bool
    {
        if (!$this->isConnected()) {
            $this->throwCLEx();
        }

        $redisResponse = -1;

        if ($expirationTime > 0) {
            $redisResponse = $this->redis->expire($pool, $expirationTime);
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
    public function printCacheHash(string $pool = '', $silent = false): string
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
     * @throws NotConnectedException
     */
    public function printCacheKeys()
    {
        $this->checkClientConnection();

        foreach ($this->redis->keys('*') as $key => $value) {
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
        $this->checkClientConnection();

        return $this->redis->ttl($key);
    }

    /**
     * @todo rework this
     *
     * @return array
     * @throws NotConnectedException
     */
    public function getInfo(): array
    {
        $this->checkClientConnection();

        return $this->redis->info();
    }

    /**
     * @param string $pool
     * @return array
     * @throws NotConnectedException
     */
    public function getPoolKeys(string $pool = ''): array
    {
        $this->checkClientConnection();

        return $this->redis->hkeys($pool);
    }


    /**
     * @todo rework this
     *
     * key => value array is returned corresponding accurately to the redis cache set
     *
     * @return array
     * @throws NotConnectedException
     */
    public function getAllCacheStoreAsArray()
    {
        $this->checkClientConnection();
        $keys   = array_values($this->redis->keys('*'));
        $result = [];

        if (count($keys)) {
            $result = $this->fetch($keys);
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
     * @throws NotConnectedException
     */
    public function getAllCacheStoreAsString(): string
    {
        $this->checkClientConnection();
        $toReturn = '';
        $nl       = PHP_EOL;

        foreach ($this->redis->keys('*') as $key) {
            try {
                $value = $this->fetch($key);
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
     * @return string
     */
    public function getRedisClientID(): string
    {
        return spl_object_hash($this->redis);
    }

    public function setTimeOut(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * PHPUnit DI setter
     */
    public function setRedisClient(\Predis\Client $client): void
    {
        $this->redis = $client;
    }
}
