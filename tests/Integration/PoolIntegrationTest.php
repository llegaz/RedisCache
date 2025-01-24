<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\CachePoolTest;
use LLegaz\Cache\Pool\CacheEntryPool as SUT;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Test PSR-6 implementation
 *
 * check @link https://github.com/php-cache/integration-tests
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class PoolIntegrationTest extends CachePoolTest
{
    public function createCachePool(): CacheItemPoolInterface
    {
        return new SUT();
    }

}
