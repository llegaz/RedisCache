<?php

declare(strict_types=1);

namespace LLegaz\Cache\Entry;

class CacheEntry extends AbstractCacheEntry
{
    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {

    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {

    }

    public function get(): mixed
    {

    }

    public function getKey(): string
    {

    }

    public function isHit(): bool
    {

    }

    public function set(mixed $value): static
    {

    }

}
