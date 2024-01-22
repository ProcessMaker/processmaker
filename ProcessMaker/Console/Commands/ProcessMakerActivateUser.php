<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\User;

class ProcessMakerActivateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:user-activate
                            {--username= : Username of the user to activate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate a blocked user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->option('username');

        if ($username) {
            $this->activateUser($username);
        } else {
            $this->error('Please specify a username.');
        }
    }

    /**
     * Activate a user.
     *
     * @param string $username
     *
     * @return void
     */
    private function activateUser($username): void
    {
        $user = User::where('username', $username)->first();

        if ($user) {
            if ($user->status === 'BLOCKED') {
                $user->status = 'ACTIVE';
                $user->save();

                $this->info('User ' . $username . ' has been activated.');
            } else {
                $this->error('The user ' . $username . ' is not blocked.');
            }
        } else {
            $this->error('User ' . $username . ' not found.');
        }
    }
}
