<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Encryption\Encrypter;

class ExportEncrypted
{
    public Encrypter $encrypter;

    public function __construct(string $password)
    {
        // Supported ciphers are: aes-128-cbc, aes-256-cbc, aes-128-gcm, aes-256-gcm.
        $cipher = config('app.cipher', 'AES-256-CBC');

        // Should be 32 character long for AES-256-CBC.
        $password = str_pad($password, 32, 'x', STR_PAD_RIGHT);

        $this->encrypter = new Encrypter($password, $cipher);
    }

    /**
     * Encrypt the export key.
     */
    public function call(array $package): array
    {
        $package['export'] = $this->encrypter->encrypt(json_encode($package['export']));
        $package['encrypted'] = true;

        return $package;
    }
}
