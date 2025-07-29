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
    protected $signature = 'tenants:create {--name=} {--url=} {--database=} {--username=} {--password=} {--storage-folder=} {--lang-folder=} {--packages-folder=} {--app-key=}';

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
        if (File::isDirectory($tenantStoragePath)) {
            $this->error('Tenant storage path already exists: ' . $tenantStoragePath);

            return 1;
        } else {
            mkdir($tenantStoragePath, 0755, true);
        }

        // Check if an existing storage folder is provided
        $storageFolderOption = $this->option('storage-folder', null);
        if ($storageFolderOption) {
            if (File::isDirectory($storageFolderOption)) {
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
            } else {
                $this->error('Storage folder does not exist: ' . $storageFolderOption);

                return 1;
            }
        }

        if (str_contains(lang_path(), 'tenant')) {
            $this->error('Lang path contains "tenant". Are you running this in multitenancy mode? You should not be.');

            return 1;
        }

        // Check if an existing lang folder is provided
        $langFolderOption = $this->option('lang-folder', null);
        if ($langFolderOption) {
            if (File::isDirectory($langFolderOption)) {
                $this->info('Moving lang folder to resources/lang/tenant_' . $tenant->id);
                rename($langFolderOption, lang_path('tenant_' . $tenant->id));
            } else {
                $this->error('Lang folder does not exist: ' . $langFolderOption);

                return 1;
            }
        } else {
            // Copy resources/lang to resources/lang/tenant_<id>
            $this->info('Copying resources/lang to resources/lang/tenant_' . $tenant->id);
            $source = lang_path();
            $destination = lang_path('tenant_' . $tenant->id);
            if (!File::isDirectory($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $this->info('Copying ' . $source . ' to ' . $destination);
            exec('rsync -avz --exclude=tenant_* ' . $source . '/ ' . $destination, $output, $returnVar);
            $this->info(implode("\n", $output));
        }

        // Check if an existing packages folder is provided
        $packagesFolderOption = $this->option('packages-folder', null);
        if ($packagesFolderOption) {
            if (File::isDirectory($packagesFolderOption)) {
                // Get each subfolder in the packages folder
                $subfolders = File::directories($packagesFolderOption);
                foreach ($subfolders as $subfolder) {
                    $packageLangFolder = $subfolder . '/resources/lang';
                    if (File::isDirectory($packageLangFolder)) {
                        $destinationLangFolder = base_path('vendor/processmaker/' . basename($subfolder)) . '/resources/lang';
                        if (File::isDirectory($destinationLangFolder)) {
                            $destinationLangTenantFolder = $destinationLangFolder . '/tenant_' . $tenant->id;
                            if (!File::isDirectory($destinationLangTenantFolder)) {
                                File::makeDirectory($destinationLangTenantFolder, 0755, true);
                            }
                            $this->info('Moving' . $packageLangFolder . ' to ' . $destinationLangTenantFolder);
                            rename($packageLangFolder, $destinationLangTenantFolder);
                        } else {
                            $this->error('Destination lang folder does not exist: ' . $destinationLangFolder);

                            return 1;
                        }
                    }
                }
            } else {
                $this->error('Packages folder does not exist: ' . $packagesFolderOption);

                return 1;
            }
        } else {
            // Copy the resources/lang folder in each vendor/processmaker folder to the tenant lang folder
            $packages = File::directories(base_path('vendor/processmaker'));
            foreach ($packages as $package) {
                $packageLangFolder = $package . '/resources/lang';
                if (File::isDirectory($packageLangFolder)) {
                    $destinationLangFolder = $packageLangFolder . '/tenant_' . $tenant->id;
                    if (!File::isDirectory($destinationLangFolder)) {
                        File::makeDirectory($destinationLangFolder, 0755, true);
                    }
                    $this->info('Copying ' . $packageLangFolder . ' to ' . $destinationLangFolder);
                    exec('rsync -avz --exclude=tenant_* ' . $packageLangFolder . '/ ' . $destinationLangFolder, $output, $returnVar);
                    $this->info(implode("\n", $output));
                }
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

        // Make sure these folders still exist in landlord storage.
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
