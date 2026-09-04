<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

class CaddyPhpIni
{
    /** @var array<string, string> */
    private const ENV_TO_INI = [
        'OCTANE_MEMORY_LIMIT' => 'memory_limit',
        'OCTANE_MAX_EXECUTION_TIME' => 'max_execution_time',
        'OCTANE_POST_MAX_SIZE' => 'post_max_size',
        'OCTANE_UPLOAD_MAX_FILESIZE' => 'upload_max_filesize',
        'OCTANE_MAX_INPUT_VARS' => 'max_input_vars',
        'OCTANE_MAX_INPUT_TIME' => 'max_input_time',
    ];

    public static function caddyfilePath(): string
    {
        return base_path('Caddyfile');
    }

    /**
     * Values from config/octane.php → caddy.env (what the Caddyfile should apply).
     *
     * @return array<string, string>
     */
    public static function configured(): array
    {
        /** @var array<string, mixed> $env */
        $env = config('octane.caddy.env', []);
        $php = [];

        foreach (self::ENV_TO_INI as $envKey => $iniKey) {
            if (isset($env[$envKey]) && $env[$envKey] !== '') {
                $php[$iniKey] = (string) $env[$envKey];
            }
        }

        return $php;
    }

    /**
     * @return array<string, string>
     */
    public static function fromWorker(): array
    {
        $php = [];

        foreach (self::configured() as $directive => $_expected) {
            $actual = ini_get($directive);
            $php[$directive] = $actual !== false && $actual !== '' ? (string) $actual : '';
        }

        return $php;
    }

    /**
     * @param array<string, string> $worker
     *
     * @return array<int, array{directive: string, expected: string, actual: string, matches: bool}>
     */
    public static function verify(array $worker): array
    {
        $results = [];

        foreach (self::configured() as $directive => $expected) {
            $actual = $worker[$directive] ?? '';
            $results[] = [
                'directive' => $directive,
                'expected' => $expected,
                'actual' => $actual !== '' ? $actual : '(missing)',
                'matches' => self::valuesMatch($directive, $expected, $actual),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, string> $worker
     */
    public static function allMatch(array $worker): bool
    {
        foreach (self::verify($worker) as $entry) {
            if (!$entry['matches']) {
                return false;
            }
        }

        return $worker !== [];
    }

    public static function directiveMatches(string $directive, string $expected, string $actual): bool
    {
        return self::valuesMatch($directive, $expected, $actual);
    }

    public static function directivesEquivalent(string $directive, string $left, string $right): bool
    {
        if ($left === '' && $right === '') {
            return true;
        }

        if ($left === '' || $right === '') {
            return false;
        }

        return self::normalizeValue($directive, $left) === self::normalizeValue($directive, $right);
    }

    private static function valuesMatch(string $directive, string $expected, string $actual): bool
    {
        if ($actual === '') {
            return false;
        }

        $normalizedExpected = self::normalizeValue($directive, $expected);
        $normalizedActual = self::normalizeValue($directive, $actual);

        if ($normalizedExpected === $normalizedActual) {
            return true;
        }

        if ($directive === 'max_execution_time' && $normalizedActual === '0' && (int) $normalizedExpected > 0) {
            return true;
        }

        if ($directive === 'max_input_time' && $normalizedActual === '-1' && (int) $normalizedExpected > 0) {
            return true;
        }

        return false;
    }

    private static function normalizeValue(string $directive, string $value): string
    {
        $value = trim(strtolower($value));

        if (is_numeric($value)) {
            return (string) (int) (float) $value;
        }

        return $value;
    }

    /**
     * @return array{0: ?array<string, string>, 1: ?string, 2: ?string, 3: ?array<string, bool>}
     */
    public static function workerSnapshot(bool $includePhpIni): array
    {
        if (!$includePhpIni) {
            return [null, null, null, null];
        }

        return [
            self::fromWorker(),
            self::caddyfilePath(),
            PHP_VERSION,
            CliPhpIni::extensionsLoaded(),
        ];
    }
}
