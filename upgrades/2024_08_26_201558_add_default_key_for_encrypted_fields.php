<?php

use Illuminate\Encryption\Encrypter;
use ProcessMaker\Models\EnvironmentVariable;
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
            $key = $this->generateRandomKey();
            $this->addKeyInEnvironmentFile($key);
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
            $this->removeKeyFromEnvironmentFile();
        }
    }

    /**
     * Write key in environment file with the given key.
     *
     * @param string $key
     */
    protected function addKeyInEnvironmentFile($key)
    {
        $content = file_get_contents(App::environmentFilePath());

        $content .= "\nENCRYPTED_DATA_KEY=$key\n";

        file_put_contents(App::environmentFilePath(), $content);
    }

    /**
     * Remove key from environment file.
     */
    protected function removeKeyFromEnvironmentFile()
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            '',
            $input = file_get_contents(App::environmentFilePath())
        );
        file_put_contents(App::environmentFilePath(), $replaced);
    }

    /**
     * Generate a random key to use for encrypt data.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            Encrypter::generateKey('AES-256-GCM')
        );
    }

    /**
     * Get a regex pattern that will match env ENCRYPTED_DATA_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('=' . config('app.encrypted_data_key'), '/');

        return "/^ENCRYPTED_DATA_KEY{$escaped}/m";
    }
}

