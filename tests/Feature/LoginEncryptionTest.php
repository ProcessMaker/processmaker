<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use ProcessMaker\Services\LoginCredentialEncryption;
use Tests\TestCase;

class LoginEncryptionTest extends TestCase
{
    private LoginCredentialEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.login_encrypt_credentials' => true,
            'auth.login_encrypt_ttl' => 300,
        ]);

        $this->encryption = app(LoginCredentialEncryption::class);
        $this->encryption->generateKeyPair();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/keys/login_private.pem'));
        @unlink(storage_path('app/keys/login_public.pem'));

        parent::tearDown();
    }

    public function test_login_with_encrypted_credentials(): void
    {
        User::factory()->create([
            'username' => 'encrypted-user',
            'password' => Hash::make('encrypted-password'),
            'status' => 'ACTIVE',
        ]);

        $cipher = $this->encryption->encryptCredentials('encrypted-user', 'encrypted-password');

        $response = $this->post('login', [
            'encrypted' => 1,
            'encrypted_credentials' => $cipher,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_encrypted_credentials(): void
    {
        User::factory()->create([
            'username' => 'encrypted-user',
            'password' => Hash::make('encrypted-password'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('login', [
            'encrypted' => 1,
            'encrypted_credentials' => 'invalid-cipher-text',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_login_page_exposes_public_key_when_encryption_is_enabled(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('login-public-key', false);
        $response->assertSee($this->encryption->getPublicKeyPem(), false);
    }
}
