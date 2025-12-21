<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\CachePoolTest;
use LLegaz\Cache\Pool\CacheEntryPool as SUT;
use LLegaz\Cache\RedisEnhancedCache;
use LLegaz\Cache\Tests\TestState;
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

    public static function invalidKeys()
    {
        $bigKey = '';
        //36 KB
        for ($i = 36864; $i > 0; $i--) {
            $bigKey .= 'a';
        }

        return array_merge(
            self::invalidArrayKeys(),
            [
                [''],
                ['key with withespace'],
                [$bigKey]
            ]
        );
    }

    /**
     * Yup this isn't optimal but I've only 2 restricted key scenario when keys
     * are forced into strings type
     * (which is the case thanks to PSR-6 v3 from <b>psr/cache</b> repository).
     *
     * @link https://github.com/php-fig/cache The <b>psr/cache</b> repository.
     *
     * @return array
     */
    public static function invalidArrayKeys()
    {
        return [
            ['key with withespace'],
            [''],
        ];
    }

    /**
     *  @todo TypeError suite tests needed see <code>CacheIntegrationTest</code> class
     */

    /**
     *
     * @return CacheItemPoolInterface
     */
    public function createCachePool(): CacheItemPoolInterface
    {
        $cache = new RedisEnhancedCache();

        /**
         * display adapter class used (Predis or php-redis)
         *
         * @todo work to display this before php units and test suite start
         */
        if (!TestState::$adapterClassDisplayed) {
            TestState::$adapterClassDisplayed = true;
            fwrite(STDERR, PHP_EOL);
            dump($cache->getRedis()->toString() . ' adapter used.');
        }

        return new SUT($cache);
    }
}
