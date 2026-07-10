<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use Tests\Support\InteractsWithLoginEncryptionKeys;
use Tests\TestCase;

class LoginEncryptionTest extends TestCase
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

    public function test_login_with_encrypted_credentials(): void
    {
        User::factory()->create([
            'username' => 'encrypted-user',
            'password' => Hash::make('encrypted-password'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('login', [
            'encrypted' => 1,
            'encrypted_credentials' => $this->loginEncryption->encryptCredentials('encrypted-user', 'encrypted-password'),
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
        $response->assertSee('BEGIN PUBLIC KEY', false);
        $response->assertSee('login-form', false);
    }
}
