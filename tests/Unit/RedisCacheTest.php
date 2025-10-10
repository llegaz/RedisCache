<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

/**
 * <code>RedisEnhancedCache</code> tests
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class RedisCacheTest extends SimpleCacheTest
{
    /**
     * @todo implement this
     */
    public function testFetchFromPool()
    {
        $this->assertTrue(true);
    }

    public function testHasInPool()
    {
        $this->assertTrue(true);
    }

    public function testGetAllCacheStoreAsString()
    {
        $this->assertTrue(true);
    }

/*

    public function getAllCacheStoreAsArray()
    public function getPoolKeys(string $pool): array
    public function getInfo(): array
    public function getTtl(string $key): int
    public function printCacheKeys()
    public function printCacheHash(string $pool, $silent = false): string
    public function setHsetPoolExpiration(string $pool, int $expirationTime = self::HOURS_EXPIRATION_TIME): bool
    public function unserializeFromPool(string $key, string $pool)
    public function serializeToPool(string $key, mixed $data, string $pool): bool
    public function deleteFromPool(array $keys, string $pool): bool
    public function storeToPool(array $values, string $pool): bool
    public function fetchAllFromPool(string $pool): array
*/
}
