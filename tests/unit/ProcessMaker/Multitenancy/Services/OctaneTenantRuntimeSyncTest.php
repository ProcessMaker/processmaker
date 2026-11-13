<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Multitenancy\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Multitenancy\Services\OctaneTenantRuntimeSync;
use ProcessMaker\Multitenancy\Tenant;
use Tests\TestCase;

class OctaneTenantRuntimeSyncTest extends TestCase
{
    private string $supervisorDir;

    private string $edgeCaddyfile;

    private string $appDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisorDir = sys_get_temp_dir() . '/pm-octane-sync-sup-' . uniqid();
        $this->edgeCaddyfile = sys_get_temp_dir() . '/pm-octane-sync-edge-' . uniqid() . '.caddy';
        $this->appDirectory = sys_get_temp_dir() . '/pm-octane-sync-app-' . uniqid();

        File::makeDirectory($this->supervisorDir, 0755, true);
        File::makeDirectory($this->appDirectory, 0755, true);

        config([
            'app.multitenancy' => false,
            'octane_tenants.supervisor_dir' => $this->supervisorDir,
            'octane_tenants.edge_caddyfile' => $this->edgeCaddyfile,
            'octane_tenants.frankenphp_binary' => $this->appDirectory . '/frankenphp',
            'octane_tenants.app_directory' => $this->appDirectory,
            'octane_tenants.supervisor_user' => 'nginx',
            'octane_tenants.php_binary' => 'php',
            'octane_tenants.edge_host' => '127.0.0.1',
            'octane_tenants.edge_port' => 8001,
            'octane_tenants.tenant_host' => '127.0.0.1',
            'octane_tenants.tenant_port_base' => 8100,
            'octane_tenants.tenant_admin_port_base' => 9100,
            'octane_tenants.supervisorctl_bin' => 'supervisorctl',
            'octane_tenants.supervisorctl_config' => null,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->supervisorDir);
        File::deleteDirectory($this->appDirectory);
        if (is_file($this->edgeCaddyfile)) {
            unlink($this->edgeCaddyfile);
        }

        parent::tearDown();
    }

    public function test_single_mode_writes_octane_ini_and_removes_tenant_runtime_files(): void
    {
        File::put($this->supervisorDir . '/octane-edge.ini', 'old-edge');
        File::put($this->supervisorDir . '/octane-tenant-1.ini', 'old-tenant');
        File::put($this->edgeCaddyfile, 'old-caddy');

        $result = (new OctaneTenantRuntimeSync())->sync();

        $this->assertSame('single', $result['mode']);
        $this->assertFileExists($this->supervisorDir . '/octane.ini');
        $this->assertFileDoesNotExist($this->supervisorDir . '/octane-edge.ini');
        $this->assertFileDoesNotExist($this->supervisorDir . '/octane-tenant-1.ini');
        $this->assertFileDoesNotExist($this->edgeCaddyfile);

        $octaneIni = file_get_contents($this->supervisorDir . '/octane.ini');
        $this->assertStringContainsString(OctaneTenantRuntimeSync::MANAGED_MARKER, $octaneIni);
        $this->assertStringContainsString('[program:octane]', $octaneIni);
        $this->assertStringContainsString('--port=8001', $octaneIni);
        $this->assertStringNotContainsString('TENANT=', $octaneIni);
    }

    public function test_multitenant_mode_writes_edge_and_per_tenant_programs(): void
    {
        config(['app.multitenancy' => true]);
        File::put($this->supervisorDir . '/octane.ini', 'legacy-single');

        $sync = new class extends OctaneTenantRuntimeSync
        {
            protected function tenants(): Collection
            {
                return collect([
                    $this->fakeTenant(1, 'tenant-one.example.com'),
                    $this->fakeTenant(2, 'tenant-two.example.com'),
                ]);
            }

            private function fakeTenant(int $id, string $domain): Tenant
            {
                $tenant = new Tenant();
                $tenant->forceFill([
                    'id' => $id,
                    'name' => 'Tenant ' . $id,
                    'domain' => $domain,
                ]);

                return $tenant;
            }
        };

        $result = $sync->sync();

        $this->assertSame('multitenant', $result['mode']);
        $this->assertFileDoesNotExist($this->supervisorDir . '/octane.ini');
        $this->assertFileExists($this->supervisorDir . '/octane-edge.ini');
        $this->assertFileExists($this->supervisorDir . '/octane-tenant-1.ini');
        $this->assertFileExists($this->supervisorDir . '/octane-tenant-2.ini');
        $this->assertFileExists($this->edgeCaddyfile);

        $edge = file_get_contents($this->edgeCaddyfile);
        $this->assertStringContainsString('127.0.0.1:8001', $edge);
        $this->assertStringContainsString('host tenant-one.example.com', $edge);
        $this->assertStringContainsString('reverse_proxy 127.0.0.1:8101', $edge);
        $this->assertStringContainsString('reverse_proxy 127.0.0.1:8102', $edge);

        $edgeIni = file_get_contents($this->supervisorDir . '/octane-edge.ini');
        $this->assertStringContainsString('[program:octane-edge]', $edgeIni);
        $this->assertStringContainsString('frankenphp run --config', $edgeIni);
        $this->assertStringContainsString($this->edgeCaddyfile, $edgeIni);

        $tenant1 = file_get_contents($this->supervisorDir . '/octane-tenant-1.ini');
        $this->assertStringContainsString('[program:octane-tenant-1]', $tenant1);
        $this->assertStringContainsString('--port=8101', $tenant1);
        $this->assertStringContainsString('--admin-port=9101', $tenant1);
        $this->assertStringContainsString('TENANT="1"', $tenant1);

        $this->assertCount(2, $result['tenants']);
    }

    public function test_dry_run_does_not_write_files(): void
    {
        $result = (new OctaneTenantRuntimeSync())->sync(dryRun: true);

        $this->assertSame('single', $result['mode']);
        $this->assertFileDoesNotExist($this->supervisorDir . '/octane.ini');
        $this->assertContains($this->supervisorDir . '/octane.ini', $result['written']);
    }

    public function test_apply_supervisor_runs_reread_and_update(): void
    {
        Process::fake([
            '*' => Process::result('ok', exitCode: 0),
        ]);

        $output = (new OctaneTenantRuntimeSync())->applySupervisor();

        $this->assertStringContainsString('ok', $output);
        Process::assertRan(function ($process) {
            $command = is_array($process->command) ? $process->command : [];

            return ($command[0] ?? null) === 'supervisorctl'
                && in_array(($command[1] ?? null), ['reread', 'update'], true);
        });
    }

    public function test_edge_caddyfile_renderer_handles_empty_tenant_list(): void
    {
        $contents = (new OctaneTenantRuntimeSync())->renderEdgeCaddyfile([]);

        $this->assertStringContainsString('respond "No tenants configured" 503', $contents);
    }

    public function test_artisan_command_dry_run_succeeds_in_single_mode(): void
    {
        $this->artisan('tenants:octane-sync', ['--dry-run' => true])
            ->expectsOutputToContain('Mode: single')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->supervisorDir . '/octane.ini');
    }
}
