<?php

declare(strict_types=1);

namespace LLegaz\Cache\Entry;

use LLegaz\Cache\RedisCache;
use LLegaz\Cache\Utils;

class CacheEntry extends AbstractCacheEntry
{
    private string $key;
    private mixed $value = null;
    private bool $isHit = false;
    private ?int $ttl = -1;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @todo expiration methods
     *
     * @param int|\DateInterval|null $time
     * @return static
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time instanceof \DateInterval) {
            $this->ttl = Utils::dateIntervalToSeconds($time);
        } elseif (is_int($time)) {
            $this->ttl = $time;
        } else {
            // null case
            $this->ttl = RedisCache::DAY_EXPIRATION_TIME; // 24h
        }

        $this->isTimeStamp = false;

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        // hexpireat
        if ($expiration) {
            $this->ttl = $expiration->getTimestamp();
        } else {
            // null case
            $this->ttl = RedisCache::DAY_EXPIRATION_TIME; // 24h
        }

        return $this;
    }

    public function get(): mixed
    {
        return $this->value;

    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function hit(): static
    {
        $this->isHit = true;

        return $this;
    }

    public function set($value): static
    {
        /*$type = gettype($value);
        dump("CacheEntry::set value= " . $value ." and type= " . $type);
        $e = new \Exception();
        dump($e->getTraceAsString());*/
        $this->value = $value;

        return $this;
    }

    /**
     * return TTL in seconds OR unix timestamp
     *
     * @return int
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

}
