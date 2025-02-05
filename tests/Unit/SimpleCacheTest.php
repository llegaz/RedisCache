<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Redis\RedisClientInterface;
use LLegaz\Redis\Tests\Unit\RedisAdapterTest;
use Predis\Response\Status;

/**
 * Test PSR-16 implementation
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SimpleCacheTest extends RedisAdapterTest
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
        parent::setUp();

        $this->cache = new SUT(
            RedisClientInterface::DEFAULTS['host'],
            RedisClientInterface::DEFAULTS['port'],
            null,
            RedisClientInterface::DEFAULTS['scheme'],
            RedisClientInterface::DEFAULTS['database'],
            false,
            $this->predisClient
        );
        $this->assertDefaultContext();

    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        unset($this->cache);
    }

    public function testClearAll()
    {
        $this->predisClient->expects($this->once())
            ->method('flushall')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear(true));
    }

    public function testClear()
    {
        $this->predisClient->expects($this->once())
            ->method('flushdb')
            ->willReturn(new Status('OK'))
        ;
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

    public function testSet()
    {
        $key = 'test';
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb'));
    }

    /**
     *
     * @todo check doc for array (keys => values) and also complete arrays,
     *       adding more elements, here and there (above)
     */
    public function testSetMultiple()
    {
        $keys = ['do:exist'];
        $actual = $this->cache->setMultiple($keys);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);

    }

    /**
     * @todo test TTL here
     */
    public function testSetWithTtl()
    {
        $key = 'testTTL';
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb', 1337));
    }

}
