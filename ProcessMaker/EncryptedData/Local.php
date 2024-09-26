<?php

namespace ProcessMaker\EncryptedData;

use Illuminate\Support\Facades\App;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use ProcessMaker\EncryptedData\EncryptedDataInterface;
use ProcessMaker\Models\EncryptedData;

class Local implements EncryptedDataInterface
{
    const ENCRYPTION_METHOD = 'aes-256-cbc';

    public static $iv = '';

    /**
     * Encrypt text.
     *
     * @param string $plainText
     * @param string $iv
     * @param string $key
     * @return string
     */
    public function encryptText(string $plainText, string $iv = null, string $key = null): string
    {
        if (is_null($iv)) {
            $iv = self::generateIv();
        }

        if (is_null($key)) {
            $key = self::getEncryptedDataKey();
        }

        // Encrypt text
        $cipherText = openssl_encrypt($plainText, self::ENCRYPTION_METHOD, $key, 0, $iv);

        // Store last $iv used
        self::$iv = $iv;
        
        return $cipherText;
    }

    /**
     * Decrypt text.
     *
     * @param string $cipherText
     * @param string $iv
     * @param string $key
     * @return string
     */
    public function decryptText(string $cipherText, string $iv = null, string $key = null): string
    {
        if (is_null($iv)) {
            $iv = self::$iv;
        }

        if (is_null($key)) {
            $key = self::getEncryptedDataKey();
        }

        // Decrypt text
        $plainText = openssl_decrypt($cipherText, self::ENCRYPTION_METHOD, $key, 0, $iv);

        return $plainText;
    }

    /**
     * Generate an iv value.
     *
     * @return string
     */
    public static function generateIv()
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPTION_METHOD));

        return $iv;
    }

    /**
     * Get encrypted data key value.
     *
     * @return string
     */
    public static function getEncryptedDataKey()
    {
        $key = config('app.encrypted_data.key');
        $prefix = 'base64:';

        $key = base64_decode(Str::after($key, $prefix));

        return $key;
    }

    /**
     * Change key and update encrypted texts
     */
    public function changeKey(): void
    {
        // Get key before change it
        $oldKey = self::getEncryptedDataKey();

        // Generate new key
        $newKey = Encrypter::generateKey(self::ENCRYPTION_METHOD);

        // Get all encrypted data
        $records = EncryptedData::select(['id', 'iv', 'data'])->get();

        // Change values in all records
        foreach ($records as $record) {
            // Decrypt text
            $oldIv = base64_decode($record->iv);
            $plainText = self::decryptText($record->data, $oldIv, $oldKey);

            // Encrypt text with new key
            $newIv = self::generateIv();
            $cipherText = self::encryptText($plainText, $newIv, $newKey);

            // Store new values
            $record->iv = base64_encode($newIv);
            $record->data = $cipherText;
            $record->save();
        }

        // Remove previous key from environment file
        self::removeKeyFromEnvironmentFile();

        // Write new key in environment file
        self::addKeyInEnvironmentFile($newKey);

        // Set new key in config
        $key = 'base64:' . base64_encode($newKey);
        config(['app.encrypted_data.key' => $key]);
    }

    /**
     * Set IV value
     *
     * @param string $iv
     */
    public function setIv(string $iv): void
    {
        self::$iv = $iv;
    }

    /**
     * Get IV value
     *
     * @return string
     */
    public function getIv(): string
    {
        return self::$iv;
    }

    /**
     * Write key in environment file with the given key.
     *
     * @param string $key
     */
    public static function addKeyInEnvironmentFile($key)
    {
        $key = 'base64:' . base64_encode($key);

        $content = file_get_contents(App::environmentFilePath());

        $content .= "\nENCRYPTED_DATA_KEY=$key\n";

        file_put_contents(App::environmentFilePath(), $content);
    }

    /**
     * Get a regex pattern that will match env ENCRYPTED_DATA_KEY with any random key.
     *
     * @return string
     */
    public static function keyReplacementPattern()
    {
        $escaped = preg_quote('=' . config('app.encrypted_data.key'), '/');

        return "/^ENCRYPTED_DATA_KEY{$escaped}/m";
    }

    /**
     * Remove key from environment file.
     */
    public static function removeKeyFromEnvironmentFile()
    {
        $replaced = preg_replace(
            self::keyReplacementPattern(),
            '',
            $input = file_get_contents(App::environmentFilePath())
        );
        file_put_contents(App::environmentFilePath(), $replaced);
    }
}
