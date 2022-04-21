<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SanitizeUsernames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $index => $user) {

            // Store the pre-update username for comparison
            $pre_update_username = $user->username;
            $updated_username = static::filterAndValidateUsername($user->username, $user->id);

            // If it's the same, then it was already valid
            if ($pre_update_username === $updated_username) {
                continue;
            }

            // Set the valid username to the user
            $update = DB::table('users')->where('id', $user->id)->update([
                'username' => $updated_username
            ]);

            // Log the result and continue
            if ($update) {
                logger()->info("Username Updated From ({$pre_update_username}) to ({$user->username})", [
                    'user_id' => $user->id,
                    'updated_from_username' => $pre_update_username,
                    'updated_to_username' => $user->username
                ]);
            }
        }
    }

    /**
     * Provide the user id or User model and receive a validated username string
     *
     * @param  string  $username
     * @param  int  $id
     *
     * @return string
     */
    public static function filterAndValidateUsername(string $username, int $id): string
    {
        $i = 0;

        $generator = static function () use ($username, &$i): string {
            preg_match_all('/[^a-zA-Z\d\s_-]/m', $username, $invalid_chars, PREG_SET_ORDER, 0);
            $invalid_chars = collect($invalid_chars)->flatten()->unique()->values();
            $username = ! blank($invalid_chars) ? str_replace($invalid_chars->all(), '', $username) : $username;
            return $i++ !== 0 ? $username.$i : $username;
        };

        // Ensure uniqueness for the username
        build_username_query:
        $unique_username_query = DB::table('users')->where('username', $username = $generator())
                                   ->where('id', '!=', $id)
                                   ->orderBy('id');

        if($unique_username_query->exists()) {
            goto build_username_query;
        }

        // Grab the User model rules
        //        $rules = (object) User::rules($username);

        // Make sure the new, filtered username is valid
        // with the User-model provided rules
        //        Validator::make($rules->username,
        //            ['username' => $username]
        //        )->validate();

        // Once we know it's a unique, valid
        // username, send it back
        return $username;
    }
}
