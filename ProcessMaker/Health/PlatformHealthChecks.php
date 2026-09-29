<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class PlatformHealthChecks
{
    /**
     * @return array{
     *     checks: list<array{name: string, status: string, detail: ?string}>|null,
     *     failure: ?string
     * }
     */
    public static function evaluate(bool $ready): array
    {
        if (!$ready) {
            return ['checks' => null, 'failure' => null];
        }

        $checks = self::run();

        return [
            'checks' => $checks,
            'failure' => self::firstFailure($checks),
        ];
    }

    /**
     * @return list<array{name: string, status: string, detail: ?string}>
     */
    public static function run(): array
    {
        $database = self::tryCheck('Database', static fn () => DB::connection()->getPdo(), (string) config('database.default'));

        if ($database['status'] === 'failed') {
            return [$database];
        }

        $checks = [$database];

        if (!self::redisRequired()) {
            $checks[] = [
                'name' => 'Redis',
                'status' => 'skipped',
                'detail' => 'Not required by cache, queue, session, or broadcasting',
            ];

            return $checks;
        }

        $checks[] = self::tryCheck(
            'Redis',
            static fn () => Redis::connection()->ping(),
            (string) config('database.redis.default.host', '127.0.0.1'),
        );

        return $checks;
    }

    /**
     * @param array{name: string, status: string, detail: ?string} $check
     */
    public static function consoleLine(array $check): string
    {
        $name = $check['name'];
        $detail = $check['detail'] ?? '';

        return match ($check['status']) {
            'ok' => $detail !== '' ? "{$name}: <info>OK</info> ({$detail})" : "{$name}: <info>OK</info>",
            'skipped' => $detail !== '' ? "{$name}: <comment>skipped</comment> — {$detail}" : "{$name}: <comment>skipped</comment>",
            default => $detail !== '' ? "{$name}: <error>FAILED</error> — {$detail}" : "{$name}: <error>FAILED</error>",
        };
    }

    /**
     * @return array{
     *     dead: ?OctaneHeartbeatResult,
     *     checks: list<array{name: string, status: string, detail: ?string}>|null
     * }
     */
    public static function guard(
        ProbeContext $context,
        bool $ready,
        string $hint,
        ?int $httpStatus = null,
    ): array {
        $platform = self::evaluate($ready);

        if ($platform['failure'] === null) {
            return ['dead' => null, 'checks' => $platform['checks']];
        }

        return [
            'dead' => OctaneHeartbeatResult::deadFromContext(
                $context,
                $platform['failure'],
                hint: $hint,
                httpStatus: $httpStatus,
                platformChecks: $platform['checks'],
            ),
            'checks' => $platform['checks'],
        ];
    }

    /**
     * @return array{name: string, status: string, detail: ?string}
     */
    private static function tryCheck(string $name, callable $callback, string $okDetail): array
    {
        try {
            $callback();

            return ['name' => $name, 'status' => 'ok', 'detail' => $okDetail];
        } catch (Throwable $exception) {
            return ['name' => $name, 'status' => 'failed', 'detail' => $exception->getMessage()];
        }
    }

    /**
     * @param list<array{name: string, status: string, detail: ?string}> $checks
     */
    private static function firstFailure(array $checks): ?string
    {
        $failed = array_find($checks, static fn (array $check): bool => $check['status'] === 'failed');

        if ($failed === null) {
            return null;
        }

        return strtolower($failed['name']) . ' unavailable: ' . ($failed['detail'] ?? 'check failed');
    }

    private static function redisRequired(): bool
    {
        return in_array('redis', [
            config('cache.default'),
            config('queue.default'),
            config('broadcasting.default'),
            config('session.driver'),
        ], true);
    }
}
