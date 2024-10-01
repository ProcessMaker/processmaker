<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Repositories\CaseSyncRepository;

class CasesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:sync
                            {--request_ids= : Comma-separated list of request IDs to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync requests with the cases tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestIds = $this->option('request_ids');
        $requestIds = $requestIds ? explode(',', $requestIds) : [];

        if (count($requestIds) > 0) {
            $data = CaseSyncRepository::syncCases($requestIds);

            foreach ($data['successes'] as $value) {
                $this->info('Case started synced ' . $value);
            }

            foreach ($data['errors'] as $value) {
                $this->error('Error syncing case started ' . $value);
            }
        } else {
            $this->error('Please specify a list of request IDs.');
        }
    }
}
