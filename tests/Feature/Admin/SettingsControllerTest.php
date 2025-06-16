<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un usuario administrador
        $this->admin = User::factory()->create([
            'is_administrator' => true,
        ]);
    }

    /**
     * Test storing a valid white_list setting with a valid URL
     */
    public function testStoreValidWhiteListUrl()
    {
        $setting = Setting::create([
            'key' => 'white_list.google',
            'config' => 'https://old-url.com',
            'name' => 'Google Docs',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.google',
            'config' => 'https://docs.google.com',
            'name' => 'Google Docs',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(204);
    }

    /**
     * Test storing a white_list setting with an invalid URL
     */
    public function testStoreInvalidWhiteListUrl()
    {
        $setting = Setting::create([
            'key' => 'white_list.invalid',
            'config' => 'https://old-url.com',
            'name' => 'Invalid URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.invalid',
            'config' => 'invalid-url',
            'name' => 'Invalid URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a non-https URL
     */
    public function testStoreNonHttpsWhiteListUrl()
    {
        $setting = Setting::create([
            'key' => 'white_list.ftp',
            'config' => 'https://old-url.com',
            'name' => 'FTP URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.ftp',
            'config' => 'ftp://example.com',
            'name' => 'FTP URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a non-white_list setting (should not validate URL)
     */
    public function testStoreNonWhiteListSetting()
    {
        $setting = Setting::create([
            'key' => 'other.setting',
            'config' => 'old-value',
            'name' => 'Other Setting',
            'format' => 'text',
            'group' => 'Other Group',
        ]);

        $data = [
            'key' => 'other.setting',
            'config' => 'not-a-url',
            'name' => 'Other Setting',
            'format' => 'text',
            'group' => 'Other Group',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(204);
    }

    /**
     * Test storing a duplicate white_list setting
     */
    public function testStoreDuplicateWhiteListSetting()
    {
        Setting::create([
            'key' => 'white_list.duplicate',
            'config' => 'https://example.com',
            'name' => 'Duplicate Setting',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $setting = Setting::create([
            'key' => 'white_list.other',
            'config' => 'https://old-url.com',
            'name' => 'Other Setting',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.duplicate',
            'config' => 'https://another-example.com',
            'name' => 'Duplicate Setting',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a valid URL including port
     */
    public function testStoreWhiteListUrlWithPort()
    {
        $setting = Setting::create([
            'key' => 'white_list.local',
            'config' => 'https://old-url.com',
            'name' => 'Local Development',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.local',
            'config' => 'http://localhost:8080',
            'name' => 'Local Development',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a valid URL including subdomain
     */
    public function testStoreWhiteListUrlWithSubdomain()
    {
        $setting = Setting::create([
            'key' => 'white_list.api',
            'config' => 'https://old-url.com',
            'name' => 'API URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.api',
            'config' => 'https://api.example.com',
            'name' => 'API URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(204);
    }

    /**
     * Test storing a white_list setting with a URL containing special characters
     */
    public function testStoreWhiteListUrlWithSpecialCharacters()
    {
        $setting = Setting::create([
            'key' => 'white_list.special',
            'config' => 'https://old-url.com',
            'name' => 'Special Characters URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.special',
            'config' => 'https://example.com/path-with-special-chars_123',
            'name' => 'Special Characters URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a URL containing query parameters
     */
    public function testStoreWhiteListUrlWithQueryParams()
    {
        $setting = Setting::create([
            'key' => 'white_list.query',
            'config' => 'https://old-url.com',
            'name' => 'Query Parameters URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.query',
            'config' => 'https://example.com?param=value',
            'name' => 'Query Parameters URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a URL containing wildcard subdomain
     */
    public function testStoreWhiteListUrlWithWildcardSubdomain()
    {
        $setting = Setting::create([
            'key' => 'white_list.wildcard',
            'config' => 'https://old-url.com',
            'name' => 'Wildcard Subdomain URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.wildcard',
            'config' => 'https://*.example.com',
            'name' => 'Wildcard Subdomain URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(204);
    }

    /**
     * Test storing a white_list setting with an empty URL
     */
    public function testStoreWhiteListUrlWithEmptyValue()
    {
        $setting = Setting::create([
            'key' => 'white_list.empty',
            'config' => 'https://old-url.com',
            'name' => 'Empty URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.empty',
            'config' => '',
            'name' => 'Empty URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a URL containing uppercase letters
     */
    public function testStoreWhiteListUrlWithUppercase()
    {
        $setting = Setting::create([
            'key' => 'white_list.uppercase',
            'config' => 'https://old-url.com',
            'name' => 'Uppercase URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.uppercase',
            'config' => 'HTTPS://EXAMPLE.COM',
            'name' => 'Uppercase URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a URL containing IP address
     */
    public function testStoreWhiteListUrlWithIpAddress()
    {
        $this->actingAs($this->admin);

        $setting = Setting::create([
            'key' => 'white_list.ip',
            'config' => 'https://old-url.com',
            'name' => 'IP Address URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.ip',
            'config' => 'https://192.168.1.1',
            'name' => 'IP Address URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }

    /**
     * Test storing a white_list setting with a URL containing IP address
     */
    public function testStoreWhiteListUrlWithString()
    {
        $this->actingAs($this->admin);

        $setting = Setting::create([
            'key' => 'white_list.default',
            'config' => '',
            'name' => 'Default URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
        ]);

        $data = [
            'key' => 'white_list.ip',
            'config' => 'aaaabbb',
            'name' => 'Default URL',
            'format' => 'text',
            'group' => 'IFrame Whitelist Config',
            'hidden' => false,
            'readonly' => false,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/1.0/settings/{$setting->id}", $data);

        $response->assertStatus(422);
    }
}
