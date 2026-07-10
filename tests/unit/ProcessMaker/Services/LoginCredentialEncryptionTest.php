<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Services;

use ProcessMaker\Services\LoginCredentialEncryption;
use Tests\Support\InteractsWithLoginEncryptionKeys;
use Tests\TestCase;

class LoginCredentialEncryptionTest extends TestCase
{
    use InteractsWithLoginEncryptionKeys;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLoginEncryptionKeys();
    }

    protected function tearDown(): void
    {
        $this->tearDownLoginEncryptionKeys();
        parent::tearDown();
    }

    public function test_it_encrypts_and_decrypts_login_credentials(): void
    {
        $cipher = $this->loginEncryption->encryptCredentials('test-user', 'secret-password');
        $credentials = $this->loginEncryption->decryptCredentials($cipher);

        $this->assertSame('test-user', $credentials['username']);
        $this->assertSame('secret-password', $credentials['password']);
    }

    public function test_it_rejects_expired_credentials(): void
    {
        $cipher = $this->loginEncryption->encryptCredentials('test-user', 'secret-password', time() - 301);

        $this->assertNull($this->loginEncryption->decryptCredentials($cipher));
    }

    public function test_it_rejects_invalid_cipher_text(): void
    {
        $this->assertNull($this->loginEncryption->decryptCredentials('not-valid-base64-cipher'));
    }

    public function test_it_encrypts_long_credentials_with_hybrid_encryption(): void
    {
        $cipher = $this->loginEncryption->encryptCredentials(
            str_repeat('u', 255),
            str_repeat('p', 200)
        );

        $credentials = $this->loginEncryption->decryptCredentials($cipher);

        $this->assertSame(str_repeat('u', 255), $credentials['username']);
        $this->assertSame(str_repeat('p', 200), $credentials['password']);
    }
}
