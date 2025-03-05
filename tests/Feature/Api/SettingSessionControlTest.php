<?php

namespace Tests\Feature\Api;

use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SettingSessionControlTest extends TestCase
{
    use RequestHelper;

    private function upgrade()
    {
        require_once base_path('upgrades/2023_12_06_182508_add_session_control_settings.php');
        $upgrade = new \AddSessionControlSettings();
        $upgrade->up();
    }

    public function testDefaultSessionControlSettings()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Session Control', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $response->assertStatus(200);
        $this->assertCount(3, $response['data']);
        $response->assertJsonFragment(['name' => 'IP restriction', 'key' => 'session-control.ip_restriction', 'format' => 'choice']);
        $response->assertJsonFragment(['name' => 'Device restriction', 'key' => 'session-control.device_restriction', 'format' => 'choice']);
        $response->assertJsonFragment(['name' => 'Session Inactivity', 'key' => 'session.lifetime', 'format' => 'text']);

        $this->assertDatabaseCount('settings', 21);
        $this->assertDatabaseCount('security_logs', 0);
    }

    public function testUpdateIPRestrictionSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Session Control', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(3, $response['data']);
        $ipRestriction = $response['data'][0];
        $this->assertEquals('IP restriction', $ipRestriction['name']);
        $this->assertEquals(0, $ipRestriction['config']);

        $data = array_merge($ipRestriction, ['config' => 1]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $ipRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $ipRestriction['id'], 'config' => 1]);

        $data = array_merge($ipRestriction, ['config' => 2]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $ipRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $ipRestriction['id'], 'config' => 2]);

        $data = array_merge($ipRestriction, ['config' => 0]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $ipRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $ipRestriction['id'], 'config' => 0]);

        $this->assertDatabaseCount('security_logs', 3);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $ipRestriction['id']]);
    }

    public function testUpdateDeviceRestrictionSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Session Control', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(3, $response['data']);
        $deviceRestriction = $response['data'][1];
        $this->assertEquals('Device restriction', $deviceRestriction['name']);
        $this->assertEquals(0, $deviceRestriction['config']);

        $data = array_merge($deviceRestriction, ['config' => 1]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $deviceRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $deviceRestriction['id'], 'config' => 1]);

        $data = array_merge($deviceRestriction, ['config' => 2]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $deviceRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $deviceRestriction['id'], 'config' => 2]);

        $data = array_merge($deviceRestriction, ['config' => 0]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $deviceRestriction['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $deviceRestriction['id'], 'config' => 0]);

        $this->assertDatabaseCount('security_logs', 3);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $deviceRestriction['id']]);
    }

    public function testUpdateSessionLifetimeSetting()
    {
        $this->upgrade();

        $response = $this->apiCall('GET', route('api.settings.index', ['group' => 'Session Control', 'order_by' => 'name', 'order_direction' => 'ASC']));
        $this->assertCount(3, $response['data']);
        $sessionLifetime = $response['data'][2];
        $this->assertEquals('Session Inactivity', $sessionLifetime['name']);
        $this->assertEquals(120, $sessionLifetime['config']);

        $data = array_merge($sessionLifetime, ['config' => 30]);
        $response = $this->apiCall('PUT', route('api.settings.update', ['setting' => $sessionLifetime['id']]), $data);
        $response->assertStatus(204);
        $this->assertDatabaseHas('settings', ['id' => $sessionLifetime['id'], 'config' => 30]);

        $this->assertDatabaseCount('security_logs', 1);
        $this->assertDatabaseHas('security_logs', ['event' => 'SettingsUpdated', 'changes->setting_id' => $sessionLifetime['id']]);
    }
}
