<?php

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\Log;

/**
 * Stores application and package boot timing for the lifetime of an Octane worker.
 *
 * This service is written only while the application is booting. Requests read the
 * captured values when building the Server-Timing header, so the service must not
 * be scoped to or flushed after an individual request.
 */
final class WorkerBootTimingService
{
    private ?float $providerBootTime = null;

    /**
     * @var array<string, array{start: float, end: float|null}>
     */
    private array $packageBootTiming = [];

    /**
     * Store the ProcessMaker service provider boot duration for this worker.
     *
     * @param float $time Boot duration in milliseconds
     */
    public function setProviderBootTime(float $time): void
    {
        $this->providerBootTime = $time;
    }

    /**
     * Get the ProcessMaker service provider boot duration for this worker.
     *
     * @return float|null Boot duration in milliseconds, or null before it is recorded
     */
    public function getProviderBootTime(): ?float
    {
        return $this->providerBootTime;
    }

    /**
     * Record when a package service provider starts booting.
     *
     * Invalid negative timestamps are logged and stored as zero.
     * Calling this method again for the same package replaces its prior timing.
     *
     * @param string $package Package name used in the Server-Timing header
     * @param float $time Start timestamp in seconds, as returned by microtime(true)
     */
    public function setPackageBootStart(string $package, float $time): void
    {
        if ($time < 0) {
            Log::info("Server Timing: Invalid boot time for package: {$package}, time: {$time}");

            $time = 0.0;
        }

        $this->packageBootTiming[$package] = [
            'start' => $time,
            'end' => null,
        ];
    }

    /**
     * Record when a package service provider finishes booting.
     *
     * Invalid negative timestamps and packages without a recorded start are
     * logged and ignored.
     *
     * @param string $package Package name used in the Server-Timing header
     * @param float $time End timestamp in seconds, as returned by microtime(true)
     */
    public function setPackageBootedTime(string $package, float $time): void
    {
        if (!isset($this->packageBootTiming[$package]) || $time < 0) {
            Log::info("Server Timing: Invalid booted time for package: {$package}, time: {$time}");

            return;
        }

        $this->packageBootTiming[$package]['end'] = $time;
    }

    /**
     * Get all package boot timestamps recorded for this worker.
     *
     * @return array<string, array{start: float, end: float|null}>
     */
    public function getPackageBootTiming(): array
    {
        return $this->packageBootTiming;
    }
}
