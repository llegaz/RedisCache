<?php

declare(strict_types=1);

namespace LLegaz\Cache;

use DateTimeImmutable;

class Utils
{
    /**
     * return time to live in seconds
     *
     * @param \DateInterval $ttl
     * @return int
     */
    public static function dateIntervalToSeconds(\DateInterval $ttl): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($ttl);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }
}
