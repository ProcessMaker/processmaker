<?php

declare(strict_types=1);

namespace ProcessMaker\Health;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

final class OctaneHeartbeatOutput
{
    public function __construct(private readonly Command $command)
    {
    }

    public function render(OctaneHeartbeatResult $result, bool $showPhpIni): void
    {
        if ($this->command->getOutput()->isQuiet()) {
            $this->command->getOutput()->writeln(sprintf(
                'octane:heartbeat: %s (%s, %0.0fms)',
                $result->alive ? 'alive' : 'dead',
                $result->detail ?? 'probe failed',
                $result->durationMs,
            ), OutputInterface::VERBOSITY_QUIET);

            return;
        }

        $this->human($result, $showPhpIni);
    }

    private function human(OctaneHeartbeatResult $result, bool $showPhpIni): void
    {
        $this->command->line('');
        $this->command->line('<info>Octane Heartbeat</info>');
        $this->command->line(str_repeat('─', 40));
        $this->command->line('Mode: ' . $result->mode);
        $this->command->line('Server: ' . self::serverLabel());

        foreach ([
            'Listener' => $result->listener,
            'Endpoint' => $result->endpoint,
            'HTTP' => $result->httpStatus,
            'Detail' => ($result->detail !== null && $result->detail !== '') ? $result->detail : null,
        ] as $label => $value) {
            if ($value !== null) {
                $this->command->line("{$label}: {$value}");
            }
        }

        $this->command->line(sprintf('Duration: %0.0fms', $result->durationMs));

        if ($result->platformChecks !== null) {
            $this->command->line('');
            $this->command->line('<comment>Platform checks</comment>');

            foreach ($result->platformChecks as $check) {
                $this->command->line('  ' . PlatformHealthChecks::consoleLine($check));
            }
        }

        $this->command->line('');

        if ($result->alive) {
            $this->command->info('Status: ALIVE');
        } else {
            $this->command->error('Status: DEAD');

            if ($result->hint !== null) {
                $this->command->line('');
                $this->command->warn($result->hint);
            }
        }

        if ($showPhpIni) {
            (new OctaneHeartbeatPhpIniRenderer($this->command))->render($result);
        }
    }

    private static function serverLabel(): string
    {
        return match ((string) config('octane.server', 'frankenphp')) {
            'frankenphp' => 'FrankenPHP',
            'roadrunner' => 'RoadRunner',
            'swoole' => 'Swoole',
            default => ucfirst((string) config('octane.server', 'octane')),
        };
    }
}
