<?php

namespace ProcessMaker\Cache\Monitoring;

interface CacheMetricsInterface
{
    public function recordHit(string $key, $microtime): void;

    public function recordMiss(string $key, $microtime): void;

    public function recordWrite(string $key, int $size): void;

    public function getHitRate(string $key): float;

    public function getMissRate(string $key): float;

    public function getHitAvgTime(string $key): float;

    public function getMissAvgTime(string $key): float;

    public function getTopKeys(int $count = 5): array;

    public function getMemoryUsage(string $key): array;

    public function resetMetrics(): void;

    public function getSummary(): array;
}
