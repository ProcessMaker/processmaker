<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\User;

class PopulateAvatarColumn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:populate-avatar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the avatar column with the current media files';

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
     * @return int
     */
    public function handle()
    {
        // Fetch all users
        $users = User::cursor();

        // Iterate through each user and populate the avatar column
        foreach ($users as $user) {
            $user->avatar = $user->getAvatar();
            $user->save();
        }

        // Output a message indicating success
        $this->info('Avatar column populated successfully.');

        return 0;
    }
}
