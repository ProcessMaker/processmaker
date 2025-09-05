<?php

namespace ProcessMaker\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ProcessMaker\Multitenancy\Tenant;

class TenantsTransition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:transition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition clients to tenants from .env files in storage/transitions folder';

    /**
     * The success messages.
     *
     * @var array
     */
    protected $success = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if multitenancy is enabled
        if (!config('app.multitenancy')) {
            $this->error('Multitenancy is not enabled. Exiting.');

            return 1;
        }

        $transitionsPath = base_path('storage/transitions');

        if (!File::exists($transitionsPath)) {
            $this->error('The storage/transitions directory does not exist.');

            return 1;
        }

        $envFiles = File::files($transitionsPath, true);
        $envFiles = array_filter($envFiles, function ($file) {
            return str_starts_with(basename($file), '.env');
        });

        if (empty($envFiles)) {
            $this->error('No .env files found in storage/transitions.');

            return 1;
        }

        foreach ($envFiles as $envFile) {
            $exitCode = $this->processEnvFile($envFile);
            if ($exitCode !== 0) {
                $this->outputSuccessMessages();

                return $exitCode;
            }
        }

        $this->info('All clients have been transitioned to tenants.');
        $this->outputSuccessMessages();

        return 0;
    }

    private function outputSuccessMessages()
    {
        foreach ($this->success as $message) {
            $this->info($message);
        }
    }

    /**
     * Process a single .env file
     *
     * @param string $envFilePath
     * @return void
     */
    private function processEnvFile(string $envFilePath)
    {
        $fileName = basename($envFilePath);
        $this->info("Processing .env file: {$fileName}");

        // Read the .env file
        $envContents = File::get($envFilePath);
        $envVars = $this->parseEnvFile($envContents);

        // Required environment variables
        $requiredVars = ['APP_NAME', 'APP_URL', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
        foreach ($requiredVars as $var) {
            if (!isset($envVars[$var])) {
                $this->error("Missing required environment variable: {$var} in {$fileName}");

                return 1;
            }
        }

        $appName = $envVars['PROCESS_INTELLIGENCE_COMPANY_NAME'] ?? null;
        if (!$appName) {
            // Get the app name from the .env file suffix (e.g., .env.my-app -> my-app)
            $appName = str_replace('.env.', '', $fileName);
        }

        // Create the tenant
        $domain = parse_url($envVars['APP_URL'], PHP_URL_HOST);

        $this->info("Creating tenant for domain: {$domain}");

        // Call tenants:create command
        // NOTE: Storage and lang folders will be moved manually after the tenant is created.
        $command = [
            '--name' => $appName,
            '--url' => $envVars['APP_URL'],
            '--database' => $envVars['DB_DATABASE'],
            '--username' => $envVars['DB_USERNAME'],
            '--password' => $envVars['DB_PASSWORD'],
            '--app-key' => $envVars['APP_KEY'],
            '--skip-setup-notifications' => true,
            '--skip-initialize-folders' => true,
        ];

        $exitCode = Artisan::call('tenants:create', $command, $this->output);
        if ($exitCode !== 0) {
            $this->error("Failed to create tenant for domain: {$domain}");

            return 1;
        }

        // Find the newly created tenant
        $tenant = Tenant::where('domain', $domain)->first();
        if (!$tenant) {
            $this->error("Failed to find tenant after creation for domain: {$domain}");

            return 1;
        }

        // Delete the .env file
        File::delete($envFilePath);

        $this->success[] = "Success: {$fileName} -> Tenant ID: {$tenant->id}";

        return 0;
    }

    /**
     * Parse .env file contents into an array
     *
     * @param string $contents
     * @return array
     */
    private function parseEnvFile(string $contents): array
    {
        return Dotenv::parse($contents);
    }
}
