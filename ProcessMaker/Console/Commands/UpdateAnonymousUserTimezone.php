<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;

class UpdateAnonymousUserTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:update-anonymous-user-timezone
                            {--timezone= : Timezone to assign. Defaults to config app.anonymous_user_timezone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the anonymous user timezone from config (safe to run multiple times)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = $this->option('timezone') ?: config('app.anonymous_user_timezone', 'UTC');

        $user = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->first();

        if (!$user) {
            $this->error('Anonymous user not found.');

            return self::FAILURE;
        }

        if ($user->timezone === $timezone) {
            $this->info("Anonymous user timezone is already set to [{$timezone}].");

            return self::SUCCESS;
        }

        $previousTimezone = $user->timezone;
        $user->timezone = $timezone;
        $user->save();

        $this->info("Anonymous user timezone updated from [{$previousTimezone}] to [{$timezone}].");

        return self::SUCCESS;
    }
}
