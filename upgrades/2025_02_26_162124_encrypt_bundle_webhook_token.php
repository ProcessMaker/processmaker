<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class EncryptBundleWebhookToken extends Upgrade
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
     *
     * @return void
     */
    public function up()
    {
        $bundles = DB::table('bundles')->get();

        // Encrypt the webhook_token column
        foreach ($bundles as $bundle) {
            if (!$this->isEncrypted($bundle->webhook_token)) {
                DB::table('bundles')
                    ->where('id', $bundle->id)
                    ->update([
                        'webhook_token' => $bundle->webhook_token ? Crypt::encrypt($bundle->webhook_token) : null,
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
        $bundles = DB::table('bundles')->get();

        foreach ($bundles as $bundle) {
            try {
                // try to decrypt the data
                $decrypted = Crypt::decryptString($bundle->webhook_token);
                DB::table('bundles')
                    ->where('id', $bundle->id)
                    ->update([
                        'webhook_token' => $decrypted,
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
