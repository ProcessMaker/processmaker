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
    private $encrypter = null;

    private $pdo = null;

    private $originalValues = null;

    public function bootstrap(Application $app)
    {
        if (!$this->env('MULTITENANCY')) {
            return;
        }

        // We need to save the original values for running horizon
        $this->saveOriginalValues();

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
        $this->setTenantEnvironmentVariables($app, $tenantData);

        $tenantData['original_values'] = $this->getOriginalValue();
        Tenant::setBootstrappedTenant($app, $tenantData);
    }

    private function setTenantEnvironmentVariables($app, $tenantData)
    {
        // Additional configs are set in SwitchTenant.php

        $tenantId = $tenantData['id'];
        $config = json_decode($tenantData['config'], true);

        $this->set('APP_CONFIG_CACHE', $app->basePath('storage/tenant_' . $tenantId . '/config.php'));
        $this->set('LARAVEL_STORAGE_PATH', $app->basePath('storage/tenant_' . $tenantId));
        $this->set('APP_URL', $config['app.url']);
        $this->set('APP_KEY', $this->decrypt($config['app.key']));
        $this->set('DB_DATABASE', $tenantData['database']);
        $this->set('DB_USERNAME', $tenantData['username'] ?? $this->getOriginalValue('DB_USERNAME'));
        $encryptedPassword = $tenantData['password'] ?? $this->getOriginalValue('DB_PASSWORD');
        $this->set('DB_PASSWORD', $encryptedPassword ? $this->decrypt($encryptedPassword) : $encryptedPassword);
        $this->set('REDIS_PREFIX', $this->getOriginalValue('REDIS_PREFIX') . 'tenant-' . $tenantId . ':');
        $this->set('LOG_PATH', $app->basePath('storage/tenant_' . $tenantId . '/logs/processmaker.log'));
    }

    private function saveOriginalValues()
    {
        if ($this->env('ORIGINAL_VALUES')) {
            return;
        }
        $toSave = [
            'APP_URL',
            'APP_KEY',
            'DB_USERNAME',
            'DB_PASSWORD',
            'REDIS_PREFIX',
            'CACHE_SETTING_PREFIX',
            'SCRIPT_MICROSERVICE_CALLBACK',
        ];
        $values = [];
        foreach ($toSave as $key) {
            $values[$key] = $this->env($key);
        }
        $this->set('ORIGINAL_VALUES', serialize($values));
    }

    private function getOriginalValue($key = null)
    {
        if (!$this->originalValues) {
            $this->originalValues = unserialize($this->env('ORIGINAL_VALUES'));
        }
        if (!$key) {
            return $this->originalValues;
        }

        return $this->originalValues[$key];
    }

    private function env($key, $default = null)
    {
        return Env::get($key, $default);
    }

    private function set($key, $value)
    {
        Env::getRepository()->set($key, $value);
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
}
