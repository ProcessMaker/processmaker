<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDOException;

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

        // Check if multitenancy is enabled
        if (config('app.multitenancy')) {
            $this->error('Multitenancy is already enabled. Remove or set MULTITENANCY=false in .env then run this command again.');

            return 1;
        }

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
        }

        // Get the landlord database name
        $landlordDbName = config('database.connections.landlord.database');

        // Check if the landlord database exists. If not, throw an error.
        try {
            DB::connection('landlord')->getPdo();
        } catch (PDOException $e) {
            $this->error("Landlord database does not exist. Please create the database `{$landlordDbName}` first.");

            return 1;
        }

        // migrate the landlord database
        $this->info("Database: {$landlordDbName}");
        $exitCode = Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations/landlord', '--database' => 'landlord'], $this->output);

        if ($exitCode !== 0) {
            $this->error('Failed to migrate landlord database');

            return 1;
        }

        // Run artisan optimize:clear
        $exitCode = Artisan::call('optimize:clear', [], $this->output);
        if ($exitCode !== 0) {
            $this->error('Failed to clear optimize cache');

            return 1;
        }

        if (!$migrate) {
            // Add or update the MULTITENANCY env var
            $this->addOrUpdateEnvVar('MULTITENANCY', 'true');

            $this->info('Tenant support enabled successfully');

            return;
        }

        /**
         * Begin migration if the --migrate option is provided.
         */

        // First, copy the existing storage folder to a temp location
        $tempStorageFolder = base_path('storage-temp');
        exec("rsync -avz --exclude='tenant_*' " . base_path('storage') . '/ ' . $tempStorageFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to copy storage folder to temp location');
            $this->error(implode("\n", $output));

            return 1;
        }
        $this->info(implode("\n", $output));

        // Next, do the same thing for the lang folder
        $tempLangFolder = base_path('lang-temp');
        exec('rsync -avz ' . resource_path('lang') . '/ ' . $tempLangFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to copy lang folder to temp location');
            $this->error(implode("\n", $output));
        }
        $this->info(implode("\n", $output));

        // Now, create the tenant. The folder will be moved to the new tenant after the creation
        // and the $tempStorageFolder will no longer exist.
        $exitCode = Artisan::call('tenants:create', [
            '--database' => config('database.connections.processmaker.database'),
            '--url' => config('app.url'),
            '--storage-folder' => $tempStorageFolder,
            '--lang-folder' => $tempLangFolder,
            '--name' => config('app.name'),
            '--app-key' => config('app.key'),
        ], $this->output);

        if ($exitCode !== 0) {
            $this->error('Failed to create tenant');

            return 1;
        }

        // Remove temp storage folder
        exec('rm -rf ' . $tempStorageFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to remove temp storage folder');
            $this->error(implode("\n", $output));
        }
        $this->info(implode("\n", $output));

        // Remove temp lang folder
        exec('rm -rf ' . $tempLangFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to remove temp lang folder');
            $this->error(implode("\n", $output));
        }
        $this->info(implode("\n", $output));

        // Add or update the MULTITENANCY env var
        $this->addOrUpdateEnvVar('MULTITENANCY', 'true');

        $this->info('Tenant support enabled successfully and migrated to a new tenant');
    }

    private function addOrUpdateEnvVar($envKey, $envValue)
    {
        $envFile = base_path('.env');
        $env = file_get_contents($envFile);

        if (preg_match('/^' . $envKey . '=.*/m', $env)) {
            // Update existing line
            $env = preg_replace('/^' . $envKey . '=.*/m', $envKey . '=' . $envValue, $env);
        } else {
            // Add to the end of the file
            $env .= "\n" . $envKey . '=' . $envValue;
        }

        file_put_contents($envFile, $env);
    }
}
