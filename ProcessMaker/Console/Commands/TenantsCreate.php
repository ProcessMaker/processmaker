<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ProcessMaker\Multitenancy\Tenant;

class TenantsCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:create {--name=} {--url=} {--database=} {--username=} {--password=} {--storage-folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Datbase name should be .env DB_DATABASE + -tenant-<id>
        // For example pm4_ci-003c0e7501-tenant-1

        // For CI Instnaces, Domain is https://t1.ci-003c0e7501.engk8s.processmaker.net

        // In prod, domain won't change

        $requiredOptions = ['name', 'database', 'url'];
        foreach ($requiredOptions as $option) {
            if (!$this->option($option)) {
                $this->error('These settings are required: ' . implode(', ', $requiredOptions));

                return;
            }
        }

        $domain = parse_url($this->option('url'), PHP_URL_HOST);

        if (Tenant::where('domain', $domain)->exists()) {
            $this->info('Tenant already exists for domain: ' . $domain . '. Exiting.');

            return;
        }

        $key = 'base64:' . base64_encode(
            Encrypter::generateKey(config('app.cipher'))
        );
        $key = Crypt::encryptString($key);

        // insert into the tenants table
        $tenant = Tenant::create([
            'domain' => $domain,
            'name' => $this->option('name'),
            'database' => $this->option('database'),
            'username' => $this->option('username', null),
            'password' => $this->option('password', null),
            'config' => ['app.url' => $this->option('url'), 'app.key' => $key],
        ]);

        // Setup storage
        $tenantStoragePath = base_path('storage/tenant_' . $tenant->id);
        $needsStorage = true;

        // Check if the storage folder already exists
        if (File::isDirectory($tenantStoragePath)) {
            $needsStorage = false;
        }

        // Check if an existing storage folder is provided
        $storageFolderOption = $this->option('storage-folder', null);
        if ($needsStorage && $storageFolderOption && File::isDirectory($storageFolderOption)) {
            $this->info('Moving storage folder to ' . $tenantStoragePath);
            rename($storageFolderOption, $tenantStoragePath);
            $needsStorage = false;
        }

        // If the storage folder does not exist, create it
        if ($needsStorage) {
            mkdir($tenantStoragePath);
            $subfolders = [
                'app',
                'app/private',
                'app/public',
                'app/public/profile',
                'app/public/setting',
                'app/private/settings',
                'app/private/web_services',
                'app/public/tmp',
                'samlidp',
                'decision-tables',
                'framework',
                'framework/views',
                'framework/cache',
                'framework/cache/data',
                'framework/sessions',
                'skins',
                'skins/base',
                'api-docs',
            ];
            foreach ($subfolders as $subfolder) {
                mkdir($tenantStoragePath . '/' . $subfolder);
            }
        }

        // Setup database
        // DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->option('database')}'");
        DB::statement("CREATE DATABASE `{$this->option('database')}`");

        // Create the database
        // SKIPPING - migrate/upgrade should be done on its own
        // if (!$tenantDbExists) {
        //     DB::statement("CREATE DATABASE `{$this->option('database')}`");
        //     $this->tenantArtisan('migrate --seed --force', $tenant->id);

        //     // Call the upgrade commands
        //     $this->tenantArtisan('upgrade', $tenant->id);
        // }

        // Crate the config cache subfolder
        //
        // SKIPPING FOR NOW - we because we set config vars at runtime in SwitchTenant.php
        // so caching a tenant-specific config has no effect
        //
        // $configCachePath = base_path('bootstrap/cache/tenant_' . $tenant->id);
        // if (!file_exists($configCachePath)) {
        //     mkdir($configCachePath);
        // }
        // $this->tenantArtisan('config:cache', $tenant->id);

        // Create storage link for the new tenant
        $this->tenantArtisan('tenant:storage-link', $tenant->id);

        $this->info('Tenant created successfully');
    }

    private function tenantArtisan($command, $tenantId)
    {
        Artisan::call('tenants:artisan', ['artisanCommand' => $command, '--tenant' => $tenantId]);
        $this->info(Artisan::output());
    }
}
