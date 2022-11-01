<?php

namespace ProcessMaker\Console\Commands;

use Hash;
use Illuminate\Console\Command;
use Log;
use ProcessMaker\Models\User;

class AuthSetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:set-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set or reset the password on an account';

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
        $identifier = $this->ask("Enter the user's id or email address");

        if (is_numeric($identifier)) {
            $user = User::find($identifier);
        } else {
            $user = User::where('email', $identifier)->first();
        }

        if ($user) {
            if ($user->password) {
                $verb = 'reset';
            } else {
                $verb = 'set';
            }

            $confirm = $this->confirm("Are you sure you want to {$verb} the password for {$user->fullname}?");

            if ($confirm) {
                $password = $this->secret('Enter the new password');
                $confirm = $this->secret('Confirm the new password');

                if ($password === $confirm) {
                    $user->password = Hash::make($password);
                    $user->save();

                    Log::notice("Password {$verb} for user {$user->fullname} on command line.");
                    $this->info("Password {$verb} for user {$user->fullname}.");
                } else {
                    return $this->error('Password & confirmation do not match. Please try again.');
                }
            } else {
                return $this->error('Password not reset.');
            }
        } else {
            return $this->error('Unable to find user.');
        }
    }
}
