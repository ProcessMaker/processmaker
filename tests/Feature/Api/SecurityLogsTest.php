<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class SecurityLogsTest extends TestCase
{
    use RequestHelper;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        // Seed our tables.
        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Attempt to access security logs
     */
    public function testAccessSecurityLogsApi()
    {
        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(403);

        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('GET', '/security-logs');
        $response->assertStatus(200);
    }

    public function testSearchSecurityLogsApi()
    {
        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();

        // Create security logs for two users.
        SecurityLog::factory()->create([
            'event' => 'login',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Firefox',
                ],
            ],
        ]);
        SecurityLog::factory()->create([
            'event' => 'login',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Chrome',
                ],
            ],
        ]);
        $anotherUser = User::factory()->create();
        SecurityLog::factory()->create([
            'event' => 'login',
            'user_id' => $anotherUser->id,
            'meta' => [
                'os' => [
                    'name' => 'OS X',
                ],
                'browser' => [
                    'name' => 'Firefox',
                ],
            ],
        ]);

        // Test that the results obtained are from the user in session.
        $response = $this->apiCall('GET', '/security-logs', [
            'pmql' => "user_id={$this->user->id}",
            'filter' => 'firefox',
        ]);
        $response->assertStatus(200);
        $results = $response->getData()->data;
        $this->assertCount(1, $results);

        // Test a PMQL search query.
        $response = $this->apiCall('GET', '/security-logs', [
            'pmql' => 'user_id=' . $this->user->id . ' AND (event = "login")',
            'filter' => '',
        ]);
        $response->assertStatus(200);
        $results = $response->getData()->data;
        $this->assertCount(2, $results);
    }

    public function testStore()
    {
        $response = $this->apiCall('POST', '/security-logs');
        $response->assertStatus(403);

        $permission = Permission::byName('create-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('POST', '/security-logs', [
            'event' => 'TestStoreEvent',
            'ip' => '127.0.01',
            'meta' => [
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
                'browser' => [
                    'name' => 'Chrome',
                    'version' => '111',
                ],
                'os' => [
                    'name' => 'Linux',
                    'version' => null,
                ],
            ],
            'user_id' => $this->user->id,
            'occurred_at' => time(),
            'data' => [
                'fullname' => $this->user->getAttribute('fullname'),
            ],
        ]);
        $response->assertStatus(201);
        $collection = SecurityLog::where('user_id', $this->user->id)->get();
        $this->assertCount(2, $collection);
        $securityLog = $collection->skip(1)->first();
        $this->assertEquals([
            'fullname' => $this->user->getAttribute('fullname'),
        ], (array) $securityLog->data);
        $this->assertEquals([
            'event' => 'TestStoreEvent',
            'ip' => '127.0.01',
            'user_id' => $this->user->id,
            'meta' => (object) [
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36',
                'browser' => (object) [
                    'name' => 'Chrome',
                    'version' => '111',
                ],
                'os' => (object) [
                    'name' => 'Linux',
                    'version' => null,
                ],
            ],
        ], $securityLog->only('event', 'ip', 'user_id', 'meta'));
    }

    public function testSettingUpdated()
    {
        $setting = Setting::factory()->create(['key' => 'users.properties']);
        $setting->config = ['city' => 'City of residence'];
        $original = array_intersect_key($setting->getOriginal(), $setting->getDirty());
        $setting->save();
        SettingsUpdated::dispatch($setting, $setting->getChanges(), $original);

        $collection = SecurityLog::get();
        $this->assertCount(1, $collection);
        $securityLog = $collection->first();
        $this->assertEquals('SettingsUpdated', $securityLog->getAttribute('event'));
    }
}
