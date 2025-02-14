<?php

declare(strict_types=1); // @todo add to SimpleCacheTest

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
        //dump('setup service');        sleep(1);
        if (SKIP_INTEGRATION_TESTS) {
            // don't forget that tests are deleoppers' tools (and not only an approval seal)
            $this->markTestSkipped('INTEGRATION TESTS are skipped by default when executing Units tests only.');
        }
        parent::setupService();
    }

    protected function setUp(): void
    {
        //dump('setp');        sleep(1);
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
            [''],
        ];
    }

    public static function invalidTEKeys()
    {
        return array_merge(
            self::invalidArrayTEKeys(),
            [
                [2], // TypeError
            ]
        );
    }

    public static function invalidArrayTEKeys()
    {
        return [
            [true], // TypeError
            [false], // TypeError
            [null], // TypeError
            [2.5], // TypeError
            [1337], // TypeError
            [new \stdClass()], // TypeError
            [['array']], // TypeError
        ];
    }

    /**
     * @return array
     */
    public static function invalidTtl()
    {
        return [
            //[''],
            [true],
            [false],
            //['abc'],
            [2.5],
            [' 1'], // can be casted to a int
            //['12foo'], // can be casted to a int
            ['025'], // can be interpreted as hex
            //[new \stdClass()],
            //[['array']],
        ];
    }

    /**
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
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

        $this->expectException('\TypeError');
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
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
    public function testSetInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->set($key, 'foobar');
    }

    /**
     * @dataProvider invalidArrayTEKeys
     */
    #[DataProvider('invalidArrayTEKeys')]
    public function testSetMultipleInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $values = function () use ($key) {
            yield 'key1' => 'foo';
            yield $key => 'bar';
            yield 'key2' => 'baz';
        };
        $this->expectException('\TypeError');
        $this->cache->setMultiple($values());
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
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
    public function testHasInvalidTEKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('\TypeError');
        $this->cache->has($key);
    }

    /**
     * @dataProvider invalidTEKeys
     */
    #[DataProvider('invalidTEKeys')]
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

        $this->expectException('\TypeError');
        $this->cache->deleteMultiple(['key1', $key, 'key2']);
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


    public function createSimpleCache(): CacheInterface
    {
        return new SUT();
    }

}
