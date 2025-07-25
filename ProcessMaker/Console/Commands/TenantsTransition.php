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
    protected $description = 'Transition clients to tenants from storage/transitions folder';

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

        $clientFolders = File::directories($transitionsPath);

        if (empty($clientFolders)) {
            $this->error('No client folders found in storage/transitions.');

            return 1;
        }

        foreach ($clientFolders as $clientFolder) {
            $this->processClientFolder($clientFolder);
        }

        $this->info('All clients have been transitioned to tenants.');

        return 0;
    }

    /**
     * Process a single client folder
     *
     * @param string $clientFolder
     * @return void
     */
    private function processClientFolder(string $clientFolder)
    {
        $clientName = basename($clientFolder);
        $this->info("Processing client: {$clientName}");

        $envFile = $clientFolder . '/.env';
        if (!File::exists($envFile)) {
            $this->error("No .env file found in {$clientName}");

            return;
        }

        // Read the .env file
        $envContents = File::get($envFile);
        $envVars = $this->parseEnvFile($envContents);

        // Required environment variables
        $requiredVars = ['APP_NAME', 'APP_URL', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
        foreach ($requiredVars as $var) {
            if (!isset($envVars[$var])) {
                $this->error("Missing required environment variable: {$var} in {$clientName}");

                return;
            }
        }

        // Create the tenant
        $domain = parse_url($envVars['APP_URL'], PHP_URL_HOST);

        $this->info("Creating tenant for domain: {$domain}");

        // Call tenants:create command
        $command = [
            '--name' => $envVars['APP_NAME'],
            '--url' => $envVars['APP_URL'],
            '--database' => $envVars['DB_DATABASE'],
            '--username' => $envVars['DB_USERNAME'],
            '--password' => $envVars['DB_PASSWORD'],
            '--storage-folder' => $clientFolder . '/storage',
            '--lang-folder' => $clientFolder . '/lang',
            '--app-key' => $envVars['APP_KEY'],
        ];

        Artisan::call('tenants:create', $command);

        // Find the newly created tenant
        $tenant = Tenant::where('domain', $domain)->first();
        if (!$tenant) {
            $this->error("Failed to find tenant after creation for domain: {$domain}");

            return;
        }

        // Delete the client folder
        File::deleteDirectory($clientFolder);

        $this->info("Successfully transitioned client {$clientName} to tenant.");
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
