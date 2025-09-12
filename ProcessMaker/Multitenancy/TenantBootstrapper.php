<?php

namespace ProcessMaker\Multitenancy;

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use PDO;
use PDOException;
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
    public function bootstrap(Application $app)
    {
        if (!Env::get('MULTITENANCY')) {
            return;
        }

        // Get landlord database connection details from environment variables
        $landlordConfig = [
            'host' => Env::get('DB_HOSTNAME', 'localhost'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => Env::get('LANDLORD_DB_DATABASE', 'landlord'),
            'username' => Env::get('DB_USERNAME'),
            'password' => Env::get('DB_PASSWORD'),
            'charset' => 'utf8mb4',
        ];

        try {
            // Create PDO connection to landlord database
            $dsn = "mysql:host={$landlordConfig['host']};port={$landlordConfig['port']};dbname={$landlordConfig['database']};charset={$landlordConfig['charset']}";
            $pdo = new PDO($dsn, $landlordConfig['username'], $landlordConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $tenantData = null;

            // Try to find tenant by ID first if TENANT env var is set
            $envTenant = Env::get('TENANT');
            if ($envTenant) {
                $stmt = $pdo->prepare('SELECT * FROM tenants WHERE id = ?');
                $stmt->execute([$envTenant]);
                $tenantData = $stmt->fetch();
            } else {
                $request = Request::capture();
                $host = $request->getHost();
                $stmt = $pdo->prepare('SELECT * FROM tenants WHERE domain = ? LIMIT 1');
                $stmt->execute([$host]);
                $tenantData = $stmt->fetch();
            }

            $app->instance(Tenant::BOOTSTRAPPED_TENANT, $tenantData);
        } catch (PDOException $e) {
            // Log the error but don't throw to avoid breaking the bootstrap process
            error_log('TenantBootstrapper Failed: ' . $e->getMessage());

            return null;
        }
    }
}
