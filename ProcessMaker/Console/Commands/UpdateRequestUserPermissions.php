<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;

class UpdateRequestUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:update-request-user-permissions';

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
        $query = User::whereRaw('active_at > NOW() - INTERVAL 15 DAY');
        $count = $query->count();

        $this->info("{$count} users found");

        $query->get()->each(function ($user) {
            $user->updatePermissionsToRequests();
        });
        
        $this->info("Done");
    }
}
