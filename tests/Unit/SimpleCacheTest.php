<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Redis\PredisClient;
use LLegaz\Redis\RedisClientInterface;
use LLegaz\Redis\Tests\RedisAdapterTestBase;
use Predis\Response\Status;

/**
 * Test PSR-16 implementation with predis client
 *
 * expect 1 more client command (list) because of the integrity check
 * (units are in forced paranoid mode for now @todo mb rework this here and in adapter)
 *
 *
 * @todo
 * @todo   REWORK UNITS (especially those with multiple sets)
 * @todo
 * @todo rename this class to RedisCacheTest to clarify
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SimpleCacheTest extends RedisAdapterTestBase
{
    protected SUT $cache;

    protected RedisClientInterface $predisClient;

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
        $this->predisClient->expects($this->once())
            ->method('flushall')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear(true));
    }

    public function testClear()
    {
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
            ->method('flushdb')
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->clear());
    }

    public function testDelete()
    {
        $key = 'test';
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
            ->method('del')
            ->with(trim(implode(' ', $keys)))
            ->willReturn(new Status(3))
        ;
        $this->assertTrue($this->cache->deleteMultiple($keys));
    }

    public function testGetInexistant()
    {
        $key = 'do:not:exist';
        $this->integrityCheckCL();
        $expected = 'default';
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $key = 'do:exist';
        $expected = null;
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
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
        $this->predisClient->expects($this->once())
            ->method('set')
            ->with($key)
            ->willReturn(new Status('OK'))
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb'));
    }

    /**
     *
     * @todo maybe rework this and get some inspiration here:
     *
     * @link https://medium.com/@dotcom.software/unit-testing-closures-the-right-way-b982fc833bfa
     *
     * to redefine another object to emulate transaction part from predis and test behavior inside (mset, expire, etc.)

    public function testSetMultiple()
    {
        $values = ['do:exist1' => 'value1', 'do:exist2' => 'value2'];

        $testcase = $this;
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
            ->method('transaction') // predis case
            ->withAnyParameters()
            ->willReturnCallback(function (array $options, $closure) use ($testcase, $values) {
                $reflection = new \ReflectionFunction($closure);
                $testcase->assertFalse($reflection->getStaticVariables()['redisResponse']);
                $testcase->assertIsArray($options);
                $testcase->assertIsArray($reflection->getStaticVariables()['newValues']);
                $testcase->assertEquals($values, $reflection->getStaticVariables()['newValues']);
                $ttl = $reflection->getStaticVariables()['ttl'];
                $testcase->assertTrue(is_int($ttl) || is_null($ttl));
                $reflection->getStaticVariables()['redisResponse'] = true;
                $testcase->assertTrue($reflection->getStaticVariables()['redisResponse']);
            });

        $this->assertTrue($this->cache->setMultiple($values));
    }

    /**
     * @todo maybe enhance logger testing

    public function testSetWithTtl()
    {
        $key = 'testTTL';
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
            ->method('set')
            ->with($key, 'bbbbbbbbbbbbbbbbbbbb')
            ->willReturn(new Status('OK'))
        ;
        $this->predisClient->expects($this->once())
            ->method('expire')
            ->with($key, 1337)
            //->will($this->throwException(new Exception())); // test logger
            ->willReturn(1)
        ;
        $this->assertTrue($this->cache->set($key, 'bbbbbbbbbbbbbbbbbbbb', 1337));
    }

    /**
     * @todo test TTL with DateInterval too !!!

    public function testSetMultipleWithTtl()
    {
        $values = ['do:exist1' => 'value1', 'do:exist2' => 'value2'];
        $ttl = 2;

        $testcase = $this;
        $this->integrityCheckCL();
        $this->predisClient->expects($this->once())
            ->method('transaction') // predis case
            ->withAnyParameters()
            ->willReturnCallback(function (array $options, $closure) use ($testcase, $values, $ttl) {
                $reflection = new \ReflectionFunction($closure);
                $testcase->assertFalse($reflection->getStaticVariables()['redisResponse']);
                $testcase->assertIsArray($options);
                $testcase->assertIsArray($reflection->getStaticVariables()['newValues']);
                $testcase->assertEquals($values, $reflection->getStaticVariables()['newValues']);
                $testcase->assertTrue(is_int($reflection->getStaticVariables()['ttl']));
                $testcase->assertEquals($ttl, $reflection->getStaticVariables()['ttl']);
                $reflection->getStaticVariables()['redisResponse'] = true;
                $testcase->assertTrue($reflection->getStaticVariables()['redisResponse']);
            });

        $this->assertTrue($this->cache->setMultiple($values, $ttl));
    }

    /**
     *
     * @return type
     */
    protected function getSelfClient(): RedisClientInterface
    {
        return $this->predisClient;
    }

}
