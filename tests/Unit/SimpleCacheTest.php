<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\RedisCache as SUT;

/**
 * Test PSR-16 implementation
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SimpleCacheTest extends \PHPUnit\Framework\TestCase
{
    protected SUT $cache;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        /**
         * @todo mock redis client
         */

    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        unset($this->cache);
    }

    public function testClear()
    {
        $this->assertTrue($this->cache->clear());
    }

    public function testDelete()
    {
        $key = 'test';
        $this->assertTrue($this->cache->delete($key));
    }

    public function deleteMultiple()
    {
        $keys = ['test1', 'test2', 'test3'];
        $this->assertTrue($this->cache->delete($keys));
    }

    public function testGetInexistant()
    {
        $key = 'do:not:exist';
        $expected = 'default';
        $actual = $this->cache->get($key, $expected);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetExistant()
    {
        /**
         *
         * @todo test with values other than strings (serialize)
         */
        $key = 'do:exist';
        $expected = 'a value';
        $actual = $this->cache->get($key, $expected);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetMultipleInexistant()
    {
        $keys = ['do:not:exist'];
        $expected = 'default';
        $actual = $this->cache->get($keys, $expected);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetMultipleExistant()
    {
        /**
         *
         * @todo test with values other than strings (serialize)
         */
        $keys = ['do:exist'];
        $expected = 'a value';
        $actual = $this->cache->get($keys, $expected);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testHas()
    {
        $key = 'test';
        $this->assertTrue($this->cache->has($key));
    }

    public function testHasNot()
    {
        $key = 'test';
        $this->assertFalse($this->cache->has($key));
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {

    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {

    }

}
