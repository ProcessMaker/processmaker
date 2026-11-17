<?php

declare(strict_types=1);

namespace ProcessMaker\Multitenancy\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Multitenancy\Tenant;
use RuntimeException;

/**
 * Materializes Caddyfile.edge + supervisor programs for single-Octane or
 * one-Octane-per-tenant (multitenancy) runtimes.
 */
class OctaneTenantRuntimeSync
{
    public const MANAGED_MARKER = OctaneRuntimeRenderer::MANAGED_MARKER;

    public const SINGLE_PROGRAM = OctaneRuntimeRenderer::SINGLE_PROGRAM;

    public const EDGE_PROGRAM = OctaneRuntimeRenderer::EDGE_PROGRAM;

    private OctaneRuntimeRenderer $renderer;

    private OctaneRuntimeFilesystem $filesystem;

    public function __construct(
        ?OctaneRuntimeRenderer $renderer = null,
        ?OctaneRuntimeFilesystem $filesystem = null,
    ) {
        $this->renderer = $renderer ?? new OctaneRuntimeRenderer();
        $this->filesystem = $filesystem ?? new OctaneRuntimeFilesystem();
    }

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
        $this->filesystem->ensureSupervisorDirectory($dryRun);

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
     * @param  list<array{id:int,domain:string,port:int,admin_port:int}>  $tenantPlans
     */
    public function renderEdgeCaddyfile(array $tenantPlans): string
    {
        return $this->renderer->renderEdgeCaddyfile($tenantPlans);
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
        $removed = $this->filesystem->removeRuntimeFiles(
            [
                $this->filesystem->supervisorPath($this->filesystem->edgeIniName()),
                ...$this->filesystem->existingTenantIniPaths(),
                config('octane_tenants.edge_caddyfile'),
            ],
            $dryRun
        );

        $octaneIni = $this->filesystem->supervisorPath($this->filesystem->singleIniName());
        $contents = $this->renderer->renderSingleSupervisorProgram();
        $this->filesystem->writeFile($octaneIni, $contents, $dryRun);
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
            ->map(fn (array $plan) => $this->filesystem->supervisorPath(
                $this->filesystem->tenantIniName($plan['id'])
            ))
            ->all();

        $removed = $this->filesystem->removeRuntimeFiles(
            [
                $this->filesystem->supervisorPath($this->filesystem->singleIniName()),
                ...array_diff($this->filesystem->existingTenantIniPaths(), $desiredTenantInis),
            ],
            $dryRun
        );

        $written = [];

        $edgeCaddyfile = config('octane_tenants.edge_caddyfile');
        $this->filesystem->writeFile($edgeCaddyfile, $this->renderer->renderEdgeCaddyfile($tenantPlans), $dryRun);
        $written[] = $edgeCaddyfile;

        $edgeIni = $this->filesystem->supervisorPath($this->filesystem->edgeIniName());
        $this->filesystem->writeFile($edgeIni, $this->renderer->renderEdgeSupervisorProgram(), $dryRun);
        $written[] = $edgeIni;

        foreach ($tenantPlans as $plan) {
            $path = $this->filesystem->supervisorPath($this->filesystem->tenantIniName($plan['id']));
            $this->filesystem->writeFile($path, $this->renderer->renderTenantSupervisorProgram($plan), $dryRun);
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