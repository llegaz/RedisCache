<?php

declare(strict_types=1);

namespace LLegaz\Cache\Tests\Unit;

use LLegaz\Cache\Entry\CacheEntry as SUT;

/**
 * Test PSR-6 implementation
 *
 * @author Laurent LEGAZ <laurent@legaz.eu>
 */
class CacheItemTest extends \PHPUnit\Framework\TestCase
{
    protected SUT $item;

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
        unset($this->item);
    }

    /**
     * get value + get key
     * test on empty pool
     */
    public function testGet()
    {

    }

    public function testSet()
    {

    }

    /**
     * get value + get key
     * test on "non empty" pool
     */
    public function testGetWithCacheHit()
    {

    }

}
