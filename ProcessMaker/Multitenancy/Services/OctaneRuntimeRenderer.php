<?php

declare(strict_types=1);

namespace ProcessMaker\Multitenancy\Services;

/**
 * Renders Caddyfile.edge and supervisor program definitions for Octane runtimes.
 */
class OctaneRuntimeRenderer
{
    public const MANAGED_MARKER = 'Managed by tenants:octane-sync';

    public const SINGLE_PROGRAM = 'octane';

    public const EDGE_PROGRAM = 'octane-edge';

    /**
     * @param  list<array{id:int,domain:string,port:int,admin_port:int}>  $tenantPlans
     */
    public function renderEdgeCaddyfile(array $tenantPlans): string
    {
        $port = (int) config('octane_tenants.edge_port');
        $lines = [
            '# ' . self::MANAGED_MARKER,
            '# Do not edit by hand; run php artisan tenants:octane-sync',
            '{',
            '	admin off',
            '	auto_https off',
            '	servers {',
            '		protocols h1 h2',
            '	}',
            '}',
            '',
            ":{$port} {",
        ];

        if ($tenantPlans === []) {
            $lines[] = '	respond "No tenants configured" 503';
            $lines[] = '}';
            $lines[] = '';

            return implode("\n", $lines);
        }

        foreach ($tenantPlans as $plan) {
            $matcher = '@tenant' . $plan['id'];
            $upstream = $this->tenantUpstream($plan['port']);
            $lines[] = "	{$matcher} host {$plan['domain']}";
            $lines[] = "	handle {$matcher} {";
            $lines[] = "		reverse_proxy {$upstream} {";
            $lines[] = '			header_up Host {http.request.host}';
            $lines[] = '			header_up X-Forwarded-Proto {http.request.header.X-Forwarded-Proto}';
            $lines[] = '			header_up X-Forwarded-For {http.request.remote.host}';
            $lines[] = '			flush_interval -1';
            $lines[] = '		}';
            $lines[] = '	}';
            $lines[] = '';
        }

        $lines[] = '	handle {';
        $lines[] = '		respond "Unknown tenant host" 502';
        $lines[] = '	}';
        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function renderEdgeSupervisorProgram(): string
    {
        $binary = config('octane_tenants.frankenphp_binary');
        $caddyfile = config('octane_tenants.edge_caddyfile');
        $directory = config('octane_tenants.app_directory');
        $user = config('octane_tenants.supervisor_user');

        return $this->renderSupervisorIni(self::EDGE_PROGRAM, [
            'command' => "{$binary} run --config {$caddyfile} --adapter caddyfile",
            'directory' => $directory,
            'user' => $user,
            'environment' => $this->defaultEnvironment(),
        ]);
    }

    /**
     * @param  array{id:int,domain:string,port:int,admin_port:int}  $plan
     */
    public function renderTenantSupervisorProgram(array $plan): string
    {
        $directory = config('octane_tenants.app_directory');
        $user = config('octane_tenants.supervisor_user');
        $php = config('octane_tenants.php_binary');
        $host = config('octane_tenants.tenant_host');
        $artisan = $directory . '/artisan';

        $command = implode(' ', [
            $php,
            $artisan,
            'octane:start',
            '--server=frankenphp',
            '--host=' . $host,
            '--port=' . $plan['port'],
            '--admin-port=' . $plan['admin_port'],
            ...$this->octaneWorkerCliFlags(),
            '--caddyfile=Caddyfile',
        ]);

        return $this->renderSupervisorIni($this->tenantProgramName($plan['id']), [
            'command' => $command,
            'directory' => $directory,
            'user' => $user,
            'environment' => $this->tenantEnvironment($plan['id']),
        ]);
    }

    public function renderSingleSupervisorProgram(): string
    {
        $directory = config('octane_tenants.app_directory');
        $user = config('octane_tenants.supervisor_user');
        $php = config('octane_tenants.php_binary');
        $host = config('octane_tenants.edge_host');
        $port = (int) config('octane_tenants.edge_port');
        $artisan = $directory . '/artisan';

        $command = implode(' ', [
            $php,
            $artisan,
            'octane:start',
            '--server=frankenphp',
            '--host=' . $host,
            '--port=' . $port,
            ...$this->octaneWorkerCliFlags(),
            '--caddyfile=Caddyfile',
        ]);

        return $this->renderSupervisorIni(self::SINGLE_PROGRAM, [
            'command' => $command,
            'directory' => $directory,
            'user' => $user,
            'environment' => $this->defaultEnvironment(),
        ]);
    }

    public function tenantProgramName(int $tenantId): string
    {
        return 'octane-tenant-' . $tenantId;
    }

    /**
     * @param  array{command:string,directory:string,user:string,environment:string}  $attrs
     */
    private function renderSupervisorIni(string $program, array $attrs): string
    {
        $lines = [
            '; ' . self::MANAGED_MARKER,
            "[program:{$program}]",
            'command = ' . $attrs['command'],
            'directory = ' . $attrs['directory'],
            'user = ' . $attrs['user'],
            'process_name = %(program_name)s_%(process_num)02d',
            'numprocs = 1',
            'autostart = true',
            'autorestart = true',
            'startsecs = 0',
            'redirect_stderr = true',
            'stdout_logfile = /dev/stdout',
            'stdout_logfile_maxbytes = 0',
            'stderr_logfile_maxbytes = 0',
            'environment = ' . $attrs['environment'],
            '',
        ];

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function octaneWorkerCliFlags(): array
    {
        return [
            '--max-requests=' . (int) config('octane_tenants.max_requests'),
            '--workers=' . (int) config('octane_tenants.workers'),
            '--task-workers=' . (int) config('octane_tenants.task_workers'),
        ];
    }

    private function defaultEnvironment(): string
    {
        return 'HOME="/home/nginx",XDG_CONFIG_HOME="/tmp/.config",XDG_DATA_HOME="/tmp/.local/share"';
    }

    private function tenantEnvironment(int $tenantId): string
    {
        return 'TENANT="' . $tenantId . '",' . $this->defaultEnvironment();
    }

    private function tenantUpstream(int $port): string
    {
        return config('octane_tenants.tenant_host') . ':' . $port;
    }
}