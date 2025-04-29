<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Services\DataSourceIntegrations\Integrations\AldrichIntegrationService;

class SyncAldrichIntegrationData extends Command
{
    protected $signature = 'datasource:sync-aldrich-integration
                            {--queue : Queue this command to run asynchronously}';

    protected $description = 'Sync data from Aldrich API';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->option('queue')) {
            self::dispatch();

            return Command::SUCCESS;
        }

        $this->info('Starting sync from Aldrich Integration...');

        try {
            $service = new AldrichIntegrationService();
            if (!$service) {
                $this->error('Failed to initialize Aldrich integration service');

                return Command::FAILURE;
            }
            $stats = $service->syncData();

            $this->info('Sync completed.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Fetched', $stats['fetched']],
                    ['Stored', $stats['stored']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error syncing data: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
