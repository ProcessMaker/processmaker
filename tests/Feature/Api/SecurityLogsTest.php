<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Events\UserCreated;
use ProcessMaker\Events\UserDeleted;
use ProcessMaker\Events\UserRestored;
use ProcessMaker\Events\UserUpdated;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * @covers \ProcessMaker\Models\SecurityLog
 */
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

    /**
     * Return status 200
     */
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

    /**
     * Return status 201
     */
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
        $this->assertIsObject($securityLog->data);
    }

    /**
     * This test the Setting Update
     */
    public function testSettingUpdated()
    {
        $setting = Setting::factory()->create(['key' => 'users.properties']);
        $setting->config = ['city' => 'City of residence'];
        $original = array_intersect_key($setting->getOriginal(), $setting->getDirty());
        $setting->save();
        SettingsUpdated::dispatch($setting, $setting->getChanges(), $original);
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount(1, $collection);
            $securityLog = $collection->first();
            $this->assertEquals('SettingsUpdated', $securityLog->getAttribute('event'));
        } else {
            $this->assertCount(0, $collection);
        }
    }

    /**
     * This test User Created
     */
    public function testUserCreated()
    {
        // Create a user created
        $user = User::factory()->create([
            'status' => 'ACTIVE',
        ]);
        UserCreated::dispatch($user);
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount(1, $collection);
            $securityLog = $collection->first();
            $this->assertEquals('UserCreated', $securityLog->getAttribute('event'));
            $data = (array)json_decode($securityLog->getAttribute('data'));
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('created_at', $data);
        } else {
            $this->assertCount(0, $collection);
        }
    }

    /**
     * This test User Deleted
     */
    public function testUserDeleted()
    {
        // Create a user deleted
        $user = User::factory()->create([
            'deleted_at' => '2023-01-01',
            'status' => 'ACTIVE',
        ]);
        UserDeleted::dispatch($user);
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount(1, $collection);
            $securityLog = $collection->first();
            $this->assertEquals('UserDeleted', $securityLog->getAttribute('event'));
            $data = (array)json_decode($securityLog->getAttribute('data'));
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('deleted_at', $data);
        } else {
            $this->assertCount(0, $collection);
        }
    }

    /**
     * This test User Restored
     */
    public function testUserRestored()
    {
        // Create a user restored
        $user = User::factory()->create([
            'deleted_at' => null,
            'status' => 'ACTIVE',
        ]);
        UserRestored::dispatch($user);
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount(1, $collection);
            $securityLog = $collection->first();
            $this->assertEquals('UserRestored', $securityLog->getAttribute('event'));
            $data = (array)json_decode($securityLog->getAttribute('data'));
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('last_modified', $data);
        } else {
            $this->assertCount(0, $collection);
        }
    }

    /**
     * This test User Updated
     */
    public function testUserUpdated()
    {
        // Create a user updated
        $user = User::factory()->create([
            'status' => 'ACTIVE',
        ]);
        $original = $user->getOriginal();
        $user->fill(['timezone' => 'America/Monterrey']);
        $user->saveOrFail();
        $changes = $user->getChanges();
        UserUpdated::dispatch($user, $changes, $original);
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount(1, $collection);
            $securityLog = $collection->first();
            $this->assertEquals('UserUpdated', $securityLog->getAttribute('event'));
            $data = (array)json_decode($securityLog->getAttribute('data'));
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('last_modified', $data);
        } else {
            $this->assertCount(0, $collection);
        }
    }
}
