<?php

namespace ProcessMaker\Multitenancy;

use Dotenv\Dotenv;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use PDO;
use ProcessMaker\Application;
use ProcessMaker\Multitenancy\Tenant;

/**
 * This is used to do things that need happen before
 * the application service providers are registered or booted.
 *
 * We need to use raw PDO because the database provider is not loaded yet.
 */
class TenantBootstrapper
{
    private static $landlordValues = [];

    private $encrypter = null;

    private $pdo = null;

    private $app = null;

    public static $landlordKeysToSave = [
        'APP_URL',
        'APP_KEY',
        'LOG_PATH',
        'DB_USERNAME',
        'DB_PASSWORD',
        'DB_HOSTNAME',
        'DB_PORT',
        'LANDLORD_DB_DATABASE',
        'REDIS_PREFIX',
        'CACHE_SETTING_PREFIX',
        'SCRIPT_MICROSERVICE_CALLBACK',
        'CACHE_PREFIX',
    ];

    public function bootstrap(Application $app)
    {
        try {
            $this->bootstrapRun($app);
        } catch (\Exception $e) {
            file_put_contents(storage_path('logs/tenant_bootstrapper_error.log'), date('Y-m-d H:i:s') . ' ' . get_class($e) . ' in ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            throw $e;
        }
    }

    public function bootstrapRun(Application $app)
    {
        if (!$this->env('MULTITENANCY')) {
            return;
        }
        $this->app = $app;

        $tenantData = null;

        // Try to find tenant by ID first if TENANT env var is set
        $envTenant = $this->env('TENANT');
        if ($envTenant) {
            $tenantData = $this->executeQuery('SELECT * FROM tenants WHERE id = ?', [$envTenant]);
        } else {
            $request = Request::capture();
            $host = $request->getHost();
            $tenantData = $this->executeQuery('SELECT * FROM tenants WHERE domain = ? LIMIT 1', [$host]);
        }

        if (!$tenantData) {
            Tenant::setBootstrappedTenant($app, null);

            return;
        }

        // Set storage path
        $app->useStoragePath($app->basePath('storage/tenant_' . $tenantData['id']));

        $this->setTenantEnvironmentVariables($tenantData);

        // Use tenant's translation files. Doing this here so it's available in cached filesystems.php
        $app->useLangPath(resource_path('lang/tenant_' . $tenantData['id']));

        $tenantData['original_values'] = self::$landlordValues;
        Tenant::setBootstrappedTenant($app, $tenantData);
    }

    private function setTenantEnvironmentVariables($tenantData)
    {
        // Additional configs are set in SwitchTenant.php

        $config = json_decode($tenantData['config'], true);

        $this->set('APP_CONFIG_CACHE', $this->app->storagePath('config.php'));
        $this->set('APP_URL', $config['app.url']);
        $this->set('APP_KEY', $this->decrypt($config['app.key']));
        $this->set('DB_DATABASE', $tenantData['database']);
        $this->set('DB_USERNAME', $tenantData['username'] ?? $this->getOriginalValue('DB_USERNAME'));

        // Do not set REDIS_PREFIX because it is used by the queue (not tenant specific)
        $this->set('CACHE_PREFIX', 'tenant_' . $tenantData['id'] . ':' . $this->getOriginalValue('CACHE_PREFIX'));
        $this->set('CACHE_SETTING_PREFIX', 'tenant_' . $tenantData['id'] . ':' . $this->getOriginalValue('CACHE_SETTING_PREFIX'));

        $encryptedPassword = $tenantData['password'];
        $password = null;
        if ($encryptedPassword) {
            $password = $this->decrypt($encryptedPassword);
        } else {
            $password = $this->getOriginalValue('DB_PASSWORD');
        }

        $this->set('DB_PASSWORD', $password);
        $this->set('LOG_PATH', $this->app->storagePath('logs/processmaker.log'));
    }

    private function getOriginalValue($key, $default = '')
    {
        if (self::$landlordValues === []) {
            self::$landlordValues = Dotenv::parse(file_get_contents(base_path('.env')));
        }

        if (!isset(self::$landlordValues[$key])) {
            return $default;
        }

        return self::$landlordValues[$key];
    }

    private function env($key, $default = null)
    {
        $value = $_SERVER[$key] ?? $default;
        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }

        return $value;
    }

    private function set($key, $value)
    {
        // Env::getRepository() is immutable but will use values from $_SERVER and $_ENV
        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
    }

    private function decrypt($value)
    {
        if (!$this->encrypter) {
            $key = $this->getOriginalValue('APP_KEY');
            $landlordKey = base64_decode(substr($key, 7));
            $this->encrypter = new Encrypter($landlordKey, 'AES-256-CBC');
        }

        return $this->encrypter->decryptString($value);
    }

    private function getLandlordDbConfig(): array
    {
        return [
            'host' => $this->getOriginalValue('DB_HOSTNAME', 'localhost'),
            'port' => $this->getOriginalValue('DB_PORT', '3306'),
            'database' => $this->getOriginalValue('LANDLORD_DB_DATABASE', 'landlord'),
            'username' => $this->getOriginalValue('DB_USERNAME'),
            'password' => $this->getOriginalValue('DB_PASSWORD'),
            'charset' => 'utf8mb4',
        ];
    }

    private function getPdo(): PDO
    {
        if (!$this->pdo) {
            $landlordConfig = $this->getLandlordDbConfig();
            $dsn = "mysql:host={$landlordConfig['host']};port={$landlordConfig['port']};dbname={$landlordConfig['database']};charset={$landlordConfig['charset']}";
            $this->pdo = new PDO($dsn, $landlordConfig['username'], $landlordConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return $this->pdo;
    }

    private function executeQuery($query, $params = [])
    {
        $stmt = $this->getPdo()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetch();
    }
}
