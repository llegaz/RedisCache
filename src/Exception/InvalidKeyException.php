<?php

declare(strict_types=1);

namespace LLegaz\Cache\Exception;

/**
 *
 * PSR-6 and PSR-16 <b>InvalidArgumentException</b>
 */
class InvalidKeyException extends InvalidArgumentException
{
    public function __construct(string $message = 'RedisCache says "Can\'t do shit with this Key"' . PHP_EOL, int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
