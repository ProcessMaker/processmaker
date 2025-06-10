<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Tenant;

class MultitenancyCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multitenancy:create';

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

        $requiredOptions = ['name', 'database', 'url', 'username', 'password'];
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

        // insert into the tenants table
        $tenant = Tenant::create([
            'domain' => $domain,
            'name' => $this->option('name'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'config' => ['app.url' => $this->option('url')],
        ]);

        // build the folder structure

        // Do not do when we copied the existing tenant storage

        $base = base_path('storage/tenant_' . $tenant->id);
        mkdir($base);
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
            mkdir($base . '/' . $subfolder);
        }

        // Create the database
        if (!exists) {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$this->option('database')}`");
            $this->tenantArtisan('migrate --seed --force', $tenant->id);

            // Add passport keys
            $this->tenantArtisan('passport:keys', $tenant->id);

            // Call the upgrade commands
            $this->tenantArtisan('upgrade', $tenant->id);
        }

        // Crate the config cache subfolder
        $configCachePath = base_path('bootstrap/cache/tenant_' . $tenant->id);
        if (!file_exists($configCachePath)) {
            mkdir($configCachePath);
        }
        $this->tenantArtisan('config:cache', $tenant->id);

        // Create storage link for the new tenant
        $this->tenantArtisan('tenant:storage-link', $tenant->id);

        $this->info('Tenant created successfully');
    }

    private function tenantArtisan($command, $tenantId)
    {
        Artisan::call('tenants:artisan', ['artisanCommand' => $command, '--tenant' => $tenantId]);
    }
}
