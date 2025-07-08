<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class EncryptDevlinkTokens extends Upgrade
{
    /**
     * check if the table and column exist before running the upgrade
     *
     * @return void
     * @throws RuntimeException
     */
    public function preflightChecks()
    {
        //
    }

    /**
     * Run the upgrade migration.
     * This migration needs to execute after the migrations related with dev_links
     *
     * @return void
     */
    public function up()
    {
        $devlinks = DB::table('dev_links')->get();

        // Encrypt the client_secret, access_token, and refresh_token columns
        foreach ($devlinks as $devlink) {
            if (!$this->isEncrypted($devlink->client_secret)) {
                DB::table('dev_links')
                    ->where('id', $devlink->id)
                    ->update([
                        'client_secret' => $devlink->client_secret ? Crypt::encrypt($devlink->client_secret) : null,
                        'access_token' => $devlink->access_token ? Crypt::encrypt($devlink->access_token) : null,
                        'refresh_token' => $devlink->refresh_token ? Crypt::encrypt($devlink->refresh_token) : null,
                    ]);
            }
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        // first decrypt the data
        $devlinks = DB::table('dev_links')->get();

        foreach ($devlinks as $devlink) {
            try {
                // try to decrypt the data
                $decrypted = Crypt::decryptString($devlink->client_secret);
                $decryptedAccess = Crypt::decryptString($devlink->access_token);
                $decryptedRefresh = Crypt::decryptString($devlink->refresh_token);
                DB::table('dev_links')
                    ->where('id', $devlink->id)
                    ->update([
                        'client_secret' => $decrypted,
                        'access_token' => $decryptedAccess,
                        'refresh_token' => $decryptedRefresh,
                    ]);
            } catch (Exception $e) {
                // if the decryption fails, assume the data is already decrypted
                continue;
            }
        }
    }

    /**
     * Checks if the value is encrypted
     *
     * @param string $value
     * @return bool
     */
    private function isEncrypted($value)
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
