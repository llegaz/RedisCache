<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\SimpleCacheTest;
use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Cache\Tests\TestState;
use Psr\SimpleCache\CacheInterface;
use TypeError;

if (!defined('SKIP_INTEGRATION_TESTS')) {
    define('SKIP_INTEGRATION_TESTS', true);
}

/**
 * Test PSR-16 implementation with <b>Persistent Connections</b>
 *
 * check @link https://github.com/php-cache/integration-tests
 */
class CacheIntegrationWithPCTest extends SimpleCacheTest
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
        //dump($this->cache->getRedisClientID()); // persistent co
        if (SKIP_INTEGRATION_TESTS) {
            // don't forget that tests are deleoppers' tools (and not only an approval seal)
            $this->markTestSkipped('INTEGRATION TESTS are skipped by default when executing Units tests only.');
        }
        parent::setUp();
    }

    public static function invalidKeys()
    {
        return array_merge(
            self::invalidArrayKeys(),
            [
                [''],
                ['key with withespace'],
                [self::$bigKey]
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
     *  more TypeError on single operation method (declared with string arguments)
     * @see Psr\SimpleCache\CacheInterface
     *
     * @return array
     */
    public static function invalidTEKeysSingle()
    {
        $closure = function ($a) {
            return $a;
        };

        return array_merge(
            self::invalidTEKeys(),
            [
                [2], // TypeError
                [new \stdClass()], // TypeError
                [2.5], // TypeError
                [1337], // TypeError
                [$closure], // TypeError
            ]
        );
    }

    public static function invalidTEKeys()
    {
        return [
            [['array']],
            [[1 => 'array', 2 => 'again']],
            [null],
        ];
    }

    /**
     * @return array
     */
    public static function invalidTtl()
    {
        $closure = function ($a) {
            return $a;
        };

        return [
            [''],
            [true],
            [false],
            ['abc'],
            [2.5],
            [' 1'], // can be casted to a int
            ['12foo'], // can be casted to a int
            ['025'], // can be interpreted as hex
            [$closure],
            [new \stdClass()],
            [['array']],
        ];
    }

    /**
     * @dataProvider invalidTEKeysSingle
     */
    #[DataProvider('invalidTEKeysSingle')]
    public function testGetInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->get($key);
    }

    /**
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
    public function testGetMultipleInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        //$this->expectException('\TypeError');
        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $result = $this->cache->getMultiple(['key1', $key, 'key2']);
    }

    public function testGetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $result = $this->cache->getMultiple('key');
    }

    /**
     * @dataProvider invalidTEKeysSingle
     */
    #[DataProvider('invalidTEKeysSingle')]
    public function testSetInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->set($key, 'foobar');
    }

    /**
     * @dataProvider invalidTEKeysSingle
     */
    #[DataProvider('invalidTEKeysSingle')]
    public function testHasInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->has($key);
    }

    /**
     * @dataProvider invalidTEKeysSingle
     */
    #[DataProvider('invalidTEKeysSingle')]
    public function testDeleteInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->delete($key);
    }

    /**
     * meh ?
     *
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
    public function testDeleteMultipleInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        //$this->expectException('\TypeError');
        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->deleteMultiple(['key1', $key, 'key2']);
    }

    public function testDeleteMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->deleteMultiple('key');
    }

    /**
     * @dataProvider invalidTtl
     */
    #[DataProvider('invalidTtl')]
    public function testSetInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->set('key', 'value', $ttl);
    }

    public function testSetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->setMultiple('key');
    }

    /**
     * @dataProvider invalidTtl
     */
    #[DataProvider('invalidTtl')]
    public function testSetMultipleInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->setMultiple(['key' => 'value'], $ttl);
    }

    public function testSetMultipleWithIntegerArrayKey()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple(['00' => 'value0']);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('00'));
    }

    public function createSimpleCache(): CacheInterface
    {
        $sut = new SUT('localhost', 6379, null, 'tcp', 0, true);
        $client = $sut->getRedis();

        /**
         * @todo handle persistent conn id
         */
        if (!TestState::$pid) {

        }
        /**
         * display adapter class used (Predis or php-redis)
         *
         * @todo work to display this before php units and test suite start
         */
        if (!TestState::$adapterClassDisplayed) {
            TestState::$adapterClassDisplayed = true;
            fwrite(STDERR, PHP_EOL);
            dump($client->toString() . ' adapter used.');
            /*   $this->assertTrue(TestState::$adapterClassDisplayed);
               $this->assertTrue($sut instanceof \LLegaz\Redis\RedisAdapter);
               $this->assertTrue($client instanceof \LLegaz\Redis\RedisClientInterface);*/
        }

        return $sut;
    }

}
