<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TenantsEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:enable {--migrate : migrate the existing instance to a new tenant}';

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

        if ($migrate) {
            // check if the config cache file exists
            if (File::exists(base_path('bootstrap/cache/config.php'))) {
                $this->error('Cannot migrate if the config cache file exists');

                return 1;
            }
        }

        $landlordDbName = config('database.connections.landlord.database');
        if ($migrate) {
            // If migrating, the DB_DATABASE is the tenant database. Create a new landlord database instead
            // Otherwise, the DB_DATABASE is the landlord
            $landlordDbName = 'landlord';
            config(['database.connections.landlord.database' => $landlordDbName]);
            $this->modifyEnvForDatabaseName($landlordDbName);
        }

        // create the landlord database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$landlordDbName}`");

        // migrate the landlord database
        $this->info("Database: {$landlordDbName}");
        $exitCode = Artisan::call('migrate', ['--path' => 'database/migrations/landlord', '--database' => 'landlord'], $this->output);

        if ($exitCode !== 0) {
            $this->error('Failed to migrate landlord database');

            return 1;
        }

        if (!$migrate) {
            $this->info('Tenant support enabled successfully');

            return;
        }

        // Create the tenant from the existing instance

        $exitCode = Artisan::call('tenants:create', [
            '--database' => env('DB_DATABASE'),
            '--url' => config('app.url'),
            '--storage-folder' => base_path('storage'),
            '--name' => config('app.name'),
        ], $this->output);

        if ($exitCode !== 0) {
            $this->error('Failed to create tenant');

            return 1;
        }

        $this->info('Tenant support enabled successfully and migrated to a new tenant');
    }

    private function modifyEnvForDatabaseName($databaseName)
    {
        $envFile = base_path('.env');
        $env = file_get_contents($envFile);
        $env = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $databaseName, $env);
        file_put_contents($envFile, $env);
    }
}
