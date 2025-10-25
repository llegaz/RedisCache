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
 *
 * Here we use the Hash implementation from redis. Expiration Time is set with the setHsetPoolExpiration method
 * on the entire Hash Set HASH_DB_PREFIX. $suffix (private HSET Pool in Redis, specified with $suffix
 * with those methods you can store and retrieve specific data linked together in a separate data set)
 *
 *
 *
 * It is to be noted that we use different terminology here  from Redis project in the case of a HASH.
 * for us : pool = key and key = field, but it is only semantic differences...
 * ------------------------------------------------------------------------------------------------------------
 *
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
        /**
         * check keys arguments are valid, and values are all stored as <b>strings</b>
         *
         * @todo omg are you that tired bro ? replace this shit with a foreach, :face_palm:
         */
        $self = $this;
        array_walk($values, function (&$value, $key) use ($self) {

            $self->checkKeyValuePair($key, $value);
        });

        //dump('store to pool :', $values);

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
                /***
                 * @todo investiguate
                 */
                dd('I think it could be problematic');
            }
            if ($value) {
                dump('store to pool : ' . $key . ' - ' . $value);

                //hset should returns the number of fields stored for a single key (always one here)
                return $this->getRedis()->hset($pool, $key, $value) === 1;
            }
        }

        return false;
    }

    /**
     *
     *
     *
     * @todo rework this
     *
     * @hint Redis return mostly strings with hget or hmget, maybe we should use serialize to preserve type
     *
     * @todo implement serialize with serializeToPool and cable those methods for the <code>CacheEntryPool</code> class to use it
     *
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

        /***
         * finish to implment unserializes properly you mofo
         * @todo remove hexist
         * use :
         */
        //return unserialize($this->getRedis()->hget($pool, $key));

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
                    $self = $this;
                    $label = self::DOES_NOT_EXIST;
                    /**
                     * @todo same remark here use a freaking foreach !
                     */
                    array_walk($data, function (&$value) use ($self, $label) {
                        if (is_string($value)) {
                            $value = $self->setCorrectValue($value);
                        } else {
                            $value = $label;
                        }
                    });
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
     *
     * @param array $keys
     * @param string $pool the pool's name
     * @return bool True on success
     * @throws ConnectionLostException
     */
    public function deleteFromPool(array $keys, string $pool): bool
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
        dump('from printCacheHash', $data);


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

        dump('from printCacheHash to return - ' . (strlen($toReturn) ? $toReturn : '$toReturn  is empty'));

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
