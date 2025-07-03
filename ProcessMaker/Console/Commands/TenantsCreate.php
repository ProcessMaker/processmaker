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
    protected $signature = 'tenants:create {--name=} {--url=} {--database=} {--username=} {--password=} {--storage-folder=} {--app-key=}';

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

                return 1;
            }
        }

        $domain = parse_url($this->option('url'), PHP_URL_HOST);

        if (Tenant::where('domain', $domain)->exists()) {
            $this->info('Tenant already exists for domain: ' . $domain . '. Exiting.');

            return 1;
        }

        $key = $this->option('app-key');
        if (!$key) {
            $key = 'base64:' . base64_encode(
                Encrypter::generateKey(config('app.cipher'))
            );
        }
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
        if (!File::isDirectory($tenantStoragePath)) {
            mkdir($tenantStoragePath, 0755, true);
        }

        // Check if an existing storage folder is provided
        $storageFolderOption = $this->option('storage-folder', null);
        if ($storageFolderOption && File::isDirectory($storageFolderOption)) {
            $this->info('Moving storage folder to ' . $tenantStoragePath);
            $subfoldersToExclude = '/^(tenant_\d+|logs|transitions)$/i';
            foreach (File::directories($storageFolderOption) as $subfolder) {
                if (preg_match($subfoldersToExclude, basename($subfolder))) {
                    $this->info('Skipping ' . $subfolder);
                    continue;
                }
                $this->info('Moving ' . $subfolder . ' to ' . $tenantStoragePath);
                rename($subfolder, $tenantStoragePath . '/' . basename($subfolder));
            }
        }

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
            if (!File::isDirectory($tenantStoragePath . '/' . $subfolder)) {
                mkdir($tenantStoragePath . '/' . $subfolder, 0755, true);
            }
        }

        // Make sure these folders still exist in landlor storage.
        // Some providers look for these folders before the tenant is set.
        $frameworkViewsDir = base_path('storage/framework/views');
        if (!File::isDirectory($frameworkViewsDir)) {
            mkdir($frameworkViewsDir, 0755, true);
        }
        $skinsBaseDir = base_path('storage/skins/base');
        if (!File::isDirectory($skinsBaseDir)) {
            mkdir($skinsBaseDir, 0755, true);
        }

        // Setup database
        DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `{$this->option('database')}`");

        // Hold off on this for now.
        // $this->tenantArtisan('tenant:storage-link', $tenant->id);

        $this->tenantArtisan('passport:keys --force', $tenant->id);

        $this->info('Tenant created successfully. You need to run migrations, upgrades, and package install commands. Every command should be run with TENANT=' . $tenant->id);
    }

    private function tenantArtisan($command, $tenantId)
    {
        Artisan::call('tenants:artisan', ['artisanCommand' => $command, '--tenant' => $tenantId], $this->output);
    }
}
