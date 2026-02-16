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
 * Test PSR-6 implementation with <b>Persistent Connections</b>
 *
 * check @link https://github.com/php-cache/integration-tests
 */
class PoolIntegrationWithPCTest extends CachePoolTest
{
    private static string $bigKey = '';

    public static function setUpBeforeClass(): void
    {
        //36 KB
        for ($i = 36864; $i > 0; $i--) {
            self::$bigKey .= 'a';
        }
        parent::setUpBeforeClass();
    }

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
                [self::$bigKey],
            ]
        );
    }


    /**
     * We have less restricted key scenarios with keys forced into strings type
     * (which is the case thanks to PSR-16 v3 from <b>psr/simple-cache</b> repository).
     *
     * @link https://github.com/php-fig/simple-cache The <b>psr/simple-cache</b> repository.
     *
     * @return array
     */
    public static function invalidArrayKeys()
    {
        return [
            [''],
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand\\str'],
            ['key with withespace'],
            ['key   with    tabs'],
            ['key' . PHP_EOL . 'with' . PHP_EOL . 'CRLF'],
            ['key\nFLUSHALL'], // insecure key
            [self::$bigKey],
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
        $cache = new RedisEnhancedCache('localhost', 6379, null, 'tcp', 0, true);

        /**
         * @todo handle persistent conn id
         */
        if (!TestState::$pid) {

        }
        /**

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
