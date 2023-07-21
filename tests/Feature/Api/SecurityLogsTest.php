<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Events\CategoryCreated;
use ProcessMaker\Events\CategoryDeleted;
use ProcessMaker\Events\CategoryUpdated;
use ProcessMaker\Events\EnvironmentVariablesCreated;
use ProcessMaker\Events\EnvironmentVariablesDeleted;
use ProcessMaker\Events\EnvironmentVariablesUpdated;
use ProcessMaker\Events\GroupCreated;
use ProcessMaker\Events\GroupDeleted;
use ProcessMaker\Events\GroupUpdated;
use ProcessMaker\Events\ProcessArchived;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Events\ProcessPublished;
use ProcessMaker\Events\ProcessRestored;
use ProcessMaker\Events\ScreenCreated;
use ProcessMaker\Events\ScreenDeleted;
use ProcessMaker\Events\ScreenUpdated;
use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Events\UserCreated;
use ProcessMaker\Events\UserDeleted;
use ProcessMaker\Events\UserRestored;
use ProcessMaker\Events\UserUpdated;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
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
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('SettingsUpdated', 'last_modified');
    }

    /**
     * This test the asserts related to the Security Log
     */
    public function checkAssertsSegurityLog(string $event, $date = 'created_at', $total = 1)
    {
        $collection = SecurityLog::get();
        // Check if the variable security_log is enable
        if (config('app.security_log')) {
            $this->assertCount($total, $collection);
            $securityLog = $collection->first();
            $this->assertEquals($event, $securityLog->getAttribute('event'));
            $this->assertIsObject($securityLog->getAttribute('data'));
            $this->assertArrayHasKey('name', get_object_vars($securityLog->getAttribute('data')));
            $this->assertArrayHasKey($date, get_object_vars($securityLog->getAttribute('data')));
            $this->assertIsObject($securityLog->getAttribute('changes'));
        } else {
            $this->assertCount(0, $collection);
        }
    }

    /**
     * This test Category Created
     */
    public function testCategoryCreated()
    {
        $fields = [
            'name' => 'var_1',
        ];
        ProcessCategory::factory()->create($fields);
        CategoryCreated::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('CategoryCreated', 'created_at');
    }

    /**
     * This test Category Deleted
     */
    public function testCategoryDeleted()
    {
        $processCategory = ProcessCategory::factory()->create();
        $processCategory->delete();
        CategoryDeleted::dispatch($processCategory);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('CategoryDeleted', 'deleted_at');
    }

    /**
     * This test Category Updated
     */
    public function testCategoryUpdated()
    {
        $processCategory = ProcessCategory::factory()->create();
        $original = $processCategory->getOriginal();
        $processCategory->fill(['name' => 'update name']);
        $processCategory->saveOrFail();
        $changes = $processCategory->getChanges();
        CategoryUpdated::dispatch($processCategory, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('CategoryUpdated', 'last_modified');
    }

    /**
     * This test Environment Variables Created
     */
    public function testEnvironmentVariablesCreated()
    {
        $fields = [
            'name' => 'var_1',
            'description' => 'description 1',
            'created_at' => '2019-01-01',
        ];
        EnvironmentVariable::factory()->create($fields);
        EnvironmentVariablesCreated::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('EnvironmentVariablesCreated', 'created_at');
    }

    /**
     * This test Environment Variables Deleted
     */
    public function testEnvironmentVariablesDeleted()
    {
        $vars = EnvironmentVariable::factory()->create([
            'name' => 'var_2',
        ]);
        $vars->delete();
        EnvironmentVariablesDeleted::dispatch($vars);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('EnvironmentVariablesDeleted', 'deleted_at');
    }

    /**
     * This test Environment Variables Updated
     */
    public function testEnvironmentVariablesUpdated()
    {
        $vars = EnvironmentVariable::factory()->create([
            'name' => 'var_3',
        ]);
        $original = $vars->getOriginal();
        $vars->fill(['description' => 'var description']);
        $vars->save();
        $changes = $vars->getChanges();
        EnvironmentVariablesUpdated::dispatch($vars, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('EnvironmentVariablesUpdated', 'last_modified');
    }

    /**
     * This test Group Created
     */
    public function testGroupCreated()
    {
        $fields = [
            'name' => 'group_1',
        ];
        $group = Group::factory()->create($fields);
        GroupCreated::dispatch($group);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('GroupCreated', 'created_at');
    }

    /**
     * This test Group Deleted
     */
    public function testGroupDeleted()
    {
        $group = Group::factory()->create([
            'name' => 'group_1',
        ]);
        $group->delete();
        GroupDeleted::dispatch($group, [], []);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('GroupDeleted', 'deleted_at');
    }

    /**
     * This test Group Updated
     */
    public function testGroupUpdated()
    {
        $group = Group::factory()->create([
            'name' => 'group_2',
        ]);
        $original = $group->getOriginal();
        $group->fill(['description' => 'group description']);
        $group->save();
        $changes = $group->getChanges();
        GroupUpdated::dispatch($group, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('GroupUpdated', 'last_modified');
    }

    /**
     * This test Process Archived
     */
    public function testProcessArchived()
    {
        $process = Process::factory()->create();
        $process->status = 'ARCHIVED';
        $process->save();
        ProcessArchived::dispatch($process);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ProcessArchived', 'last_modified');
    }

    /**
     * This test Process Created
     */
    public function testProcessCreated()
    {
        $process = Process::factory()->create();
        ProcessCreated::dispatch($process);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ProcessCreated', 'created_at');
    }

    /**
     * This test Process Published
     */
    public function testProcessPublished()
    {
        $process = Process::factory()->create();
        $original = $process->getOriginal();
        $process->fill(['description' => 'process description']);
        $process->saveOrFail();
        $changes = $process->getChanges();
        ProcessPublished::dispatch($process, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ProcessUpdated', 'last_modified');
    }

    /**
     * This test Process Restored
     */
    public function testProcessRestored()
    {
        $process = Process::factory()->create();
        $process->status = 'ARCHIVED';
        $process->save();
        $process->status = 'ACTIVE';
        $process->save();
        ProcessRestored::dispatch($process);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ProcessRestored', 'last_modified');
    }

    /**
     * This test Screen Created
     */
    public function testScreenCreated()
    {
        $fields = [
            'id' => 999,
            'title' => 'screen_1',
            'description' => 'screen_1',
        ];
        Screen::factory()->create($fields);
        ScreenCreated::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScreenCreated', 'created_at');
    }

    /**
     * This test Screen Deleted
     */
    public function testScreenDeleted()
    {
        $screen = Screen::factory()->create();
        $screen->delete();
        ScreenDeleted::dispatch($screen);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScreenDeleted', 'deleted_at');
    }

    /**
     * This test Screen Updated
     */
    public function testScreenUpdated()
    {
        $screen = Screen::factory()->create();
        $original = $screen->getOriginal();
        $screen->fill(['description' => 'screen description']);
        $screen->saveOrFail();
        $changes = $screen->getChanges();
        ScreenUpdated::dispatch($screen, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScreenUpdated', 'last_modified');
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
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('UserCreated', 'created_at');
    }

    /**
     * This test User Deleted
     */
    public function testUserDeleted()
    {
        // Create a user deleted
        $user = User::factory()->create([
            'status' => 'ACTIVE',
        ]);
        $user->delete();
        UserDeleted::dispatch($user);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('UserDeleted', 'deleted_at');
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
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('UserRestored', 'last_modified');
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
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('UserUpdated', 'last_modified');
    }
}
