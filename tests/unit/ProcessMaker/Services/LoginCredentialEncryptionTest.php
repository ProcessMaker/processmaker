<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Services;

use ProcessMaker\Services\LoginCredentialEncryption;
use Tests\TestCase;

class LoginCredentialEncryptionTest extends TestCase
{
    private LoginCredentialEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.login_encrypt_credentials' => true,
            'auth.login_encrypt_ttl' => 300,
        ]);

        $this->encryption = new LoginCredentialEncryption();
        $this->encryption->generateKeyPair();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/keys/login_private.pem'));
        @unlink(storage_path('app/keys/login_public.pem'));

        parent::tearDown();
    }

    public function test_it_encrypts_and_decrypts_login_credentials(): void
    {
        $cipher = $this->encryption->encryptCredentials('test-user', 'secret-password');

        $credentials = $this->encryption->decryptCredentials($cipher);

        $this->assertSame('test-user', $credentials['username']);
        $this->assertSame('secret-password', $credentials['password']);
    }

    public function test_it_rejects_expired_credentials(): void
    {
        $cipher = $this->encryption->encryptCredentials('test-user', 'secret-password', time() - 301);

        $this->assertNull($this->encryption->decryptCredentials($cipher));
    }

    public function test_it_rejects_invalid_cipher_text(): void
    {
        $this->assertNull($this->encryption->decryptCredentials('not-valid-base64-cipher'));
    }
}
