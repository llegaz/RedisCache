<?php

declare(strict_types=1);

namespace LLegaz\Cache\Entry;

class CacheEntry extends AbstractCacheEntry
{
    private string $key;
    private mixed $value = null;
    private bool $isHit = false;
    private int $ttl;

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

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {

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

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

}
