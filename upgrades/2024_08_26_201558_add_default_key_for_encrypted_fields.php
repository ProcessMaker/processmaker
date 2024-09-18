<?php

use Illuminate\Encryption\Encrypter;
use ProcessMaker\Models\EncryptedData;
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
        if (empty(config('app.encrypted_data_key'))) {
            $key = Encrypter::generateKey(EncryptedData::ENCRYPTION_METHOD);
            EncryptedData::addKeyInEnvironmentFile($key);
        }
    }

    /**
     * Reverse the upgrade migration.
     *
     * @return void
     */
    public function down()
    {
        if (!empty(config('app.encrypted_data_key'))) {
            EncryptedData::removeKeyFromEnvironmentFile();
        }
    }
}
