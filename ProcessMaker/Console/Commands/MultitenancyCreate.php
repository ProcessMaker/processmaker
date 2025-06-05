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
    protected $signature = 'multitenancy:create {--name=} {--database=} {--url=}';

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
        if (!$this->option('name') || !$this->option('database') || !$this->option('url')) {
            $this->error('Name, database, and url are required');

            return;
        }

        $domain = parse_url($this->option('url'), PHP_URL_HOST);

        // insert into the tenants table
        $tenant = Tenant::create([
            'domain' => $domain,
            'name' => $this->option('name'),
            'database' => $this->option('database'),
            'config' => ['app.url' => $this->option('url')],
        ]);

        // build the folder structure
        $base = base_path('storage/tenant_' . $tenant->id);
        mkdir($base);
        $subfolders = [
            'app',
            'app/private',
            'app/public',
            'framework',
            'framework/views',
            'framework/cache',
            'framework/cache/data',
            'framework/sessions',
            'skins',
            'skins/base',
        ];
        foreach ($subfolders as $subfolder) {
            mkdir($base . '/' . $subfolder);
        }

        // Create the database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$this->option('database')}`");
        $this->tenantArtisan('migrate --seed --force', $tenant->id);

        // Add passport keys
        $this->tenantArtisan('passport:keys', $tenant->id);

        // Call the upgrade commands
        $this->tenantArtisan('upgrade', $tenant->id);

        // Crate the config cache subfolder
        $configCachePath = base_path('bootstrap/cache/tenant_' . $tenant->id);
        if (!file_exists($configCachePath)) {
            mkdir($configCachePath);
        }
        $this->tenantArtisan('config:cache', $tenant->id);

        // Create storage link for the new tenant
        $this->call('tenant:storage-link', ['--tenant' => $tenant->id]);

        $this->info('Tenant created successfully');
    }

    private function tenantArtisan($command, $tenantId)
    {
        Artisan::call('tenants:artisan', ['artisanCommand' => $command, '--tenant' => $tenantId]);
    }
}
