<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Tenant;

class MultitenancyEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multitenancy:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing instnace to multitenancy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configCachePath = base_path('bootstrap/cache/config.php');
        if (file_exists($configCachePath)) {
            unlink($configCachePath);
        }

        $dbName = config('database.connections.landlord.database');

        // migrate the landlord database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`");

        Artisan::call('migrate', ['--path' => 'database/migrations/landlord', '--database' => 'landlord']);

        // Get just the domain from the app url
        $domain = parse_url(Env::get('APP_URL'), PHP_URL_HOST);

        // Create the first tenant
        $tenant = Tenant::updateOrCreate([
            'domain' => $domain,
        ], [
            'name' => config('app.name'),
            'database' => Env::get('DB_DATABASE'),
            'config' => [
                'app.url' => config('app.url'),
            ],
        ]);

        // make storage/tenant_{id}
        $storageDir = base_path('storage/tenant_' . $tenant->id);
        if (!file_exists($storageDir)) {
            mkdir($storageDir, 0755);
        }

        // Move the existing storage into storage/tenant_{id} (except for tenant_*)
        $storageFiles = glob(base_path('storage/*'));
        foreach ($storageFiles as $file) {
            if (preg_match('/^(tenant_|logs)/', basename($file))) {
                continue;
            }
            rename($file, $storageDir . '/' . basename($file));
        }

        // Leave an empty folders to stop some providers from complaining
        $frameworkViewsDir = base_path('storage/framework/views');
        mkdir($frameworkViewsDir, 0755, true);
        $skinsBaseDir = base_path('storage/skins/base');
        mkdir($skinsBaseDir, 0755, true);

        // make boostrap/cache/tenant_{id}
        $configCacheDir = base_path('bootstrap/cache/tenant_' . $tenant->id);
        if (!file_exists($configCacheDir)) {
            mkdir($configCacheDir, 0755);
        }

        // cache config in bootstrap/cache/tenant_{id}/config.php
        Artisan::call('tenants:artisan', ['artisanCommand' => 'config:cache', '--tenant' => $tenant->id]);

        $this->info('Multitenancy enabled successfully');
    }
}
