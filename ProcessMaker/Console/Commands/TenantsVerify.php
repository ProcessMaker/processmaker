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
    protected $signature = 'tenants:verify {--json : Output the results as JSON}';

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
    private $jsonData = [];

    public function handle()
    {
        if (!config('app.multitenancy')) {
            $this->info('Multitenancy is disabled');

            return;
        }

        $errors = [];
        $currentTenant = null;
        if (app()->has('currentTenant')) {
            $currentTenant = app('currentTenant');
        }

        if (!$currentTenant) {
            $this->error('Multitenancy enabled but no current tenant found.');

            return;
        }

        \Log::warning('TenantsVerify: Current Tenant ID: ' . ($currentTenant?->id ?? 'NONE'));

        $paths = [
            ['Storage Path', storage_path()],
            ['Config Cache Path', app()->getCachedConfigPath()],
            ['Lang Path', lang_path()],
        ];

        // Display paths in a nice table
        $this->infoTable(['Path', 'Value'], $paths);

        $configs = [
            'app.key' => null,
            'app.url' => null,
            'app.instance' => null,
            'cache.prefix' => 'tenant_{tenant_id}:',
            'database.redis.options.prefix' => null,
            'cache.stores.cache_settings.prefix' => 'tenant_{tenant_id}:settings',
            'script-runner-microservice.callback' => null,
            'database.connections.processmaker.database' => null,
            'logging.channels.daily.path' => base_path() . '/storage/tenant_{tenant_id}/logs/processmaker.log',
            'filesystems.disks.public.root' => base_path() . '/storage/tenant_{tenant_id}/app/public',
            'filesystems.disks.local.root' => base_path() . '/storage/tenant_{tenant_id}/app',
            'filesystems.disks.lang.root' => base_path() . '/resources/lang/tenant_{tenant_id}',
        ];

        $configs = array_map(function ($config) use ($configs, $currentTenant, &$errors) {
            $ok = '';
            if ($configs[$config] !== null) {
                $expected = str_replace('{tenant_id}', $currentTenant->id, $configs[$config]);
                if (config($config) === $expected) {
                    $ok = '✓';
                } else {
                    $ok = '✗';
                    $errors[] = 'Expected: ' . $expected . ' != Actual: ' . config($config);
                }
            }

            return [
                $config,
                config($config),
                $ok,
            ];
        }, array_keys($configs));

        // Display configs in a nice table
        $this->infoTable(['Config', 'Value', 'OK'], $configs);

        $env = EnvironmentVariable::first();
        if (!$env) {
            $decrypted = 'No environment variables found to test decryption';
        } else {
            $encryptedValue = $env->getAttributes()['value'];
            try {
                Crypt::decryptString($encryptedValue);
                $decrypted = 'OK';
            } catch (DecryptException $e) {
                $decrypted = 'FAILED! ' . $e->getMessage();
            }
        }

        $other = [
            ['Landlord Config Cache Path', base_path('bootstrap/cache/config.php')],
            ['Landlord Config Is Cached', File::exists(base_path('bootstrap/cache/config.php')) ? 'Yes' : 'No'],
            ['Tenant Config Cache Path', app()->getCachedConfigPath()],
            ['Tenant Config Is Cached', File::exists(app()->getCachedConfigPath()) ? 'Yes' : 'No'],
            ['First username (database check)', User::first()?->username ?? 'No users found'],
            ['Decrypted check', substr($decrypted, 0, 50)],
            // ['Original App URL (landlord)', $currentTenant?->getOriginalValue('APP_URL') ?? config('app.url')],
            ['config("app.url")', config('app.url')],
            ['getenv("APP_URL")', getenv('APP_URL')],
            ['env("APP_URL")', env('APP_URL')],
            ['$_SERVER["APP_URL"]', $_SERVER['APP_URL'] ?? 'NOT SET'],
            ['$_ENV["APP_URL"]', $_ENV['APP_URL'] ?? 'NOT SET'],
            ['Current PID', getmypid()],
        ];

        // Display other in a nice table
        $this->infoTable(['Other', 'Value'], $other);

        $checkUrls = [
            'config("app.url")' => config('app.url'),
            'getenv("APP_URL")' => getenv('APP_URL'),
            'env("APP_URL")' => env('APP_URL'),
            '$_SERVER["APP_URL"]' => $_SERVER['APP_URL'] ?? 'NOT SET',
            '$_ENV["APP_URL"]' => $_ENV['APP_URL'] ?? 'NOT SET',
        ];

        foreach ($checkUrls as $key => $value) {
            if ($value !== $currentTenant?->config['app.url']) {
                $errors[] = 'Expected: ' . $key . ' to be ' . $currentTenant?->config['app.url'] . ' but got ' . $value;
            }
        }

        $this->finish($errors);
    }

    private function finish($errors)
    {
        if (count($errors) > 0) {
            $this->error('Errors found');
        } else {
            $this->info('No errors found');
        }

        if ($this->option('json')) {
            $this->jsonData['Errors'] = $errors;
            $this->line(json_encode($this->jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    private function infoTable($headers, $rows)
    {
        if ($this->option('json')) {
            $section = [];
            foreach ($rows as $row) {
                $section[$row[0]] = $row[1];
            }
            $this->jsonData[$headers[0]] = $section;
        } else {
            foreach ($rows as $row) {
                \Log::warning($row[0] . ': ' . $row[1]);
            }
            $this->table($headers, $rows);
        }
    }
}
