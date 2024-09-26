<?php

use Illuminate\Encryption\Encrypter;
use ProcessMaker\Facades\EncryptedData;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;


class AddDefaultKeyForEncryptedFields extends Upgrade
{
    /**
     * Run the upgrade migration.
     *
     * @return void
     */
    public function up()
    {
        if (empty(config('app.encrypted_data.key'))) {
            $localInstance = EncryptedData::driver('local');
            $key = Encrypter::generateKey($localInstance::ENCRYPTION_METHOD);
            EncryptedData::driver('local')->addKeyInEnvironmentFile($key);
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        if (!empty(config('app.encrypted_data.key'))) {
            EncryptedData::driver('local')->removeKeyFromEnvironmentFile();
        }
    }
}
