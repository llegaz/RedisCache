<?php

declare(strict_types=1);

namespace LLegaz\Cache\Exception;

use Psr\Cache\InvalidArgumentException;

class InvalidKeyException extends \Exception implements InvalidArgumentException
{
    public function __construct(string $message = 'RedisCache says "Can\'t do shit with this Key"' . PHP_EOL, int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
