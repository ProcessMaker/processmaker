<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\Setting;
use ProcessMaker\Package\Auth\Database\Seeds\AtlassianSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\Auth0Seeder;
use ProcessMaker\Package\Auth\Database\Seeds\AuthSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\FacebookSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\GitHubSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\GoogleSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\KeycloakSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\LdapSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\MicrosoftSeeder;
use ProcessMaker\Package\Auth\Database\Seeds\SamlSeeder;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingAuthTest extends TestCase
{
    use RequestHelper;

    private function seedLDAPSettings()
    {
        ScriptExecutor::factory()->create([
            'title' => 'Node Executor',
            'description' => 'Default Javascript/Node Executor',
            'language' => 'javascript',
        ]);

        ProcessCategory::factory()->create([
            'name' => 'System',
            'status' => 'ACTIVE',
            'is_system' => true,
        ]);

        \Artisan::call('db:seed', ['--class' => LdapSeeder::class, '--force' => true]);
    }

    public function testDefaultLdapSettings()
    {
        $this->seedLDAPSettings();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'LDAP', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(18, $response['data']);

        $this->assertDatabaseCount('settings', 38);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.enabled', 'name' => 'Enabled', 'format' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.cron.period', 'name' => 'Synchronization Schedule', 'format' => 'object']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.type', 'name' => 'Type', 'format' => 'choice']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.server.address', 'name' => 'Server Address', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.server.port', 'name' => 'Server Port', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.server.tls', 'name' => 'TLS', 'format' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.base_dn', 'name' => 'Base DN', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.authentication.username', 'name' => 'Username', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.authentication.password', 'name' => 'Password', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.groups', 'name' => 'Groups To Import', 'format' => 'checkboxes']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.departments', 'name' => 'Departments To Import', 'format' => 'checkboxes']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.identifiers.user', 'name' => 'User Identifier', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.identifiers.group', 'name' => 'Group Identifier', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.identifiers.user_class', 'name' => 'User Class Identifier', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.identifiers.group_class', 'name' => 'Group Class Identifier', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.variables', 'name' => 'Variable Map', 'format' => 'object']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.users.chunksize', 'name' => 'Chunk Size for User Import', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.log', 'name' => 'Logs', 'format' => 'button']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.certificate_file', 'name' => 'Certificate location', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.ldap.certificate', 'name' => 'Certificate', 'format' => 'file']);

        $this->assertDatabaseCount('security_logs', 0);
    }

    public function testUpdateLdapSettings()
    {
        $this->seedLDAPSettings();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'LDAP', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(18, $response['data']);

        $enabled = $response['data'][0];
        $this->assertEquals('Enabled', $enabled['name']);
        $this->assertEquals(0, $enabled['config']);

        $syncSchedule = $response['data'][1];
        $this->assertEquals('Synchronization Schedule', $syncSchedule['name']);
        $this->assertEquals(['quantity' => 1, 'units' => 'days'], $syncSchedule['config']);

        $type = $response['data'][2];
        $this->assertEquals('Type', $type['name']);
        $this->assertNull($type['config']);

        $serverAddress = $response['data'][3];
        $this->assertEquals('Server Address', $serverAddress['name']);
        $this->assertNull($serverAddress['config']);

        $serverPort = $response['data'][4];
        $this->assertEquals('Server Port', $serverPort['name']);
        $this->assertEquals(636, $serverPort['config']);

        $tls = $response['data'][5];
        $this->assertEquals('TLS', $tls['name']);
        $this->assertEquals(1, $tls['config']);

        $username = $response['data'][8];
        $this->assertEquals('Username', $username['name']);
        $this->assertNull($username['config']);

        $password = $response['data'][9];
        $this->assertEquals('Password', $password['name']);
        $this->assertNull($password['config']);

        $data = array_merge($enabled, ['config' => 1]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $enabled['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $enabled['id'], 'config' => 1]);

        $data = array_merge($syncSchedule, ['config' => ['quantity' => 2, 'units' => 'hours']]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $syncSchedule['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $syncSchedule['id'], 'config' => json_encode(['quantity' => 2, 'units' => 'hours'])]);

        $data = array_merge($type, ['config' => 'ad']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $type['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $type['id'], 'config' => 'ad']);

        $data = array_merge($type, ['config' => '389ds']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $type['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $type['id'], 'config' => '389ds']);

        $data = array_merge($type, ['config' => 'openldap']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $type['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $type['id'], 'config' => 'openldap']);

        $data = array_merge($serverAddress, ['config' => 'ldap://ldap.example.com']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $serverAddress['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $serverAddress['id'], 'config' => 'ldap://ldap.example.com']);

        $data = array_merge($serverPort, ['config' => 389]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $serverPort['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $serverPort['id'], 'config' => 389]);

        $data = array_merge($tls, ['config' => 0]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $tls['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $tls['id'], 'config' => 0]);

        $data = array_merge($username, ['config' => 'admin']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $username['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $username['id'], 'config' => 'admin']);

        $data = array_merge($password, ['config' => 'password']);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $password['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $password['id'], 'config' => 'password']);

        $this->assertDatabaseCount('security_logs', 10);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $enabled['id']]);
    }

    public function testDefaultSsoSettings()
    {
        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'SSO', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(0, $response['data']);

        \Artisan::call('db:seed', ['--class' => AuthSeeder::class, '--force' => true]);

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'SSO', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(4, $response['data']);

        $this->assertDatabaseCount('settings', 23);
        $this->assertDatabaseHas('settings', ['key' => 'standard-login.enabled', 'name' => 'Allow Standard Login', 'format' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'sso.automatic_user_creation', 'name' => 'Automatic Registration', 'format' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'sso.user_default_config', 'name' => 'New User Default Config', 'format' => 'object']);
        $this->assertDatabaseHas('settings', ['key' => 'sso.debug', 'name' => 'Debug Mode', 'format' => 'boolean']);
        $this->assertDatabaseHas('settings', ['key' => 'package.auth.installed']);

        \Artisan::call('db:seed', ['--class' => AtlassianSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => Auth0Seeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => FacebookSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => GitHubSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => GoogleSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => KeycloakSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => MicrosoftSeeder::class, '--force' => true]);
        \Artisan::call('db:seed', ['--class' => SamlSeeder::class, '--force' => true]);

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'SSO', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(12, $response['data']);
        $this->assertDatabaseCount('settings', 69);

        $this->assertDatabaseHas('settings', ['key' => 'services.atlassian.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.atlassian.client_secret', 'name' => 'Client Secret', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.atlassian.redirect', 'name' => 'Redirect', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.auth0.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.auth0.redirect', 'name' => 'Callback URL', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.auth0.client_secret', 'name' => 'Client Secret', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.auth0.base_url', 'name' => 'Domain', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.facebook.client_id', 'name' => 'App ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.facebook.client_secret', 'name' => 'App Secret', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.facebook.redirect', 'name' => 'Redirect', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.github.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.github.redirect', 'name' => 'Redirect', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.github.client_secret', 'name' => 'Client Secret', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.google.redirect', 'name' => 'Redirect', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.google.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.google.client_secret', 'name' => 'Client Secret', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.keycloak.base_url', 'name' => 'Base URL', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.keycloak.client_secret', 'name' => 'Client Secret', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.keycloak.realms', 'name' => 'Realm', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.keycloak.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.keycloak.redirect', 'name' => 'Redirect', 'format' => 'text']);

        $this->assertDatabaseHas('settings', ['key' => 'services.microsoft.redirect', 'name' => 'Redirect', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.microsoft.client_id', 'name' => 'Client ID', 'format' => 'text']);
        $this->assertDatabaseHas('settings', ['key' => 'services.microsoft.client_secret', 'name' => 'Client Secret', 'format' => 'text']);

        $this->assertDatabaseCount('security_logs', 0);
    }

    public function testUpdateSsoSettings()
    {
        \Artisan::call('db:seed', ['--class' => AuthSeeder::class, '--force' => true]);

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'SSO', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(4, $response['data']);

        $allowStandardLogin = $response['data'][0];
        $this->assertEquals('Allow Standard Login', $allowStandardLogin['name']);
        $this->assertEquals(1, $allowStandardLogin['config']);

        $automaticRegistration = $response['data'][1];
        $this->assertEquals('Automatic Registration', $automaticRegistration['name']);
        $this->assertEquals(1, $automaticRegistration['config']);

        $newUserDefaultConfig = $response['data'][2];
        $this->assertEquals('New User Default Config', $newUserDefaultConfig['name']);
        $this->assertEquals(['permissions' => [], 'groups' => []], $newUserDefaultConfig['config']);

        $debugMode = $response['data'][3];
        $this->assertEquals('Debug Mode', $debugMode['name']);
        $this->assertEquals(0, $debugMode['config']);

        $data = array_merge($allowStandardLogin, ['config' => 1]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $allowStandardLogin['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $allowStandardLogin['id'], 'config' => 1]);

        $data = array_merge($automaticRegistration, ['config' => 0]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $automaticRegistration['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $automaticRegistration['id'], 'config' => 0]);

        $data = array_merge($newUserDefaultConfig, ['config' => ['permissions' => ['view', 'edit'], 'groups' => ['admin', 'user']]]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $newUserDefaultConfig['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $newUserDefaultConfig['id'], 'config' => json_encode(['permissions' => ['view', 'edit'], 'groups' => ['admin', 'user']])]);

        $data = array_merge($debugMode, ['config' => 1]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $debugMode['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $debugMode['id'], 'config' => 1]);

        $this->assertDatabaseCount('security_logs', 4);
    }

    public function testConfigCacheUpdatedAfterSettingEdit()
    {
        $setting = Setting::factory()->create([
            'name' => 'Allow Standard Login',
            'key' => 'standard-login.enabled',
            'config' => true,
            'group' => 'SSO',
            'format' => 'boolean',
        ]);

        $this->assertDatabaseHas('settings', ['key' => $setting->key, 'config' => $setting->config]);

        $this->assertTrue(config('standard-login.enabled'));

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'SSO', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(1, $response['data']);
        $standardLogin = $response['data'][0];
        $this->assertEquals('Allow Standard Login', $standardLogin['name']);
        $this->assertTrue($standardLogin['config']);

        // Update setting config
        $data = array_merge($standardLogin, ['config' => false]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $standardLogin['id']]), $data);
        // Verify the status
        $response->assertStatus(204);
        // Verify variables were updated
        $this->assertDatabaseHas('settings', ['id' => $standardLogin['id'], 'config' => false]);

        // Check if the config cache was updated
        $this->assertFalse(config('standard-login.enabled'));
    }
}
