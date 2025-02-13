<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Redis\PredisClient;
use LLegaz\Redis\RedisClientInterface;
use LLegaz\Redis\Tests\RedisAdapterTestBase;
use Predis\Response\Status;

/**
 * Test PSR-16 implementation
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SimpleCacheTest extends RedisAdapterTestBase
{
    protected SUT $cache;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->predisClient = $this->getMockBuilder(PredisClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['disconnect', 'executeCommand', 'transaction',])
            ->addMethods([
                'ping',
                'select',
                'client',
                'del',
                'exists',
                'expire',
                'get',
                'mget',
                'flushall',
                'flushdb',
                'set',
                'mset',
                ])
            ->getMock()
        ;
        $this->predisClient
            ->expects($this->any())
            ->method('disconnect')
            ->willReturnSelf()
        ;
        $this->predisClient
            ->expects($this->any())
            ->method('ping')
            ->willReturn(new Status('PONG'))
        ;

        $this->redisAdapter = $this->cache = new SUT(
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
        $this->predisClient->expects($this->exactly(1))
            ->method('client')
            ->with('list')
            ->willReturn([['id' => 1337, 'db' => 0, 'cmd' => 'client']])
        ;
        $this->predisClient->expects($this->once())
            ->method('flushdb')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear());
    }

    public function testDelete()
    {
        $key = 'test';
        $this->predisClient->expects($this->exactly(1))
            ->method('del')
            ->with($key)
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->delete($key));
    }

    public function deleteMultiple()
    {
        $keys = ['test1', 'test2', 'test3'];
        $this->predisClient->expects($this->exactly(1))
            ->method('del')
            ->with(trim(implode(' ', $keys)))
            ->willReturn(new Status(3))
        ;
        $this->assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testGetInexistant()
    {
        $key = 'do:not:exist';
        $expected = 'default';
        $this->predisClient->expects($this->exactly(1))
            ->method('get')
            ->with($key)
            ->willReturn(false)
        ;
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
        $this->predisClient->expects($this->exactly(1))
            ->method('get')
            ->with($key)
            ->willReturn($expected)
        ;
        $actual = $this->cache->get($key);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetExistantButNull()
    {
        /**
         *
         * @todo test with values other than strings (serialize)
         */
        $key = 'do:exist';
        $expected = NULL;
        $this->predisClient->expects($this->exactly(1))
            ->method('get')
            ->with($key)
            ->willReturn("N;")
        ;
        $actual = $this->cache->get($key);
        $this->assertNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetMultipleInexistant()
    {
        $keys = ['do:not:exist1', 'do:not:exist2', 'do:not:exist3'];
        $expected = 'default';
        $this->predisClient->expects($this->exactly(1))
            ->method('mget')
            ->with($keys)
            ->willReturn([false, false, false])
        ;
        $actuals = $this->cache->getMultiple($keys, $expected);
        $this->assertIsArray($actuals);
        foreach ($actuals as $key => $actual) {
            $this->assertTrue(in_array($key, $keys));
            $this->assertIsString($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    public function testGetMultipleExistant()
    {
        /**
         *
         * @todo test with values other than strings (serialize)
         */
        $keys = ['do:exist1', 'do:exist2', 'do:exist3'];
        $this->predisClient->expects($this->exactly(1))
            ->method('mget')
            ->with($keys)
            ->willReturn(['value1', 'value2', 'value3'])
        ;
        $actuals = $this->cache->getMultiple($keys);
        $this->assertIsArray($actuals);
        foreach ($actuals as $key => $actual) {
            $this->assertTrue(in_array($key, $keys));
            $this->assertIsString($actual);
            $this->assertTrue(str_contains($actual, 'value'));
        }
    }

    public function testHas()
    {
        $key = 'test';
        $this->predisClient->expects($this->exactly(1))
            ->method('exists')
            ->with($key)
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->has($key));
    }

    public function testHasNot()
    {
        $key = 'test';
        $this->predisClient->expects($this->exactly(1))
            ->method('exists')
            ->with($key)
            ->willReturn(0)
        ;
        $this->assertFalse($this->cache->has($key));
    }

    public function testSet()
    {
        $key = 'test';
        $this->predisClient->expects($this->exactly(1))
            ->method('set')
            ->with($key)
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb'));
    }

    /**
     *
     * @todo rework parameters
     */
    public function testSetMultiple()
    {
        $values = ['do:exist1' => 'value1', 'do:exist2' => 'value2'];
        $this->predisClient->expects($this->exactly(1))
            //->method('mset')
            ->method('transaction') // predis case
            //->with($values)
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->setMultiple($values));
    }

    /**
     * @todo test TTL here
     */
    public function testSetWithTtl()
    {
        $key = 'testTTL';
        $this->predisClient->expects($this->exactly(1))
            ->method('set')
            ->with($key, 'bbbbbbbbbbbbbbbbbbbb')
            ->willReturn(new Status('OK'))
        ;
        $this->predisClient->expects($this->exactly(1))
            ->method('expire')
            ->with($key, 1337)
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb', 1337));
    }

}
