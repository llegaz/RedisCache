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

    public static function benchmark(callable $callback, array $params = []): string
    {
        $startTime = microtime(true);
        call_user_func_array($callback, $params);
        $endTime = microtime(true);

        return sprintf('%.9f seconds', $endTime - $startTime);
    }
}
