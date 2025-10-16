<?php

declare(strict_types=1);

namespace LLegaz\Cache\Exception;

use Psr\Cache\InvalidArgumentException as Psr6InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as Psr16InvalidArgumentException;

/**
 * inspired from Symfony Cache (by Nicolas Grekas)
 *
 * PSR-6 and PSR-16 <b>InvalidArgumentException</b>
 *
 * @todo rework / clean all package(s) exceptions ?
 */
class InvalidArgumentException extends \InvalidArgumentException implements Psr6InvalidArgumentException, Psr16InvalidArgumentException
{
}
