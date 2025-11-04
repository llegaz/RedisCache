<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Redis\RedisClient;
use LLegaz\Redis\RedisClientInterface;
use LLegaz\Redis\Tests\RedisAdapterTestBase;
use Predis\Response\Status;

/**
 * Test PSR-16 implementation with phpredis client
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SimpleCacheRCTest extends RedisAdapterTestBase
{
    protected SUT $cache;

    protected RedisClientInterface $redisClient;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        if (!in_array('redis', get_loaded_extensions())) {
            $this->markTestSkipped('Skip those units as php-redis extension is not loaded.');
        }

        parent::setUp();
        $this->redisClient = $this->getMockBuilder(RedisClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'client',
                'disconnect',
                'isConnected',
                'launchConnection',
                'ping',
                'select',
                'del',
                'exists',
                'expire',
                'get',
                'mget',
                'flushall',
                'flushdb',
                'set',
                'mset',
                'multi',
                'exec',
                ])
            ->getMock()
        ;
        $this->redisClient
            ->expects($this->any())
            ->method('isConnected')
            ->willReturn(true)
        ;
        $this->redisClient
            ->expects($this->any())
            ->method('disconnect')
            ->willReturnSelf()
        ;
        $this->redisClient
            ->expects($this->any())
            ->method('ping')
            ->willReturn(true)
        ;
        $this->cache = $this->redisAdapter = new SUT(
            RedisClientInterface::DEFAULTS['host'],
            RedisClientInterface::DEFAULTS['port'],
            null,
            RedisClientInterface::DEFAULTS['scheme'],
            RedisClientInterface::DEFAULTS['database'],
            false,
            $this->redisClient
        );
        /**
         * expect 1 more client command (list) because of the integrity check
         * (units are in forced paranoid mode for now)
         *
         * @todo mb rework this here and in adapter project
         */
        \LLegaz\Redis\RedisClientsPool::setOracle($this->defaults);
        $this->assertDefaultContext();

    }

    protected function tearDown(): void
    {
        unset($this->cache);
    }

    public function testClearAll()
    {
        $this->redisClient->expects($this->once())
            ->method('flushall')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear(true));
    }

    public function testClear()
    {
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('flushdb')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear());
    }

    public function testDelete()
    {
        $key = 'test';
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('del')
            ->with($key)
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->delete($key));
    }

    public function deleteMultiple()
    {
        $keys = ['test1', 'test2', 'test3'];
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
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
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
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
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($expected)
        ;
        $actual = $this->cache->get($key);
        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetFalse()
    {
        $key = 'do:exist';
        $expected = false;
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn('b:0;')
        ;
        $actual = $this->cache->get($key);
        $this->assertIsBool($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetNull()
    {
        /**
         *
         * @todo test with values other than strings (serialize)
         */
        $key = 'do:exist';
        $expected = null;
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn('N;')
        ;
        $actual = $this->cache->get($key);
        $this->assertNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetMultipleInexistant()
    {
        $keys = ['do:not:exist1', 'do:not:exist2', 'do:not:exist3'];
        $expected = 'default';
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
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
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
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
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('exists')
            ->with($key)
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->has($key));
    }

    public function testHasNot()
    {
        $key = 'test';
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('exists')
            ->with($key)
            ->willReturn(0)
        ;
        $this->assertFalse($this->cache->has($key));
    }

    public function testSet()
    {
        $key = 'test';
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('set')
            ->with($key)
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb'));
    }

    public function testSetMultiple()
    {
        $values = ['do:exist1' => 'value1', 'do:exist2' => 'value2'];

        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn(true);
        $this->redisClient->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);
        $this->redisClient->expects($this->never())
            ->method('expire');
        $this->redisClient->expects($this->once())
            ->method('exec')
            ->withAnyParameters()
            ->willReturn(true);

        $this->assertTrue($this->cache->setMultiple($values));
    }

    /**
     * @todo test TTL with DateInterval too
     */
    public function testSetMultipleWithTtl()
    {
        $values = ['do:exist1' => 'value1', 'do:exist2' => 'value2'];
        $ttl = 2;

        $expected = array_keys($values);
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('multi')
            ->with(\Redis::PIPELINE)
            ->willReturn(true);
        $this->redisClient->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);
        $matcher = $this->exactly(\count($values));
        $this->redisClient
            ->expects($matcher)
            ->method('expire')
            ->willReturnCallback(function (string $key, int $i) use ($matcher, $expected, $ttl) {
                $this->assertLessThanOrEqual(\count($expected), $matcher->numberOfInvocations());
                /** we could replace this by an <code>in_array</code> generic check */
                match ($matcher->numberOfInvocations()) {
                    1 =>  $this->assertEquals($expected[0], $key),
                    2 =>  $this->assertEquals($expected[1], $key),
                };
                $this->assertTrue(is_array($expected) && in_array($key, $expected));
                $this->assertEquals($ttl, $i);
            });
        $this->redisClient->expects($this->once())
            ->method('exec')
            ->withAnyParameters()
            ->willReturn(true);

        $this->assertTrue($this->cache->setMultiple($values, $ttl));
    }

    public function testSetWithTtl()
    {
        $key = 'testTTL';
        $this->integrityCheckCL();
        $this->redisClient->expects($this->once())
            ->method('set')
            ->with($key, 'bbbbbbbbbbbbbbbbbbbb')
            ->willReturn(new Status('OK'))
        ;
        $this->redisClient->expects($this->once())
            ->method('expire')
            ->with($key, 1337)
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb', 1337));
    }

    protected function getSelfClient()
    {
        return $this->redisClient;
    }

}
