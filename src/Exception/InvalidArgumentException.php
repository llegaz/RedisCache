<?php

namespace LLegaz\Cache\Exception;

use Psr\Cache\InvalidArgumentException as Psr6CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

/**
 * inspired from Symfony Cache (by Nicolas Grekas)
 * 
 * @todo rework all package exceptions ?
 */
class InvalidArgumentException extends \InvalidArgumentException implements Psr6CacheInterface, SimpleCacheInterface
{
}
