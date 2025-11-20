<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Multitenancy\Tenant;

class TenantsCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:create 
    {--name=} 
    {--url=} 
    {--database=} 
    {--username=} 
    {--password=} 
    {--storage-folder=} 
    {--lang-folder=} 
    {--app-key=}
    {--skip-initialize-folders}
    {--skip-setup-notifications}';

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
        $infoCallback = function ($type, $message) {
            if ($type === 'out') {
                $this->info($message);
            } else {
                $this->error($message);
            }
        };

        // Check if rsync exists
        Process::run('which rsync')->throw();

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
                $cmd = "rsync -avz --exclude='tenant_*' --exclude='logs' --exclude='transitions' " . $storageFolderOption . '/ ' . $tenantStoragePath;
                Process::run($cmd, $infoCallback)->throw();
            } else {
                $this->error('Storage folder does not exist: ' . $storageFolderOption);

                return 1;
            }
        }

        // Check if an existing lang folder is provided
        $langFolderOption = $this->option('lang-folder', null);
        $sourceLangPath = resource_path('lang');
        $tenantLangPath = resource_path('lang/tenant_' . $tenant->id);
        if ($langFolderOption) {
            if (File::isDirectory($langFolderOption)) {
                $this->info('Copying lang folder to ' . $tenantLangPath);
                if (File::isDirectory($tenantLangPath)) {
                    $this->error('Tenant lang path already exists: ' . $tenantLangPath);

                    return 1;
                } else {
                    $cmd = "rsync -avz --exclude='tenant_*' " . $langFolderOption . '/ ' . $tenantLangPath;
                    Process::run($cmd, $infoCallback)->throw();
                }
            } else {
                $this->error('Lang folder does not exist: ' . $langFolderOption);

                return 1;
            }
        } else {
            // Initialize lang folder
            if (!File::isDirectory($tenantLangPath)) {
                mkdir($tenantLangPath, 0755, true);
            }
        }

        if (!$this->option('skip-initialize-folders')) {
            $cmd = sprintf(
                "rsync -azv --ignore-existing --exclude='tenant_*' %s %s",
                escapeshellarg($sourceLangPath . '/'),
                escapeshellarg($tenantLangPath)
            );
            Process::run($cmd, $infoCallback)->throw();
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

        if (!$this->option('skip-initialize-folders')) {
            foreach ($subfolders as $subfolder) {
                if (!File::isDirectory($tenantStoragePath . '/' . $subfolder)) {
                    mkdir($tenantStoragePath . '/' . $subfolder, 0755, true);
                }
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

        $this->info("Empty tenant created. ID: {$tenant->id}");
        if (!$this->option('skip-setup-notifications')) {
            $this->info("You will need to do the following using TENANT={$tenant->id} env prefix");
            $this->line('- Run migrations and seed the database');
            $this->line('- Run the install command for each package');
            $this->line('- Run artisan upgrade');
            $this->line('- Install passport by calling passport:install (create the default clients');
            $this->line('- Reset the admin password with auth:set-password');
            $this->line('- Run processmaker:initialize-script-microservice');
            $this->info("For example, `TENANT={$tenant->id} php artisan migrate:fresh --seed`");
        }
    }

    private function tenantArtisan($command, $tenantId)
    {
        Artisan::call('tenants:artisan', ['artisanCommand' => $command, '--tenant' => $tenantId], $this->output);
    }
}
