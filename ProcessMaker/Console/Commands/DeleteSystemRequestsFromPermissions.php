<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\RequestUserPermission;

class DeleteSystemRequestsFromPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:delete-system-requests-from-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $r = RequestUserPermission::whereIn('request_id',
            ProcessRequest::whereIn('process_id',
                Process::system()->select('id')
            )->select('id')
        )->delete();
        $this->info("Rows deleted: {$r}");
    }
}
