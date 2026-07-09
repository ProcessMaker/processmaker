<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

class LoginCredentialEncryption
{
    private const PRIVATE_KEY_FILE = 'login_private.pem';

    private const PUBLIC_KEY_FILE = 'login_public.pem';

    public function isEnabled(): bool
    {
        return (bool) config('auth.login_encrypt_credentials', true);
    }

    public function hasKeyPair(): bool
    {
        return is_readable($this->privateKeyPath()) && is_readable($this->publicKeyPath());
    }

    public function ensureKeyPair(): void
    {
        if (!$this->hasKeyPair()) {
            $this->generateKeyPair();
        }
    }

    public function getPublicKeyPem(): ?string
    {
        if (!$this->hasKeyPair()) {
            return null;
        }

        return trim((string) file_get_contents($this->publicKeyPath()));
    }

    /**
     * @return array{username: string, password: string}|null
     */
    public function decryptCredentials(string $cipherTextBase64): ?array
    {
        $privateKey = openssl_pkey_get_private((string) file_get_contents($this->privateKeyPath()));
        if ($privateKey === false) {
            return null;
        }

        $cipher = base64_decode($cipherTextBase64, true);
        if ($cipher === false) {
            return null;
        }

        $plain = '';
        $success = openssl_private_decrypt($cipher, $plain, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$success) {
            return null;
        }

        return $this->parsePayload($plain);
    }

    public function encryptCredentials(string $username, string $password, ?int $timestamp = null): string
    {
        $this->ensureKeyPair();

        $payload = json_encode([
            'u' => $username,
            'p' => $password,
            't' => $timestamp ?? time(),
        ], JSON_THROW_ON_ERROR);

        $publicKey = openssl_pkey_get_public((string) file_get_contents($this->publicKeyPath()));
        if ($publicKey === false) {
            throw new \RuntimeException('Unable to load login public key.');
        }

        $encrypted = '';
        $success = openssl_public_encrypt($payload, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$success) {
            throw new \RuntimeException('Unable to encrypt login credentials.');
        }

        return base64_encode($encrypted);
    }

    public function generateKeyPair(): void
    {
        $directory = $this->keysDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new \RuntimeException('Unable to generate login RSA key pair.');
        }

        if (!openssl_pkey_export_to_file($key, $this->privateKeyPath())) {
            throw new \RuntimeException('Unable to write login private key.');
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || empty($details['key'])) {
            throw new \RuntimeException('Unable to extract login public key.');
        }

        file_put_contents($this->publicKeyPath(), $details['key']);
        chmod($this->privateKeyPath(), 0600);
    }

    /**
     * @return array{username: string, password: string}|null
     */
    private function parsePayload(string $json): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['u'], $data['p'], $data['t'])) {
            return null;
        }

        $ttl = (int) config('auth.login_encrypt_ttl', 300);
        $timestamp = (int) $data['t'];
        if (abs(time() - $timestamp) > $ttl) {
            return null;
        }

        return [
            'username' => (string) $data['u'],
            'password' => (string) $data['p'],
        ];
    }

    private function keysDirectory(): string
    {
        return storage_path('app/keys');
    }

    private function privateKeyPath(): string
    {
        return $this->keysDirectory() . '/' . self::PRIVATE_KEY_FILE;
    }

    private function publicKeyPath(): string
    {
        return $this->keysDirectory() . '/' . self::PUBLIC_KEY_FILE;
    }
}
