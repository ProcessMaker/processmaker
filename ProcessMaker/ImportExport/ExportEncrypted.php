<?php

namespace ProcessMaker\ImportExport;

class ExportEncrypted
{
    private $key;

    private $method;

    public function __construct(string $password)
    {
        $this->key = hash('sha256', $password, true);
        $this->method = config('app.cipher', 'AES-256-CBC');
    }

    /**
     * Encrypt the export key.
     */
    public function call(array $package): array
    {
        $plaintext = json_encode($package['export']);
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt($plaintext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        $base64 = base64_encode($iv . $ciphertext);

        $package['export'] = $base64;
        $package['encrypted'] = true;

        return $package;
    }

    /**
     * Decrypt the export key using the given password.
     */
    public function decrypt(array $package): array
    {
        $base64 = $package['export'];
        $ciphertext_iv = base64_decode($base64);

        $iv = substr($ciphertext_iv, 0, 16);
        $ciphertext = substr($ciphertext_iv, 16);

        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        $package['export'] = json_decode($plaintext, true);

        return $package;
    }
}
