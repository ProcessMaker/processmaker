<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;

class TenantsVerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:verify {--verify-against= : The tenant ID to verify against}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify tenant configuration and storage paths';

    /**
     * Strip protocol from URL
     *
     * @param string $url
     * @return string
     */
    private function stripProtocol(string $url): string
    {
        return preg_replace('#^https?://#', '', $url);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentTenant = null;
        if (app()->has('currentTenant')) {
            $currentTenant = app('currentTenant');
        }

        $verifyAgainstId = $this->option('verify-against');

        if (!$currentTenant) {
            $this->error('No current tenant found');

            return;
        }

        $this->info('Current Tenant ID: ' . $currentTenant->id);
        $this->line('----------------------------------------');

        // Expected paths and configurations
        $expectedStoragePath = base_path('storage/tenant_' . $currentTenant->id);
        $actualConfigs = [
            'filesystems.disks.local.root' => storage_path('app'),
            'cache.prefix' => config('cache.prefix'),
            'app.url' => config('app.url'),
        ];

        // Display current values
        $this->info('Current Storage Path: ' . storage_path());
        $this->line('----------------------------------------');

        $this->info('Current Configuration Values:');
        foreach ($actualConfigs as $key => $expectedValue) {
            $currentValue = config($key);
            $this->line("{$key}: {$currentValue}");
        }

        // If verify-against is specified, perform verification
        if ($verifyAgainstId) {
            $this->line('----------------------------------------');
            $this->info("Verifying against tenant ID: {$verifyAgainstId}");

            $expectedStoragePath = base_path('storage/tenant_' . $verifyAgainstId);
            $expectedConfigs = [
                'filesystems.disks.local.root' => $expectedStoragePath . '/app',
                'cache.prefix' => 'tenant_id_' . $verifyAgainstId,
                'app.url' => config('app.url'),
            ];

            $hasMismatch = false;

            // Verify storage path
            if (storage_path() !== $expectedStoragePath) {
                $this->error('Storage path mismatch!');
                $this->line("Expected: {$expectedStoragePath}");
                $this->line('Current: ' . storage_path());
                $hasMismatch = true;
            }

            // Verify tenant URL if tenant exists
            $verifyTenant = Tenant::find($verifyAgainstId);
            if ($verifyTenant && $verifyTenant->domain !== $this->stripProtocol(config('app.url'))) {
                $this->error('Tenant URL mismatch!');
                $this->line("Expected: {$verifyTenant->domain}");
                $this->line('Current: ' . config('app.url'));
                $hasMismatch = true;
            }

            // Verify config values
            foreach ($expectedConfigs as $key => $expectedValue) {
                $currentValue = config($key);
                if ($currentValue !== $expectedValue) {
                    $this->error("Config mismatch for {$key}!");
                    $this->line("Expected: {$expectedValue}");
                    $this->line("Current: {$currentValue}");
                    $hasMismatch = true;
                }
            }

            if (!$hasMismatch) {
                $this->info('All configurations match as expected!');
            }

            return $hasMismatch ? Command::FAILURE : Command::SUCCESS;
        }

        return Command::SUCCESS;
    }
}
