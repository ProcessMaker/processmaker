<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Carbon\Carbon;
use ProcessMaker\Models\Process;

class CaseRetentionTierService
{
    /**
     * @deprecated Stored only for backward compatibility; use {@see self::NOTICE_AT_PROPERTY_KEY}.
     */
    public const NOTICE_PROPERTY_KEY = 'case_retention_tier_adjustment_notice';

    public const NOTICE_AT_PROPERTY_KEY = 'case_retention_tier_adjustment_notice_at';

    public const NOTICE_DURATION_HOURS = 24;

    private const VALID_PERIODS = ['six_months', 'one_year', 'three_years', 'five_years'];

    /** @var array<string, int> */
    private const PERIOD_MONTHS = [
        'six_months' => 6,
        'one_year' => 12,
        'three_years' => 36,
        'five_years' => 60,
    ];

    /**
     * Whether the process-listing warning for a tier-driven retention clamp should show (admins only).
     */
    public static function adjustmentNoticeIsActive(Process $process): bool
    {
        $props = $process->properties ?? [];
        $at = $props[self::NOTICE_AT_PROPERTY_KEY] ?? null;
        if (is_string($at) && $at !== '') {
            return Carbon::parse($at)->greaterThan(now()->subHours(self::NOTICE_DURATION_HOURS));
        }

        if (filter_var($props[self::NOTICE_PROPERTY_KEY] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $updatedAt = $process->updated_at;

            return $updatedAt && Carbon::parse($updatedAt)->greaterThan(now()->subHours(self::NOTICE_DURATION_HOURS));
        }

        return false;
    }

    /**
     * Retention period options allowed for the configured CASE_RETENTION_TIER.
     *
     * @return list<string>
     */
    public static function allowedPeriodsForCurrentTier(): array
    {
        $tier = (string) config('app.case_retention_tier', '1');
        $options = config('app.case_retention_tier_options', []);

        return $options[$tier] ?? $options['1'] ?? ['six_months', 'one_year'];
    }

    /**
     * Longest retention period in the allowed list (by duration), not by array order.
     *
     * @param  list<string>  $allowed
     */
    public static function longestAllowedPeriod(array $allowed): string
    {
        $best = 'one_year';
        $bestMonths = 0;
        foreach ($allowed as $period) {
            if (!is_string($period)) {
                continue;
            }
            $months = self::PERIOD_MONTHS[$period] ?? null;
            if ($months === null) {
                continue;
            }
            if ($months > $bestMonths) {
                $bestMonths = $months;
                $best = $period;
            }
        }

        return $bestMonths > 0 ? $best : 'one_year';
    }

    public static function normalizePeriod(mixed $period): string
    {
        if (is_string($period) && in_array($period, self::VALID_PERIODS, true)) {
            return $period;
        }

        return 'one_year';
    }

    /**
     * If the process retention period is not allowed for the current tier, set it to the
     * longest period allowed for that tier, refresh retention_updated_at, clear retention_updated_by
     * (so the UI shows the default retention message), and record when to show the admin notice (24h).
     *
     * @param  list<string>|null  $tierAllowedPeriods  When null, resolved from config; when set (e.g. from a
     *                                                 batch command), avoids re-reading tier options per process.
     * @return bool True when the process was updated.
     */
    public static function clampProcessRetentionToCurrentTier(Process $process, ?array $tierAllowedPeriods = null): bool
    {
        $allowed = $tierAllowedPeriods ?? self::allowedPeriodsForCurrentTier();
        $current = self::normalizePeriod($process->properties['retention_period'] ?? null);

        if (in_array($current, $allowed, true)) {
            return false;
        }

        $maxPeriod = self::longestAllowedPeriod($allowed);
        $properties = $process->properties ?? [];
        $properties['retention_period'] = $maxPeriod;
        $properties['retention_updated_at'] = now()->toIso8601String();
        unset($properties['retention_updated_by']);
        unset($properties[self::NOTICE_PROPERTY_KEY]);
        $properties[self::NOTICE_AT_PROPERTY_KEY] = now()->toIso8601String();
        $process->properties = $properties;
        $process->saveQuietly();

        return true;
    }
}
