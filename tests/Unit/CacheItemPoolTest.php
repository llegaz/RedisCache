<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\Entry\CacheEntry as SUT;

/**
 * Test PSR-6 implementation
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class CacheItemPoolTest extends \PHPUnit\Framework\TestCase
{
    protected SUT $pool;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {

    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        unset($this->pool);
    }

    /**
     * @todo remove dummy
     */
    public function testSomething()
    {
        $this->assertTrue(true);
    }

}
