<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\User;
use Spatie\Multitenancy\Models\Tenant;

class TenantsVerify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify tenant configuration and storage paths';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentTenant = null;
        if (app()->has('currentTenant')) {
            $currentTenant = app('currentTenant');
        }

        if (config('app.multitenancy') && !$currentTenant) {
            $this->error('Multitenancy enabled but current tenant found.');

            return;
        }

        $this->info('Current Tenant ID: ' . ($currentTenant?->id ?? 'NONE'));

        $paths = [
            ['Storage Path', storage_path()],
            ['Config Cache Path', app()->getCachedConfigPath()],
            ['Lang Path', lang_path()],
        ];

        // Display paths in a nice table
        $this->table(['Path', 'Value'], $paths);

        $configs = [
            'app.key',
            'app.url',
            'app.instance',
            'cache.prefix',
            'database.redis.options.prefix',
            'cache.stores.cache_settings.prefix',
            'script-runner-microservice.callback',
            'database.connections.processmaker.database',
            'logging.channels.daily.path',
            'filesystems.disks.public.root',
            'filesystems.disks.local.root',
            'filesystems.disks.lang.root',
        ];

        $configs = array_map(function ($config) {
            return [
                $config,
                config($config),
            ];
        }, $configs);

        // Display configs in a nice table
        $this->table(['Config', 'Value'], $configs);

        $env = EnvironmentVariable::first();
        if (!$env) {
            $decrypted = 'No environment variables found to test decryption';
        }
        $encryptedValue = $env->getAttributes()['value'];
        try {
            Crypt::decryptString($encryptedValue);
            $decrypted = 'OK';
        } catch (DecryptException $e) {
            $decrypted = 'FAILED! ' . $e->getMessage();
        }

        $other = [
            ['Landlord Config Cache Path', base_path('bootstrap/cache/config.php')],
            ['Landlord Config Is Cached', File::exists(base_path('bootstrap/cache/config.php')) ? 'Yes' : 'No'],
            ['Tenant Config Cache Path', app()->getCachedConfigPath()],
            ['Tenant Config Is Cached', File::exists(app()->getCachedConfigPath()) ? 'Yes' : 'No'],
            ['First username (database check)', User::first()->username],
            ['Decrypted check', substr($decrypted, 0, 50)],
            ['Original App URL (landlord)', $currentTenant?->getOriginalValue('APP_URL') ?? config('app.url')],
        ];

        // Display other in a nice table
        $this->table(['Other', 'Value'], $other);
    }
}
