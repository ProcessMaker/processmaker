<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class EncryptPpsSftpConfigsConfig extends Upgrade
{
    /**
     * check if the table and column exist before running the upgrade
     *
     * @return void
     * @throws RuntimeException
     */
    public function preflightChecks()
    {
        if (!Schema::hasTable('pps_sftp_configs')) {
            throw new RuntimeException('The table pps_sftp_configs does not exist.');
        }

        if (!Schema::hasColumn('pps_sftp_configs', 'config')) {
            throw new RuntimeException('The column config does not exist in pps_sftp_configs table.');
        }
    }

    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        // First change the column type from JSON to TEXT
        Schema::table('pps_sftp_configs', function (Blueprint $table) {
            $table->text('config')->change();
        });

        // then encrypt the data
        $configs = DB::table('pps_sftp_configs')->get();

        foreach ($configs as $config) {
            // check if the value is already encrypted
            if (!$this->isEncrypted($config->config)) {
                // encrypt the value and save it
                DB::table('pps_sftp_configs')
                    ->where('id', $config->id)
                    ->update([
                        'config' => Crypt::encryptString($config->config),
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
        $configs = DB::table('pps_sftp_configs')->get();

        foreach ($configs as $config) {
            try {
                // try to decrypt the data
                $decrypted = Crypt::decryptString($config->config);
                DB::table('pps_sftp_configs')
                    ->where('id', $config->id)
                    ->update([
                        'config' => $decrypted,
                    ]);
            } catch (Exception $e) {
                // if the decryption fails, assume the data is already decrypted
                continue;
            }
        }
        // then change the column type from TEXT to JSON
        Schema::table('pps_sftp_configs', function (Blueprint $table) {
            $table->json('config')->change();
        });
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
