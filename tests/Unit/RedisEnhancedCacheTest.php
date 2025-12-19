<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

/**
 * <code>RedisEnhancedCache</code> tests
 *
 *
 *
 * @todo implement this
 *
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisEnhancedCacheTest extends SimpleCacheTest
{
    /**
     * @todo Test RedisEnhancedCache::fetchFromPool  in depth !
     *
     * that is to say: HMGET
     */
    public function testFetchFromPool()
    {
        $this->assertTrue(true);
    }

    public function testHasInPool()
    {
        $this->assertTrue(true);
    }

    /*
        public function setHsetPoolExpiration(string $pool, int $expirationTime = self::HOURS_EXPIRATION_TIME): bool
        public function deleteFromPool(array $keys, string $pool): bool
        public function storeToPool(array $values, string $pool): bool    => multiple scenarios !! (test with RedisCache::DOES_NOT_EXIST var)
    */
}
