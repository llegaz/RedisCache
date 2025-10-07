<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\CachePoolTest;
use LLegaz\Cache\Pool\CacheEntryPool as SUT;
use Psr\Cache\CacheItemPoolInterface;

if (!defined('SKIP_INTEGRATION_TESTS')) {
    define('SKIP_INTEGRATION_TESTS', true);
}

/**
 * Test PSR-6 implementation
 *
 * check @link https://github.com/php-cache/integration-tests
 */
class PoolIntegrationTest extends CachePoolTest
{
    /**
     * @before
     */
    #[Before]
    public function setupService()
    {
        if (SKIP_INTEGRATION_TESTS) {
            // don't forget that tests are deleoppers' tools (and not only an approval seal)
            $this->markTestSkipped('INTEGRATION TESTS are skipped by default when executing Units tests only.');
        }
        parent::setupService();
    }

    protected function setUp(): void
    {
        if (SKIP_INTEGRATION_TESTS) {
            // don't forget that tests are deleoppers' tools (and not only an approval seal)
            $this->markTestSkipped('INTEGRATION TESTS are skipped by default when executing Units tests only.');
        }
        parent::setUp();
    }

    public function createCachePool(): CacheItemPoolInterface
    {
        $cache = new \LLegaz\Cache\RedisEnhancedCache();

        return new SUT($cache);
    }
}
