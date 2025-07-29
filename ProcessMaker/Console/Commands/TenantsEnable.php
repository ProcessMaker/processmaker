<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDOException;

class TenantsEnable extends Command
{
    private $tempStorageFolder;

    private $tempLangFolder;

    private $tempPackagesFolder;

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
        $exitCode = $this->tenantsEnable();
        $this->removeTempFolders();

        return $exitCode;
    }

    private function tenantsEnable()
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
        $this->tempStorageFolder = $tempStorageFolder = base_path('storage-temp');
        exec("rsync -avz --exclude='tenant_*' " . base_path('storage') . '/ ' . $tempStorageFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to copy storage folder to temp location');
            $this->error(implode("\n", $output));

            return 1;
        }
        $this->info(implode("\n", $output));

        // Next, do the same thing for the lang folder
        $this->tempLangFolder = $tempLangFolder = base_path('lang-temp');
        exec('rsync -avz ' . resource_path('lang') . '/ ' . $tempLangFolder, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Failed to copy lang folder to temp location');
            $this->error(implode("\n", $output));
        }
        $this->info(implode("\n", $output));

        // And for the packages folders, but only the resources/lang in each package
        $this->tempPackagesFolder = $tempPackagesFolder = base_path('packages-temp');
        // Get all the folders in the vendor/processmaker folder
        $packages = File::directories(base_path('vendor/processmaker'));
        foreach ($packages as $package) {
            $packageLangFolder = $package . '/resources/lang';
            $this->info('Checking ' . $packageLangFolder);
            if (File::isDirectory($packageLangFolder)) {
                $packageName = basename($package);
                $destinationFolder = $tempPackagesFolder . '/' . $packageName . '/resources/lang';
                File::makeDirectory($destinationFolder, 0755, true);
                $this->info('Copying ' . $packageLangFolder . ' to ' . $destinationFolder);
                exec('rsync -avz ' . $packageLangFolder . '/ ' . $destinationFolder, $output, $returnVar);
                $this->info(implode("\n", $output));
            } else {
                $this->info('Package lang folder does not exist for ' . $package . ' in ' . $packageLangFolder);
            }
        }

        // Now, create the tenant. The folder will be moved to the new tenant after the creation
        // and the $tempStorageFolder will no longer exist.
        $exitCode = Artisan::call('tenants:create', [
            '--database' => config('database.connections.processmaker.database'),
            '--url' => config('app.url'),
            '--storage-folder' => $tempStorageFolder,
            '--lang-folder' => $tempLangFolder,
            '--packages-folder' => $tempPackagesFolder,
            '--name' => config('app.name'),
            '--app-key' => config('app.key'),
        ], $this->output);

        if ($exitCode !== 0) {
            $this->error('Failed to create tenant');

            return 1;
        }

        // Add or update the MULTITENANCY env var
        $this->addOrUpdateEnvVar('MULTITENANCY', 'true');

        $this->info('Tenant support enabled successfully and migrated to a new tenant');

        return 0;
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

    private function removeTempFolders()
    {
        // Remove temp storage folder
        if ($this->tempStorageFolder) {
            exec('rm -rf ' . $this->tempStorageFolder, $output, $returnVar);
            if ($returnVar !== 0) {
                $this->error('Failed to remove temp storage folder');
                $this->error(implode("\n", $output));
            }
            $this->info(implode("\n", $output));
        }

        // Remove temp lang folder
        if ($this->tempLangFolder) {
            exec('rm -rf ' . $this->tempLangFolder, $output, $returnVar);
            if ($returnVar !== 0) {
                $this->error('Failed to remove temp lang folder');
                $this->error(implode("\n", $output));
            }
            $this->info(implode("\n", $output));
        }

        // Remove temp packages folder
        if ($this->tempPackagesFolder) {
            exec('rm -rf ' . $this->tempPackagesFolder, $output, $returnVar);
            if ($returnVar !== 0) {
                $this->error('Failed to remove temp packages folder');
                $this->error(implode("\n", $output));
            }
            $this->info(implode("\n", $output));
        }
    }
}
