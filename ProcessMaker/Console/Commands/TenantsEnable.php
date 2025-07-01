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

            // Check if rsync exists
            if (!exec('which rsync')) {
                $this->error('rsync is not installed');

                return 1;
            }

            // check if DB_DATABASE is null
            if (!env('DB_DATABASE')) {
                $this->error('DB_DATABASE is null so multitenancy is already enabled. Exiting.');

                return 1;
            }
        }

        $landlordDbName = config('database.connections.landlord.database');
        if ($migrate) {
            // If migrating, the DB_DATABASE is the tenant database. Create a new landlord database instead
            // Otherwise, the DB_DATABASE is the landlord
            $landlordDbName = 'landlord';
            config(['database.connections.landlord.database' => $landlordDbName]);

            // Now, to ensure we are using the right DB, set the DB_DATABASE in the .env to null
            $this->modifyEnvForDatabaseName('null');

            // create the landlord database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$landlordDbName}`");
        }

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

        // First, copy the existing storage folder to a temp location
        $tempStorageFolder = base_path('storage-temp');
        exec("rsync -avz --exclude='tenant_*' " . base_path('storage') . ' ' . $tempStorageFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to copy storage folder to temp location');
            $this->error(implode("\n", $output));

            return 1;
        }
        $this->info(implode("\n", $output));

        // Now, create the tenant. The folder will be moved to the new tenant after the creation
        // and the $tempStorageFolder will no longer exist.
        $exitCode = Artisan::call('tenants:create', [
            '--database' => env('DB_DATABASE'),
            '--url' => config('app.url'),
            '--storage-folder' => $tempStorageFolder,
            '--name' => config('app.name'),
            '--app-key' => config('app.key'),
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

        if (preg_match('/DB_DATABASE=.*/', $env)) {
            // Update existing DB_DATABASE line
            $env = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $databaseName, $env);
        } else {
            // Add DB_DATABASE to the end of the file
            $env .= "\nDB_DATABASE=" . $databaseName;
        }

        file_put_contents($envFile, $env);
    }
}
