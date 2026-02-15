<?php

declare(strict_types=1);

use LLegaz\Cache\Pool\CacheEntryPool;
use LLegaz\Cache\RedisCache as SUT;
use LLegaz\Cache\RedisEnhancedCache as SUT2;

/**
 * @todo trigger exception on "\" character
 *
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SecurityTest extends \PHPUnit\Framework\TestCase
{
    protected SUT $cache;

    protected CacheEntryPool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new SUT();
        $this->pool = new CacheEntryPool(new SUT2());

        $this->cache->clear();
        $this->pool->clear();
    }
    /**
     * Security test: Ensure special characters in keys don't cause
     * command injection or unexpected behavior.
     */
    public function testSpecialCharactersDoNotCauseInjectionPSR16()
    {
        // Attempt "injection-like" patterns
        $dangerousKeys = [
            'key|FLUSHALL',
            'key`FLUSHALL`',
            'key$(FLUSHALL)',
        ];

        $this->cache->set('canary', 'chirp');

        foreach ($dangerousKeys as $key) {
            // Should work without executing any injection
            $this->cache->set($key, 'safe_value');
            $this->assertEquals('safe_value', $this->cache->get($key));
            $this->cache->delete($key);
        }

        // Verify no side effects (cache not flushed)
        $this->assertEquals('chirp', $this->cache->get('canary'));
    }

    public function testSpecialCharactersDoNotCauseInjectionPSR6()
    {

        // Attempt "injection-like" patterns
        $dangerousKeys = [
            'key|FLUSHALL',
            'key`FLUSHALL`',
            'key$(FLUSHALL)',
        ];

        $item = $this->pool->getItem('canary');
        $item->set('chirp');
        $this->pool->save($item);

        foreach ($dangerousKeys as $key) {
            // Should work without executing any injection
            $item = $this->pool->getItem($key);
            $item->set('safe value!');
            $this->pool->save($item);
            $this->assertEquals('safe value!', $this->pool->getItem($key)->get());
            $this->pool->deleteItem($key);
        }

        // Verify no side effects (cache not flushed)
        $this->assertEquals('chirp', $this->pool->getItem('canary')->get());
    }
}
