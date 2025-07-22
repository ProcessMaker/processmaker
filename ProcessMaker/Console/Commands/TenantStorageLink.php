<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TenantStorageLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:storage-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the symbolic link for the current tenant storage directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenant = app('currentTenant');

        if (!$tenant) {
            $this->error('This command must be run within a tenant context using tenants:artisan');

            return 1;
        }

        $this->createStorageLink($tenant->id);
    }

    /**
     * Create symbolic link for the current tenant
     *
     * @param int $tenantId
     * @return void
     */
    protected function createStorageLink($tenantId)
    {
        $this->info('Creating storage link for tenant ' . $tenantId . '...');

        // Define paths
        $tenantStoragePath = storage_path('/app/public');
        $publicPath = public_path('storage/tenant_' . $tenantId);

        // Create tenant storage directory if it doesn't exist
        if (!File::exists($tenantStoragePath)) {
            $this->info('Creating tenant storage directory: ' . $tenantStoragePath);
            File::makeDirectory($tenantStoragePath, 0755, true);
        }

        // Create parent directory for the symbolic link if it doesn't exist
        $parentDir = dirname($publicPath);
        if (!File::exists($parentDir)) {
            $this->info('Creating parent directory: ' . $parentDir);
            File::makeDirectory($parentDir, 0755, true);
        }

        // Remove existing link or directory if it exists
        if (File::exists($publicPath)) {
            // We can't use `confirm` here because this command will be run without any user interaction
            // if (!$this->confirm('The symbolic link for tenant ' . $tenantId . ' already exists. Do you want to recreate it?')) {
            //     return;
            // }
            if (is_link($publicPath)) {
                File::delete($publicPath);
            } else {
                File::deleteDirectory($publicPath);
            }
        }

        try {
            // Create the symbolic link
            $this->info('Creating symbolic link from ' . $tenantStoragePath . ' to ' . $publicPath);

            // Use absolute paths for symlink
            $absoluteStoragePath = realpath($tenantStoragePath);
            if (!$absoluteStoragePath) {
                throw new \Exception('Could not resolve absolute path for: ' . $tenantStoragePath);
            }

            if (!symlink($absoluteStoragePath, $publicPath)) {
                throw new \Exception('Failed to create symbolic link. Check permissions and paths.');
            }

            $this->info("Symbolic link for tenant {$tenantId} created successfully.");
        } catch (\Exception $e) {
            $this->error('Could not create symbolic link for tenant ' . $tenantId . ': ' . $e->getMessage());
            $this->error('Source path: ' . $tenantStoragePath);
            $this->error('Target path: ' . $publicPath);

            // Additional debugging information
            $this->info("\nDebugging information:");
            $this->info('Source exists: ' . (File::exists($tenantStoragePath) ? 'Yes' : 'No'));
            $this->info('Source is readable: ' . (is_readable($tenantStoragePath) ? 'Yes' : 'No'));
            $this->info('Parent directory exists: ' . (File::exists($parentDir) ? 'Yes' : 'No'));
            $this->info('Parent directory is writable: ' . (is_writable($parentDir) ? 'Yes' : 'No'));
        }
    }
}
