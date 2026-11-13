<?php

declare(strict_types=1);

namespace ProcessMaker\Multitenancy\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Multitenancy\Tenant;
use RuntimeException;

/**
 * Materializes Caddyfile.edge + supervisor programs for single-Octane or
 * one-Octane-per-tenant (multitenancy) runtimes.
 */
class OctaneTenantRuntimeSync
{
    public const MANAGED_MARKER = 'Managed by tenants:octane-sync';

    public const SINGLE_PROGRAM = 'octane';

    public const EDGE_PROGRAM = 'octane-edge';

    /**
     * @return array{
     *     mode: string,
     *     written: list<string>,
     *     removed: list<string>,
     *     tenants: list<array{id:int,domain:string,port:int,admin_port:int}>
     * }
     */
    public function sync(bool $dryRun = false): array
    {
        $this->ensureSupervisorDirectory($dryRun);

        if (!config('app.multitenancy')) {
            return $this->syncSingleOctane($dryRun);
        }

        return $this->syncMultitenantOctane($dryRun);
    }

    /**
     * Apply supervisorctl reread + update after files were written.
     */
    public function applySupervisor(): string
    {
        $command = $this->supervisorctlBase();
        $reread = Process::run([...$command, 'reread']);
        if (!$reread->successful()) {
            throw new RuntimeException('supervisorctl reread failed: ' . $reread->errorOutput());
        }

        $update = Process::run([...$command, 'update']);
        if (!$update->successful()) {
            throw new RuntimeException('supervisorctl update failed: ' . $update->errorOutput());
        }

        return trim($reread->output() . "\n" . $update->output());
    }

    /**
     * @return array{
     *     mode: string,
     *     written: list<string>,
     *     removed: list<string>,
     *     tenants: list<array{id:int,domain:string,port:int,admin_port:int}>
     * }
     */
    private function syncSingleOctane(bool $dryRun): array
    {
        $written = [];
        $removed = $this->removeRuntimeFiles(
            [
                $this->supervisorPath($this->edgeIniName()),
                ...$this->existingTenantIniPaths(),
                config('octane_tenants.edge_caddyfile'),
            ],
            $dryRun
        );

        $octaneIni = $this->supervisorPath($this->singleIniName());
        $contents = $this->renderSingleSupervisorProgram();
        $this->writeFile($octaneIni, $contents, $dryRun);
        $written[] = $octaneIni;

        return [
            'mode' => 'single',
            'written' => $written,
            'removed' => $removed,
            'tenants' => [],
        ];
    }

    /**
     * @return array{
     *     mode: string,
     *     written: list<string>,
     *     removed: list<string>,
     *     tenants: list<array{id:int,domain:string,port:int,admin_port:int}>
     * }
     */
    private function syncMultitenantOctane(bool $dryRun): array
    {
        $tenants = $this->tenants();
        $tenantPlans = $tenants->map(fn (Tenant $tenant) => $this->planForTenant($tenant))->all();

        $desiredTenantInis = collect($tenantPlans)
            ->map(fn (array $plan) => $this->supervisorPath($this->tenantIniName($plan['id'])))
            ->all();

        $removed = $this->removeRuntimeFiles(
            [
                $this->supervisorPath($this->singleIniName()),
                ...array_diff($this->existingTenantIniPaths(), $desiredTenantInis),
            ],
            $dryRun
        );

        $written = [];

        $edgeCaddyfile = config('octane_tenants.edge_caddyfile');
        $this->writeFile($edgeCaddyfile, $this->renderEdgeCaddyfile($tenantPlans), $dryRun);
        $written[] = $edgeCaddyfile;

        $edgeIni = $this->supervisorPath($this->edgeIniName());
        $this->writeFile($edgeIni, $this->renderEdgeSupervisorProgram(), $dryRun);
        $written[] = $edgeIni;

        foreach ($tenantPlans as $plan) {
            $path = $this->supervisorPath($this->tenantIniName($plan['id']));
            $this->writeFile($path, $this->renderTenantSupervisorProgram($plan), $dryRun);
            $written[] = $path;
        }

        return [
            'mode' => 'multitenant',
            'written' => $written,
            'removed' => $removed,
            'tenants' => $tenantPlans,
        ];
    }

    /**
     * @return Collection<int, Tenant>
     */
    protected function tenants(): Collection
    {
        return Tenant::query()
            ->orderBy('id')
            ->get(['id', 'name', 'domain']);
    }

    /**
     * @return array{id:int,domain:string,port:int,admin_port:int}
     */
    private function planForTenant(Tenant $tenant): array
    {
        $id = (int) $tenant->id;
        $domain = trim((string) $tenant->domain);
        if ($domain === '') {
            throw new RuntimeException("Tenant {$id} has an empty domain; cannot build edge routes.");
        }

        return [
            'id' => $id,
            'domain' => $domain,
            'port' => (int) config('octane_tenants.tenant_port_base') + $id,
            'admin_port' => (int) config('octane_tenants.tenant_admin_port_base') + $id,
        ];
    }

    /**
     * @param  list<array{id:int,domain:string,port:int,admin_port:int}>  $tenantPlans
     */
    public function renderEdgeCaddyfile(array $tenantPlans): string
    {
        $host = config('octane_tenants.edge_host');
        $port = (int) config('octane_tenants.edge_port');
        $lines = [
            '# ' . self::MANAGED_MARKER,
            '# Do not edit by hand; run php artisan tenants:octane-sync',
            '{',
            '	admin off',
            '}',
            '',
            "{$host}:{$port} {",
        ];

        if ($tenantPlans === []) {
            $lines[] = '	respond "No tenants configured" 503';
            $lines[] = '}';
            $lines[] = '';

            return implode("\n", $lines);
        }

        foreach ($tenantPlans as $plan) {
            $matcher = '@tenant' . $plan['id'];
            $lines[] = "	{$matcher} host {$plan['domain']}";
            $lines[] = "	handle {$matcher} {";
            $lines[] = "		reverse_proxy {$this->tenantUpstream($plan['port'])}";
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
            '--max-requests=%(ENV_OCTANE_MAX_REQUESTS)s',
            '--workers=%(ENV_OCTANE_WORKERS)s',
            '--task-workers=%(ENV_OCTANE_TASK_WORKERS)s',
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
            '--max-requests=%(ENV_OCTANE_MAX_REQUESTS)s',
            '--workers=%(ENV_OCTANE_WORKERS)s',
            '--task-workers=%(ENV_OCTANE_TASK_WORKERS)s',
            '--caddyfile=Caddyfile',
        ]);

        return $this->renderSupervisorIni(self::SINGLE_PROGRAM, [
            'command' => $command,
            'directory' => $directory,
            'user' => $user,
            'environment' => $this->defaultEnvironment(),
        ]);
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

    private function supervisorPath(string $filename): string
    {
        return rtrim((string) config('octane_tenants.supervisor_dir'), '/') . '/' . $filename;
    }

    private function singleIniName(): string
    {
        return self::SINGLE_PROGRAM . '.ini';
    }

    private function edgeIniName(): string
    {
        return self::EDGE_PROGRAM . '.ini';
    }

    public function tenantProgramName(int $tenantId): string
    {
        return 'octane-tenant-' . $tenantId;
    }

    private function tenantIniName(int $tenantId): string
    {
        return $this->tenantProgramName($tenantId) . '.ini';
    }

    /**
     * @return list<string>
     */
    private function existingTenantIniPaths(): array
    {
        $dir = config('octane_tenants.supervisor_dir');
        if (!is_dir($dir)) {
            return [];
        }

        return array_values(array_filter(
            glob(rtrim($dir, '/') . '/octane-tenant-*.ini') ?: [],
            'is_file'
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function removeRuntimeFiles(array $paths, bool $dryRun): array
    {
        $removed = [];
        foreach (array_unique($paths) as $path) {
            if (!$path || !is_file($path)) {
                continue;
            }
            if (!$dryRun) {
                File::delete($path);
            }
            $removed[] = $path;
        }

        return $removed;
    }

    private function writeFile(string $path, string $contents, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, $contents);
    }

    private function ensureSupervisorDirectory(bool $dryRun): void
    {
        $dir = config('octane_tenants.supervisor_dir');
        if (is_dir($dir)) {
            return;
        }

        if ($dryRun) {
            return;
        }

        File::makeDirectory($dir, 0755, true);
    }

    /**
     * @return list<string>
     */
    private function supervisorctlBase(): array
    {
        $command = [
            config('octane_tenants.supervisorctl_bin', 'supervisorctl'),
        ];

        $configFile = config('octane_tenants.supervisorctl_config');
        if ($configFile) {
            $command[] = '-c';
            $command[] = $configFile;
        }

        return $command;
    }
}
