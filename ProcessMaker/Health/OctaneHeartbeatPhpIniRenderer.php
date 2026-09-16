<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

use Illuminate\Console\Command;

final class OctaneHeartbeatPhpIniRenderer
{
    public function __construct(private readonly Command $command)
    {
    }

    public function render(OctaneHeartbeatResult $result): void
    {
        if ($result->phpIni === null || $result->phpIni === []) {
            return;
        }

        $cli = CliPhpIni::snapshot();
        $configured = CaddyPhpIni::configured();
        $versusRows = CliPhpIni::versusRows($cli['directives'], $result->phpIni, $configured);
        $extensionRows = CliPhpIni::extensionRows($cli['extensions'], $result->extensionsWorker);
        $extensionDiffs = CliPhpIni::extensionDiffRows($cli['extensions'], $result->extensionsWorker);

        $this->command->line('');
        $this->command->line('<comment>PHP configuration</comment>');
        $this->command->line('  Compares three runtimes:');
        $this->command->line('    <info>' . CliPhpIni::BASELINE_COLUMN . '</info> — system php.ini (CLI / php-fpm; does not power Octane)');
        $this->command->line('    <info>' . CliPhpIni::WORKER_COLUMN . '</info> — live process serving HTTP right now');
        $this->command->line('    <info>.env target</info> — OCTANE_* values applied via Caddyfile');
        $this->command->line('');
        $this->command->line('  CLI PHP:   PHP ' . $cli['php_version'] . ' · ' . ($cli['ini_path'] ?? 'PHP runtime defaults (no php.ini loaded)'));
        $this->command->line('  Worker:    PHP ' . ($result->phpVersionWorker ?? 'unknown — restart Octane'));
        $this->command->line('  Laravel:   ' . app()->version());

        if ($result->caddyfile !== null) {
            $this->command->line('  Caddyfile: ' . $result->caddyfile);
        }

        $this->renderIniTable($result, $cli, $versusRows);
        $this->renderIniSummary($versusRows);
        $this->renderExtensions($result, $extensionRows, $extensionDiffs);
    }

    /**
     * @param array{
     *     php_version: string,
     *     ini_path: string|null,
     *     directives: array<string, string>,
     *     extensions: array<string, bool>
     * } $cli
     * @param list<array{
     *     directive: string,
     *     cli: string,
     *     worker: string,
     *     configured: string,
     *     worker_matches_configured: bool,
     *     cli_differs_from_worker: bool
     * }> $versusRows
     */
    private function renderIniTable(OctaneHeartbeatResult $result, array $cli, array $versusRows): void
    {
        $this->command->line('');
        $this->command->line('  <comment>php.ini settings</comment>');
        $this->command->table(
            ['Setting', CliPhpIni::BASELINE_COLUMN, CliPhpIni::WORKER_COLUMN, '.env target', 'Status'],
            array_map(function (array $row) use ($result, $cli): array {
                $directive = $row['directive'];

                return [
                    CliPhpIni::directiveLabel($directive),
                    CliPhpIni::formatIniValue($directive, $cli['directives'][$directive] ?? ''),
                    CliPhpIni::formatIniValue($directive, $result->phpIni[$directive] ?? ''),
                    CliPhpIni::formatIniValue($directive, $row['configured']),
                    CliPhpIni::workerStatus(
                        $directive,
                        $result->phpIni[$directive] ?? '',
                        $row['configured'],
                        $row['worker_matches_configured'],
                    ),
                ];
            }, $versusRows),
        );
    }

    /**
     * @param list<array{
     *     directive: string,
     *     cli: string,
     *     worker: string,
     *     configured: string,
     *     worker_matches_configured: bool,
     *     cli_differs_from_worker: bool
     * }> $versusRows
     */
    private function renderIniSummary(array $versusRows): void
    {
        $configMismatches = count(array_filter(
            $versusRows,
            static fn (array $row): bool => !$row['worker_matches_configured'],
        ));

        if ($configMismatches > 0) {
            $this->command->warn("  {$configMismatches} setting(s) mismatch .env — run `php artisan octane:reload`.");
        } else {
            $this->command->info('  Worker matches .env configuration.');
        }

        $baselineDiffs = count(CliPhpIni::baselineDiffRows($versusRows));

        if ($baselineDiffs > 0) {
            $this->command->line("  {$baselineDiffs} setting(s) differ from CLI php.ini — normal when using Octane.");
        }
    }

    /**
     * @param list<array{extension: string, cli: string, worker: string}> $extensionRows
     * @param list<array{extension: string, cli: string, worker: string}> $extensionDiffs
     */
    private function renderExtensions(
        OctaneHeartbeatResult $result,
        array $extensionRows,
        array $extensionDiffs,
    ): void {
        $this->command->line('');
        $this->command->line('  Extensions — ' . CliPhpIni::BASELINE_COLUMN . ' vs ' . CliPhpIni::WORKER_COLUMN . ' (gaps can cause HTTP 500)');

        if ($result->extensionsWorker === null) {
            $this->command->warn('  Worker extensions unknown — restart: `php artisan octane:stop && php artisan octane:start --server=frankenphp --caddyfile=Caddyfile`');

            return;
        }

        $this->command->table(
            ['Extension', CliPhpIni::BASELINE_COLUMN, CliPhpIni::WORKER_COLUMN],
            array_map(static fn (array $row): array => [
                $row['extension'],
                $row['cli'],
                $row['worker'],
            ], $extensionRows),
        );

        if ($extensionDiffs !== []) {
            $this->command->warn('  ' . count($extensionDiffs) . ' extension(s) in CLI PHP but not in FrankenPHP — rebuild frankenphp if the app needs them.');
        }
    }
}
