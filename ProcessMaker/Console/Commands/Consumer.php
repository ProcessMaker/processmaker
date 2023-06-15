<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use ProcessMaker\Facades\MessageBrokerService;

class Consumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the consumer worker for the configured broker messages.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            MessageBrokerService::worker();
        } catch (Exception $e) {
            $this->warn($e->getMessage());
        }
    }
}
