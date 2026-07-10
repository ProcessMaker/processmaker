<?php

declare(strict_types=1);

namespace Tests\Support;

use ProcessMaker\Services\LoginCredentialEncryption;

trait InteractsWithLoginEncryptionKeys
{
    protected LoginCredentialEncryption $loginEncryption;

    protected function setUpLoginEncryptionKeys(): void
    {
        config([
            'auth.login_encrypt_credentials' => true,
            'auth.login_encrypt_ttl' => 300,
        ]);

        $this->loginEncryption = app(LoginCredentialEncryption::class);
        $this->loginEncryption->generateKeyPair();
    }

    protected function tearDownLoginEncryptionKeys(): void
    {
        @unlink(storage_path('app/keys/login_private.pem'));
        @unlink(storage_path('app/keys/login_public.pem'));
    }
}
