<?php

declare(strict_types=1);

use LLegaz\Cache\RedisCache as SUT;

/**
 * @todo test PSR 6 class too
 * 
 * 
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class SecurityTest extends \PHPUnit\Framework\TestCase
{
    protected SUT $cache;
    
    protected function setUp(): void {
        parent::setUp();

        $this->cache = new SUT();
    }
    /**
     * Security test: Ensure special characters in keys don't cause
     * command injection or unexpected behavior.
     */
    public function testSpecialCharactersDoNotCauseInjection()
    {
        // Attempt "injection-like" patterns
        $dangerousKeys = [
            'key\nFLUSHALL',
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
}
