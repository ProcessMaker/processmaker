<?php

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Services\PluginRuntimeReload;
use Tests\TestCase;

class PluginRuntimeReloadLocalRecycleTest extends TestCase
{
    public function testRecycleLocalRuntimeInSingleModeRestartsOctaneProgram(): void
    {
        config(['app.multitenancy' => false]);
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);
        Artisan::shouldReceive('call')
            ->once()
            ->with('horizon:terminate', ['--no-interaction' => true])
            ->andReturn(0);

        $this->assertTrue((new PluginRuntimeReload())->recycleLocalRuntime());

        Process::assertRan(fn ($process) => is_array($process->command)
            && $process->command === ['supervisorctl', 'restart', 'octane:*']);
    }

    public function testRecycleLocalRuntimeInMultitenantModeUsesTenantProgram(): void
    {
        config(['app.multitenancy' => true]);
        app()->instance('currentTenant', new class {
            public function getKey(): int
            {
                return 5;
            }
        });
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);
        Artisan::shouldReceive('call')
            ->once()
            ->with('horizon:terminate', ['--no-interaction' => true])
            ->andReturn(0);

        $this->assertTrue((new PluginRuntimeReload())->recycleLocalRuntime());

        Process::assertRan(fn ($process) => is_array($process->command)
            && $process->command === ['supervisorctl', 'restart', 'octane-tenant-5:*']);
    }

    public function testRecycleLocalRuntimeFailsWithoutTenantInMultitenantMode(): void
    {
        config(['app.multitenancy' => true]);

        $this->assertFalse((new PluginRuntimeReload())->recycleLocalRuntime());
    }
}