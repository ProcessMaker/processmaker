<?php

namespace Tests\Feature\Api;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingLogInOptionsTest extends TestCase
{
    use RequestHelper;

    private function upgrade()
    {
        require_once base_path('upgrades/2023_11_30_185738_add_password_policies_settings.php');
        $upgrade = new \AddPasswordPoliciesSettings();
        $upgrade->up();
    }

    public function testDefaultLogInOptionsSettings()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(10, $response['data']);
        $response->assertJsonFragment(['name' => 'Password set by user', 'key' => 'password-policies.users_can_change', 'format' => 'boolean']);
        $response->assertJsonFragment(['name' => 'Numeric characters', 'key' => 'password-policies.numbers', 'format' => 'boolean']);
        $response->assertJsonFragment(['name' => 'Uppercase characters', 'key' => 'password-policies.uppercase', 'format' => 'boolean']);
        $response->assertJsonFragment(['name' => 'Special characters', 'key' => 'password-policies.special', 'format' => 'boolean']);
        $response->assertJsonFragment(['name' => 'Maximum length', 'key' => 'password-policies.maximum_length', 'format' => 'text']);
        $response->assertJsonFragment(['name' => 'Minimum length', 'key' => 'password-policies.minimum_length', 'format' => 'text']);
        $response->assertJsonFragment(['name' => 'Password expiration', 'key' => 'password-policies.expiration_days', 'format' => 'text']);
        $response->assertJsonFragment(['name' => 'Login failed', 'key' => 'password-policies.login_attempts', 'format' => 'text']);
        $response->assertJsonFragment(['name' => 'Require Two Step Authentication', 'key' => 'password-policies.2fa_enabled', 'format' => 'boolean']);
        $response->assertJsonFragment(['name' => 'Two Step Authentication Method', 'key' => 'password-policies.2fa_method', 'format' => 'checkboxes']);

        $this->assertDatabaseCount('security_logs', 0);
    }

    public function testUpdatePasswordSetByUserSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $passwordSetByUser = $response['data'][0];
        $this->assertEquals('Password set by user', $passwordSetByUser['name']);
        $this->assertEquals(true, $passwordSetByUser['config']);

        $data = array_merge($passwordSetByUser, ['config' => false]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $passwordSetByUser['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $passwordSetByUser['id'], 'config' => false]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $passwordSetByUser['id']]);
    }

    public function testUpdateNumericCharactersSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $numericCharacters = $response['data'][1];
        $this->assertEquals('Numeric characters', $numericCharacters['name']);
        $this->assertEquals(true, $numericCharacters['config']);

        $data = array_merge($numericCharacters, ['config' => false]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $numericCharacters['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $numericCharacters['id'], 'config' => false]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $numericCharacters['id']]);
    }

    public function testUpdateUppercaseCharactersSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $uppercaseCharacters = $response['data'][2];
        $this->assertEquals('Uppercase characters', $uppercaseCharacters['name']);
        $this->assertEquals(true, $uppercaseCharacters['config']);

        $data = array_merge($uppercaseCharacters, ['config' => false]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $uppercaseCharacters['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $uppercaseCharacters['id'], 'config' => false]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $uppercaseCharacters['id']]);
    }

    public function testUpdateSpecialCharactersSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $specialCharacters = $response['data'][3];
        $this->assertEquals('Special characters', $specialCharacters['name']);
        $this->assertEquals(true, $specialCharacters['config']);

        $data = array_merge($specialCharacters, ['config' => false]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $specialCharacters['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $specialCharacters['id'], 'config' => false]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $specialCharacters['id']]);
    }

    public function testUpdateMaximumLengthSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $maximumLength = $response['data'][4];
        $this->assertEquals('Maximum length', $maximumLength['name']);
        $this->assertNull($maximumLength['config']);

        $data = array_merge($maximumLength, ['config' => '64']);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $maximumLength['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $maximumLength['id'], 'config' => '64']);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $maximumLength['id']]);
    }

    public function testUpdateMinimumLengthSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $minimumLength = $response['data'][5];
        $this->assertEquals('Minimum length', $minimumLength['name']);
        $this->assertEquals(8, $minimumLength['config']);

        $data = array_merge($minimumLength, ['config' => '10']);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $minimumLength['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $minimumLength['id'], 'config' => '10']);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $minimumLength['id']]);
    }

    public function testUpdatePasswordExpirationSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $passwordExpiration = $response['data'][6];
        $this->assertEquals('Password expiration', $passwordExpiration['name']);
        $this->assertNull($passwordExpiration['config']);

        $data = array_merge($passwordExpiration, ['config' => '30']);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $passwordExpiration['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $passwordExpiration['id'], 'config' => '30']);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $passwordExpiration['id']]);
    }

    public function testUpdateLoginFailedSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $loginFailed = $response['data'][7];
        $this->assertEquals('Login failed', $loginFailed['name']);
        $this->assertEquals(5, $loginFailed['config']);

        $data = array_merge($loginFailed, ['config' => '3']);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $loginFailed['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $loginFailed['id'], 'config' => '3']);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $loginFailed['id']]);
    }

    public function testUpdateRequireTwoStepAuthenticationSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $requireTwoStepAuthentication = $response['data'][8];
        $this->assertEquals('Require Two Step Authentication', $requireTwoStepAuthentication['name']);
        $this->assertEquals(false, $requireTwoStepAuthentication['config']);

        $data = array_merge($requireTwoStepAuthentication, ['config' => true]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $requireTwoStepAuthentication['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $requireTwoStepAuthentication['id'], 'config' => true]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $requireTwoStepAuthentication['id']]);
    }

    public function testUpdateTwoStepAuthenticationMethodSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Log-In Options', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(10, $response['data']);
        $twoStepAuthenticationMethod = $response['data'][9];
        $this->assertEquals('Two Step Authentication Method', $twoStepAuthenticationMethod['name']);
        $this->assertEquals([], $twoStepAuthenticationMethod['config']);

        $data = array_merge($twoStepAuthenticationMethod, ['config' => [['By email']]]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $twoStepAuthenticationMethod['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $twoStepAuthenticationMethod['id'], 'config' => json_encode([['By email']])]);

        $data = array_merge($twoStepAuthenticationMethod, ['config' => [['By message to phone number']]]);

        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $twoStepAuthenticationMethod['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $twoStepAuthenticationMethod['id'], 'config' => json_encode([['By message to phone number']])]);

        $data = array_merge($twoStepAuthenticationMethod, ['config' => [['Authenticator App']]]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $twoStepAuthenticationMethod['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $twoStepAuthenticationMethod['id'], 'config' => json_encode([['Authenticator App']])]);

        $this->assertDatabaseCount('security_logs', 3);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $twoStepAuthenticationMethod['id']]);
    }
}
