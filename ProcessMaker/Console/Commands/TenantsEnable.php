<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Multitenancy\Tenant;

class TenantsEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:enable {--migrate: migrate the existing instance to a new tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable tenant support';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $migrate = $this->option('migrate', false);

        $landlordDbName = config('database.connections.landlord.database');
        if ($migrate) {
            // IF migrating, we keep the DB_DATABASE as the tenant database and create a new landlord database instead
            // Otherwise, the DB_DATABASE is the landlord
            $landlordDbName = 'landlord';
            config(['database.connections.landlord.database' => $landlordDbName]);
        }

        // create the landlord database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$landlordDbName}`");

        // migrate the landlord database
        Artisan::call('migrate', ['--path' => 'database/migrations/landlord', '--database' => 'landlord']);
        $this->info(Artisan::output());

        if (!$migrate) {
            $this->info('Tenant support enabled successfully');

            return;
        }

        // Create the tenant from the existing instance
        Artisan::call('tenants:create', [
            '--database' => config('database.connections.processmaker.database'),
            '--url' => config('app.url'),
            '--storage-folder' => base_path('storage'),
            '--name' => config('app.name'),
        ]);
        $this->info(Artisan::output());

        // Leave an empty folders to stop some providers from complaining
        $frameworkViewsDir = base_path('storage/framework/views');
        mkdir($frameworkViewsDir, 0755, true);
        $skinsBaseDir = base_path('storage/skins/base');
        mkdir($skinsBaseDir, 0755, true);

        $this->info('Tenant support enabled successfully and migrated to a new tenant');
    }
}
