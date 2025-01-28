<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\SimpleCacheTest;
use LLegaz\Cache\RedisCache as SUT;
use Psr\SimpleCache\CacheInterface;

/**
 * Test PSR-16 implementation
 *
 * check @link https://github.com/php-cache/integration-tests
 */
class CacheIntegrationTest extends SimpleCacheTest
{
    public function createSimpleCache(): CacheInterface
    {
        return new SUT();
    }

}
