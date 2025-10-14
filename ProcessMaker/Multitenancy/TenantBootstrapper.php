<?php

namespace ProcessMaker\Multitenancy;

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
        'REDIS_PREFIX',
        'CACHE_SETTING_PREFIX',
        'SCRIPT_MICROSERVICE_CALLBACK',
    ];

    public function bootstrap(Application $app)
    {
        if (!$this->env('MULTITENANCY')) {
            return;
        }
        $this->app = $app;

        self::saveLandlordValues($app);

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
        $this->setTenantEnvironmentVariables($tenantData);

        // Use tenant's translation files. Doing this here so it's available in cached filesystems.php
        $app->useLangPath(resource_path('lang/tenant_' . $tenantData['id']));

        $tenantData['original_values'] = self::$landlordValues;
        Tenant::setBootstrappedTenant($app, $tenantData);
    }

    private function setTenantEnvironmentVariables($tenantData)
    {
        // Additional configs are set in SwitchTenant.php

        $tenantId = $tenantData['id'];
        $config = json_decode($tenantData['config'], true);

        $this->set('APP_CONFIG_CACHE', $this->app->basePath('storage/tenant_' . $tenantId . '/config.php'));
        // Do not override packages cache path for now. Wait until the License service is updated.
        // $this->set('APP_PACKAGES_CACHE', $this->app->basePath('storage/tenant_' . $tenantId . '/packages.php'));
        $this->set('LARAVEL_STORAGE_PATH', $this->app->basePath('storage/tenant_' . $tenantId));
        $this->set('APP_URL', $config['app.url']);
        $this->set('APP_KEY', $this->decrypt($config['app.key']));
        $value = $tenantData['database'];
        $this->set('DB_DATABASE', $value);
        $this->set('DB_USERNAME', $tenantData['username'] ?? $this->getOriginalValue('DB_USERNAME'));

        $encryptedPassword = $tenantData['password'];
        $password = null;
        if ($encryptedPassword) {
            $password = $this->decrypt($encryptedPassword);
        } else {
            $password = $this->getOriginalValue('DB_PASSWORD');
        }

        $this->set('DB_PASSWORD', $password);
        // Commenting this out fixes the redis queue, but what about cache????
        // $this->set('REDIS_PREFIX', $this->getOriginalValue('REDIS_PREFIX') . 'tenant-' . $tenantId . ':');
        // $this->debug("Setting log path to " . $this->app->basePath('storage/tenant_' . $tenantId . '/logs/processmaker.log'));
        $this->set('LOG_PATH', $this->app->basePath('storage/tenant_' . $tenantId . '/logs/processmaker.log'));
    }

    public static function saveLandlordValues($app)
    {
        if ($app->has('landlordValues')) {
            self::$landlordValues = $app->make('landlordValues');

            return;
        }

        foreach (self::$landlordKeysToSave as $key) {
            self::$landlordValues[$key] = $_SERVER[$key] ?? '';
        }
    }

    private function getOriginalValue($key)
    {
        if (!isset(self::$landlordValues[$key])) {
            // throw new \Exception('Landlord value not found in `landlordValues`: ' . $key);
            return '';
        }

        return self::$landlordValues[$key];
    }

    private function env($key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
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
            'host' => $this->env('DB_HOSTNAME', 'localhost'),
            'port' => $this->env('DB_PORT', '3306'),
            'database' => $this->env('LANDLORD_DB_DATABASE', 'landlord'),
            'username' => $this->env('DB_USERNAME'),
            'password' => $this->env('DB_PASSWORD'),
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

    private function debug($message)
    {
        file_put_contents(base_path('storage/debug.log'), $message . PHP_EOL, FILE_APPEND);
    }
}
