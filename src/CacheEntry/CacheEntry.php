<?php

declare(strict_types=1);

namespace LLegaz\Cache\Entry;

use LLegaz\Cache\Utils;

class CacheEntry extends AbstractCacheEntry
{
    private string $key;
    private mixed $value = null;
    private bool $isHit = false;
    private ?int $ttl;
    private bool $isTimeStamp;


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
        // hexpire
        if ($time instanceof \DateInterval) {
            $this->ttl = Utils::dateIntervalToSeconds($time);
        } else {
            $this->ttl = $time;
        }

        $this->isTimeStamp = false;

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        // hexpireat
        $this->ttl = $expiration->getTimestamp();
        $this->isTimeStamp = true;

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
     * if true = expireAt
     *
     * @return bool
     */
    public function isTimeStamp(): bool
    {
        return $this->isTimeStamp;
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
