<?php

declare(strict_types=1); // @todo maybe add to Cache\IntegrationTests\SimpleCacheTest for a PR proposal

namespace LLegaz\Cache\Tests\Integration;

use Cache\IntegrationTests\SimpleCacheTest;
use LLegaz\Cache\RedisCache as SUT;
use Psr\SimpleCache\CacheInterface;
use TypeError;

if (!defined('SKIP_INTEGRATION_TESTS')) {
    define('SKIP_INTEGRATION_TESTS', true);
}

/**
 * Test PSR-16 implementation
 *
 * check @link https://github.com/php-cache/integration-tests
 */
class CacheIntegrationTest extends SimpleCacheTest
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
        return array_merge(
            self::invalidArrayKeys(),
            [
                [''],
            ]
        );
    }

    public static function invalidArrayKeys()
    {
        return [
            ['a'],
            ['b'],
            ['c'],
            ['1'],
            ['2'],
            ['3'],
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
        return array_merge(
            self::invalidTEKeys(),
            [
                [2], // TypeError
                [new \stdClass()], // TypeError
                [2.5], // TypeError
                [1337], // TypeError
            ]
        );
    }

    public static function invalidTEKeys()
    {
        return [
            [['array']],
            [[1 => 'array', 2 => 'again']],
            [true],
            [false],
            [null],
        ];
    }

    /**
     * @return array
     */
    public static function invalidTtl()
    {
        return [
            [''],
            [true],
            [false],
            ['abc'],
            [2.5],
            [' 1'], // can be casted to a int
            ['12foo'], // can be casted to a int
            ['025'], // can be interpreted as hex
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
        return new SUT();
    }

}
