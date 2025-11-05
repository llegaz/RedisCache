<?php

declare(strict_types=1);

namespace LLegaz\Cache\Entry;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use LLegaz\Cache\RedisCache;
use LLegaz\Cache\Utils;

class CacheEntry extends AbstractCacheEntry
{
    private string $key;
    private mixed $value = null;
    private bool $isHit = false;
    private ?int $ttl = -1;
    private ?DateTimeInterface $dlc = null;

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
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time instanceof DateInterval) {
            $this->ttl = Utils::dateIntervalToSeconds($time);
        } elseif (is_int($time)) {
            if ($time <= 0) {
                $this->ttl = 0;
            } else {
                $this->ttl = $time;
            }
        } else {
            // null case
            $this->ttl = RedisCache::DAY_EXPIRATION_TIME; // 24h
        }
        $this->setDLC();

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if ($expiration) {
            if ((new DateTimeImmutable())->diff($expiration)->invert > 0) {
                $this->ttl = 0;
            } else {
                $this->ttl = $expiration->getTimestamp();
            }
        } else {
            // null case
            $this->ttl = RedisCache::DAY_EXPIRATION_TIME; // 24h
        }
        $this->setDLC();

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

    public function miss(): static
    {
        $this->isHit = false;

        return $this;
    }

    public function set($value): static
    {
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

    public function isExpiredFromDLC(): bool
    {
        if ($this->dlc) {
            return (new DateTimeImmutable())->diff($this->dlc)->invert > 0;
        }

        return false;
    }

    public function getDLC(): ?DateTimeInterface
    {
        return $this->dlc;
    }

    private function setDLC()
    {
        $this->dlc = (new \DateTime((new DateTimeImmutable())->format(DateTimeInterface::ISO8601)))->add(new DateInterval("PT{$this->ttl}S"));
    }

}
