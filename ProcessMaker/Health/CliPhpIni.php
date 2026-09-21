<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

use Illuminate\Support\Facades\Process;
use Throwable;

class CliPhpIni
{
    public const BASELINE_COLUMN = 'CLI PHP (php.ini)';

    public const WORKER_COLUMN = 'FrankenPHP worker';

    /** @var list<string> */
    public const TRACKED_EXTENSIONS = [
        'grpc',
        'imagick',
        'mongodb',
        'opentelemetry',
        'pdo_sqlsrv',
        'rdkafka',
        'redis',
        'sqlsrv',
        'swoole',
        'xdebug',
    ];

    /** @var array<string, string> */
    private const DIRECTIVE_LABELS = [
        'memory_limit' => 'Memory limit',
        'max_execution_time' => 'Max execution time',
        'post_max_size' => 'POST body max size',
        'upload_max_filesize' => 'Upload max file size',
        'max_input_vars' => 'Max input variables',
        'max_input_time' => 'Max input parse time',
    ];

    /**
     * @return array{
     *     php_version: string,
     *     ini_path: string|null,
     *     directives: array<string, string>,
     *     extensions: array<string, bool>
     * }
     */
    public static function snapshot(): array
    {
        $iniPath = self::detectIniPath();
        $directives = array_keys(CaddyPhpIni::configured());

        try {
            $parsed = self::runPhpProbe($iniPath, $directives);
        } catch (Throwable) {
            $parsed = [
                'php_version' => PHP_VERSION,
                'directives' => self::directivesFromCurrentProcess(),
                'extensions' => self::extensionsLoaded(),
            ];
        }

        return [
            'php_version' => $parsed['php_version'],
            'ini_path' => $iniPath,
            'directives' => $parsed['directives'],
            'extensions' => $parsed['extensions'],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function extensionsLoaded(): array
    {
        $extensions = [];

        foreach (self::TRACKED_EXTENSIONS as $extension) {
            $extensions[$extension] = extension_loaded($extension);
        }

        return $extensions;
    }

    public static function detectIniPath(): ?string
    {
        $loaded = php_ini_loaded_file();
        $fromLoaded = self::readableIniPath(is_string($loaded) ? $loaded : null);

        if ($fromLoaded !== null) {
            return $fromLoaded;
        }

        return self::readableIniPath(self::iniPathFromPhpBinary());
    }

    public static function loadedIniPathFromPhpIniOutput(string $output): ?string
    {
        foreach (explode("\n", $output) as $line) {
            if (!str_contains($line, 'Loaded Configuration File:')) {
                continue;
            }

            $path = trim(substr($line, strpos($line, ':') + 1));

            if ($path === '' || $path === '(none)') {
                return null;
            }

            return $path;
        }

        return null;
    }

    private static function iniPathFromPhpBinary(): ?string
    {
        try {
            $result = Process::timeout(5)->run([PHP_BINARY, '--ini']);

            if (!$result->successful()) {
                return null;
            }

            return self::loadedIniPathFromPhpIniOutput($result->output());
        } catch (Throwable) {
            return null;
        }
    }

    private static function readableIniPath(?string $path): ?string
    {
        if ($path === null || $path === '' || !is_readable($path)) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<string, string> $cli
     * @param array<string, string> $worker
     * @param array<string, string> $configured
     *
     * @return array<int, array{
     *     directive: string,
     *     cli: string,
     *     worker: string,
     *     configured: string,
     *     worker_matches_configured: bool,
     *     cli_differs_from_worker: bool
     * }>
     */
    public static function versusRows(array $cli, array $worker, array $configured): array
    {
        $rows = [];

        foreach ($configured as $directive => $expected) {
            $cliValue = $cli[$directive] ?? '';
            $workerValue = $worker[$directive] ?? '';

            $rows[] = [
                'directive' => $directive,
                'cli' => self::displayValue($cliValue),
                'worker' => self::displayValue($workerValue),
                'configured' => $expected,
                'worker_matches_configured' => CaddyPhpIni::directiveMatches($directive, $expected, $workerValue),
                'cli_differs_from_worker' => !CaddyPhpIni::directivesEquivalent($directive, $cliValue, $workerValue),
            ];
        }

        return $rows;
    }

    public static function directiveLabel(string $directive): string
    {
        return self::DIRECTIVE_LABELS[$directive] ?? $directive;
    }

    public static function formatIniValue(string $directive, string $value): string
    {
        if ($value === '') {
            return 'not set';
        }

        if ($value === '-1') {
            return 'unlimited';
        }

        if ($directive === 'max_execution_time' && $value === '0') {
            return 'unlimited (0)';
        }

        if ($directive === 'max_input_time' && ($value === '-1' || $value === '0')) {
            return 'unlimited';
        }

        if ($directive === 'max_execution_time' || $directive === 'max_input_time') {
            return $value . ' sec';
        }

        return $value;
    }

    public static function workerStatus(string $directive, string $worker, string $configured, bool $matches): string
    {
        if (!$matches) {
            return 'Mismatch';
        }

        if (CaddyPhpIni::directivesEquivalent($directive, $worker, $configured)) {
            return 'OK';
        }

        return match ($directive) {
            'max_execution_time' => 'OK — worker has no time limit',
            'max_input_time' => 'OK — unlimited parse time',
            default => 'OK',
        };
    }

    /**
     * @param array<int, array{
     *     directive: string,
     *     cli: string,
     *     worker: string,
     *     configured: string,
     *     worker_matches_configured: bool,
     *     cli_differs_from_worker: bool
     * }> $versusRows
     *
     * @return array<int, array{
     *     directive: string,
     *     cli: string,
     *     worker: string,
     *     configured: string,
     *     worker_matches_configured: bool,
     *     cli_differs_from_worker: bool
     * }>
     */
    public static function baselineDiffRows(array $versusRows): array
    {
        return array_values(array_filter(
            $versusRows,
            static fn (array $row): bool => $row['cli_differs_from_worker'],
        ));
    }

    /**
     * @param array<string, bool> $cli
     * @param array<string, bool>|null $worker
     *
     * @return array<int, array{extension: string, cli: string, worker: string, differs: bool}>
     */
    public static function extensionDiffRows(array $cli, ?array $worker): array
    {
        return array_values(array_filter(
            self::extensionRows($cli, $worker),
            static fn (array $row): bool => $row['differs'],
        ));
    }

    /**
     * @param array<string, bool> $cli
     * @param array<string, bool>|null $worker
     *
     * @return array<int, array{extension: string, cli: string, worker: string, differs: bool}>
     */
    public static function extensionRows(array $cli, ?array $worker): array
    {
        $rows = [];

        foreach (self::TRACKED_EXTENSIONS as $extension) {
            $cliLoaded = $cli[$extension] ?? false;

            if ($worker === null) {
                $rows[] = [
                    'extension' => $extension,
                    'cli' => $cliLoaded ? 'yes' : 'no',
                    'worker' => 'unknown',
                    'differs' => false,
                ];

                continue;
            }

            $workerLoaded = $worker[$extension] ?? false;

            $rows[] = [
                'extension' => $extension,
                'cli' => $cliLoaded ? 'yes' : 'no',
                'worker' => $workerLoaded ? 'yes' : 'no',
                'differs' => $cliLoaded !== $workerLoaded,
            ];
        }

        return $rows;
    }

    /**
     * @param list<string> $directives
     *
     * @return array{
     *     php_version: string,
     *     directives: array<string, string>,
     *     extensions: array<string, bool>
     * }
     */
    private static function runPhpProbe(?string $iniPath, array $directives): array
    {
        $command = [PHP_BINARY];

        if ($iniPath !== null) {
            $command[] = '-c';
            $command[] = $iniPath;
        }

        $command[] = '-r';
        $command[] = self::probeScript($directives);

        $result = Process::timeout(5)->run($command);

        if (!$result->successful()) {
            throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
        }

        return self::parseProbeOutput($result->output());
    }

    /**
     * @param list<string> $directives
     */
    private static function probeScript(array $directives): string
    {
        $directivesExport = var_export($directives, true);
        $extensionsExport = var_export(self::TRACKED_EXTENSIONS, true);

        return <<<PHP
            echo 'PHP_VERSION=' . PHP_VERSION . PHP_EOL;
            foreach ({$directivesExport} as \$directive) {
                \$value = ini_get(\$directive);
                echo \$directive . '=' . ((\$value === false || \$value === '') ? '' : \$value) . PHP_EOL;
            }
            foreach ({$extensionsExport} as \$extension) {
                echo 'ext:' . \$extension . '=' . (extension_loaded(\$extension) ? '1' : '0') . PHP_EOL;
            }
        PHP;
    }

    /**
     * @return array{
     *     php_version: string,
     *     directives: array<string, string>,
     *     extensions: array<string, bool>
     * }
     */
    private static function parseProbeOutput(string $output): array
    {
        $phpVersion = PHP_VERSION;
        $directives = [];
        $extensions = [];

        foreach (explode("\n", trim($output)) as $line) {
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            if ($key === 'PHP_VERSION') {
                $phpVersion = $value;

                continue;
            }

            if (str_starts_with($key, 'ext:')) {
                $extensions[substr($key, 4)] = $value === '1';

                continue;
            }

            $directives[$key] = $value;
        }

        return [
            'php_version' => $phpVersion,
            'directives' => $directives,
            'extensions' => $extensions,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function directivesFromCurrentProcess(): array
    {
        $directives = [];

        foreach (array_keys(CaddyPhpIni::configured()) as $directive) {
            $value = ini_get($directive);
            $directives[$directive] = $value !== false && $value !== '' ? (string) $value : '';
        }

        return $directives;
    }

    private static function displayValue(string $value): string
    {
        return $value !== '' ? $value : '(missing)';
    }
}
