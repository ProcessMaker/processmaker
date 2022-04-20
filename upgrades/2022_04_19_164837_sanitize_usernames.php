<?php

use ProcessMaker\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class SanitizeUsernames extends Upgrade
{
    /**
     * The version of ProcessMaker being upgraded *to*
     *
     * @var string example: 4.2.28
     */
    public $to = '4.2.30-RC1';

    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * There is no need to check against the version(s) as the upgrade
     * migrator will do this automatically and fail if the correct
     * version(s) are not present.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = DB::table('users')->select(['id','username'])
                                   ->orderBy('id')
                                   ->get();

        foreach ($users as $user) {

            // Store the pre-update username for comparison
            $pre_update_username = $user->username;
            $updated_username = static::filterAndValidateUsername($user->username, $user->id);

            // If it's the same, then it was already valid
            if ($pre_update_username === $updated_username) {
                continue;
            }

            // Set the valid username to the user
            DB::table('users')->where('id', '=', $user->id)
                                     ->update(['username' => $updated_username]);

            // Log the result and continue
            logger()->info("Username Updated From ({$pre_update_username}) to ({$user->username})", [
                'user_id' => $user->id,
                'updated_from_username' => $pre_update_username,
                'updated_to_username' => $user->username
            ]);
        }
    }


    /**
     * Reverse the upgrade migrations.
     *
     * @return void
     */
    public function down()
    {
        //
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
        $generator = static function (int $i = null) use (&$username) {
            preg_match_all('/[^a-zA-Z\d\s_-]/m', $username, $invalid_chars, PREG_SET_ORDER, 0);

            $invalid_chars = collect($invalid_chars)->flatten()->unique()->values();
            $username = ! blank($invalid_chars) ? str_replace($invalid_chars->all(), '', $username) : $username;

            if (is_int($i) && $i !== 0) {
                $username .= $i;
            }
        };

        // Will validate/filter the username
        // and set $valid_username
        $generator($i = 0);

        // Ensure uniqueness for the username
        $unique_username_query =  DB::table('users')->where('username', '=', $username)
                                                    ->where('id', '!=', $id)
                                                    ->orderBy('id');

        // if it's not unique, append an index number to it
        while ($unique_username_query->exists()) {
            $generator(++$i);
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

