<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Http\Request;

class LoginCredentialEncryption
{
    private const KEYS_DIR = 'app/keys';

    private const PRIVATE_KEY = 'login_private.pem';

    private const PUBLIC_KEY = 'login_public.pem';

    private const HYBRID_VERSION = 2;

    private const GCM_TAG_LENGTH = 16;

    public function isEnabled(): bool
    {
        return (bool) config('auth.login_encrypt_credentials', true);
    }

    public function publicKeyForLogin(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->ensureKeyPair();

        return trim((string) file_get_contents($this->path(self::PUBLIC_KEY)));
    }

    public function mergeDecryptedCredentials(Request $request): bool
    {
        if (!$request->boolean('encrypted')) {
            return true;
        }

        if (!$this->isEnabled() || !$this->hasKeyPair()) {
            return false;
        }

        $credentials = $this->decryptCredentials((string) $request->input('encrypted_credentials', ''));
        if ($credentials === null) {
            return false;
        }

        $request->merge($credentials);

        return true;
    }

    /**
     * @return array{username: string, password: string}|null
     */
    public function decryptCredentials(string $cipherTextBase64): ?array
    {
        $decoded = base64_decode($cipherTextBase64, true);
        if ($decoded === false) {
            return null;
        }

        $bundle = json_decode($decoded, true);
        if (is_array($bundle) && (int) ($bundle['v'] ?? 0) === self::HYBRID_VERSION) {
            return $this->decryptHybridBundle($bundle);
        }

        return $this->decryptLegacyRsaPayload($cipherTextBase64);
    }

    public function encryptCredentials(string $username, string $password, ?int $timestamp = null): string
    {
        $this->ensureKeyPair();

        $payload = json_encode([
            'u' => $username,
            'p' => $password,
            't' => $timestamp ?? time(),
        ], JSON_THROW_ON_ERROR);

        $aesKey = random_bytes(32);
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($payload, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('Unable to encrypt login credentials.');
        }

        $publicKey = openssl_pkey_get_public((string) file_get_contents($this->path(self::PUBLIC_KEY)));
        $encryptedKey = '';
        if (
            $publicKey === false
            || !openssl_public_encrypt($aesKey, $encryptedKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING)
        ) {
            throw new \RuntimeException('Unable to encrypt login credentials key.');
        }

        return base64_encode(json_encode([
            'v' => self::HYBRID_VERSION,
            'k' => base64_encode($encryptedKey),
            'i' => base64_encode($iv),
            'd' => base64_encode($ciphertext . $tag),
        ], JSON_THROW_ON_ERROR));
    }

    public function generateKeyPair(): void
    {
        $directory = storage_path(self::KEYS_DIR);
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

        $privatePath = $this->path(self::PRIVATE_KEY);
        if (!openssl_pkey_export_to_file($key, $privatePath)) {
            throw new \RuntimeException('Unable to write login private key.');
        }

        $details = openssl_pkey_get_details($key);
        if ($details === false || empty($details['key'])) {
            throw new \RuntimeException('Unable to extract login public key.');
        }

        file_put_contents($this->path(self::PUBLIC_KEY), $details['key']);
        chmod($privatePath, 0600);
    }

    public function hasKeyPair(): bool
    {
        return is_readable($this->path(self::PRIVATE_KEY)) && is_readable($this->path(self::PUBLIC_KEY));
    }

    private function ensureKeyPair(): void
    {
        if (!$this->hasKeyPair()) {
            $this->generateKeyPair();
        }
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array{username: string, password: string}|null
     */
    private function decryptHybridBundle(array $bundle): ?array
    {
        if (!isset($bundle['k'], $bundle['i'], $bundle['d'])) {
            return null;
        }

        $privateKey = openssl_pkey_get_private((string) file_get_contents($this->path(self::PRIVATE_KEY)));
        $encryptedKey = base64_decode((string) $bundle['k'], true);
        $iv = base64_decode((string) $bundle['i'], true);
        $payload = base64_decode((string) $bundle['d'], true);
        $aesKey = '';

        if (
            $privateKey === false
            || $encryptedKey === false
            || $iv === false
            || $payload === false
            || strlen($payload) <= self::GCM_TAG_LENGTH
            || !openssl_private_decrypt($encryptedKey, $aesKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)
        ) {
            return null;
        }

        $tag = substr($payload, -self::GCM_TAG_LENGTH);
        $ciphertext = substr($payload, 0, -self::GCM_TAG_LENGTH);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);

        return is_string($plain) ? $this->parsePayload($plain) : null;
    }

    /**
     * @return array{username: string, password: string}|null
     */
    private function decryptLegacyRsaPayload(string $cipherTextBase64): ?array
    {
        $privateKey = openssl_pkey_get_private((string) file_get_contents($this->path(self::PRIVATE_KEY)));
        $cipher = base64_decode($cipherTextBase64, true);
        $plain = '';

        if ($privateKey === false || $cipher === false) {
            return null;
        }

        if (!openssl_private_decrypt($cipher, $plain, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            return null;
        }

        return $this->parsePayload($plain);
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

        if (abs(time() - (int) $data['t']) > (int) config('auth.login_encrypt_ttl', 300)) {
            return null;
        }

        return [
            'username' => (string) $data['u'],
            'password' => (string) $data['p'],
        ];
    }

    private function path(string $file): string
    {
        return storage_path(self::KEYS_DIR . '/' . $file);
    }
}
