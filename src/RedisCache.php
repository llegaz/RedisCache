<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use LLegaz\Redis\RedisAdapter;
use Psr\SimpleCache\CacheInterface;

class RedisCache extends RedisAdapter implements CacheInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function clear(): bool
    {

    }

    public function delete(string $key): bool
    {

    }

    public function deleteMultiple($keys): bool
    {

    }

    public function get(string $key, mixed $default = null): mixed
    {

    }

    public function getMultiple($keys, mixed $default = null): iterable
    {

    }

    public function has(string $key): bool
    {

    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {

    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {

    }

}
