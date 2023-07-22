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
use ProcessMaker\Events\ScriptCreated;
use ProcessMaker\Events\ScriptDeleted;
use ProcessMaker\Events\ScriptDuplicated;
use ProcessMaker\Events\ScriptExecutorCreated;
use ProcessMaker\Events\ScriptExecutorDeleted;
use ProcessMaker\Events\ScriptExecutorUpdated;
use ProcessMaker\Events\ScriptUpdated;
use ProcessMaker\Events\SettingsUpdated;
use ProcessMaker\Events\SignalCreated;
use ProcessMaker\Events\SignalDeleted;
use ProcessMaker\Events\SignalUpdated;
use ProcessMaker\Events\TemplateCreated;
use ProcessMaker\Events\TemplateDeleted;
use ProcessMaker\Events\TemplatePublished;
use ProcessMaker\Events\TemplateUpdated;
use ProcessMaker\Events\TokenCreated;
use ProcessMaker\Events\TokenDeleted;
use ProcessMaker\Events\UserCreated;
use ProcessMaker\Events\UserDeleted;
use ProcessMaker\Events\UserRestored;
use ProcessMaker\Events\UserUpdated;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

/**
 * @covers \ProcessMaker\Models\SecurityLog
 */
class SecurityLogsTest extends TestCase
{
    use HelperTrait;
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
     * This test Script Created
     */
    public function testScriptCreated()
    {
        $script = Script::factory()->create();
        $changes = $script->getChanges();
        ScriptCreated::dispatch($script, $changes);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptCreated', 'created_at');
    }

    /**
     * This test Script Deleted
     */
    public function testScriptDeleted()
    {
        $script = Script::factory()->create();
        $script->delete();
        ScriptDeleted::dispatch($script);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptDeleted', 'deleted_at');
    }

    /**
     * This test Script Duplicated
     */
    public function testScriptDuplicated()
    {
        $script = Script::factory()->create();
        $changes = $script->getChanges();
        ScriptDuplicated::dispatch($script, $changes);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptDuplicated', 'created_at');
    }

    /**
     * This test Script Updated
     */
    public function testScriptUpdated()
    {
        $script = Script::factory()->create();
        $original = array_intersect_key($script->getOriginal(), $script->getDirty());
        $script->fill(['description' => 'screen description']);
        $script->saveOrFail();
        $changes = $script->getChanges();
        ScriptUpdated::dispatch($script, $changes, $original);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptUpdated', 'last_modified');
    }

    /**
     * This test Script Executor Created
     */
    public function testScriptExecutorCreated()
    {
        $fields = [
            'id' => 999,
            'uuid' => '999fa7a8-893c-4eaf-test-389777b979a3',
            'title' => 'scriptexecutors_1',
            'description' => 'description_1',
            'language' => 'php',
        ];
        $scriptExecutor = ScriptExecutor::create($fields);
        ScriptExecutorCreated::dispatch($scriptExecutor->getAttributes());
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptExecutorCreated', 'created_at');
    }

    /**
     * This test Script Executor Deleted
     */
    public function testScriptExecutorDeleted()
    {
        $fields = [
            'id' => 99999,
            'title' => 'scriptexecutors_3',
            'description' => 'description_3',
            'language' => 'php',
        ];
        $scriptExecutor = ScriptExecutor::create($fields);
        ScriptExecutor::destroy($scriptExecutor->id);
        ScriptExecutorDeleted::dispatch($scriptExecutor->getAttributes());
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptExecutorDeleted', 'deleted_at');
    }

    /**
     * This test Script Executor Updated
     */
    public function testScriptExecutorUpdated()
    {
        $fields = [
            'id' => 9999,
            'uuid' => '999fa7a8-test-test-test-389777b979a3',
            'title' => 'scriptexecutors_2',
            'description' => 'description_2',
            'language ' => 'php',
        ];
        $scriptExecutor = ScriptExecutor::create($fields);
        $original = $scriptExecutor->getAttributes();
        $scriptExecutor->update(['description' => 'scriptexecutors description']);
        ScriptExecutorUpdated::dispatch($scriptExecutor->id, $original, $scriptExecutor->getChanges());
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('ScriptExecutorUpdated', 'last_modified');
    }

    /**
     * This test Signal Created
     */
    public function testSignalCreated()
    {
        $this->addGlobalSignalProcess();
        $fields = [
            'id' => 'id test',
            'name' => 'name test',
            'detail' => 'detail test',
        ];
        $signal = new SignalData($fields['id'], $fields['name'], $fields['detail']);
        SignalManager::addSignal($signal, $fields['detail']);
        SignalCreated::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('SignalCreated', 'created_at');
    }

    /**
     * This test Signal Deleted
     */
    public function testSignalDeleted()
    {
        $this->addGlobalSignalProcess();
        $fields = [
            'id' => 'id test',
            'name' => 'name test',
            'detail' => 'detail test',
        ];
        $signal = new SignalData($fields['id'], $fields['name'], $fields['detail']);
        SignalManager::addSignal($signal, $fields['detail']);
        SignalManager::removeSignal($signal);
        SignalDeleted::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('SignalDeleted', 'deleted_at');
    }

    /**
     * This test Signal Updated
     */
    public function testSignalUpdated()
    {
        $this->addGlobalSignalProcess();
        $oldSignal = [
            'id' => 'id test',
            'name' => 'name test',
            'detail' => 'detail test',
        ];
        $signal = new SignalData($oldSignal['id'], $oldSignal['name'], $oldSignal['detail']);
        SignalManager::addSignal($signal, $oldSignal['detail']);
        $oldSignal = SignalManager::findSignal($oldSignal['id']);
        $newSignal = [
            'id' => 'id test_1',
            'name' => 'name test_1',
            'detail' => 'detail test_1',
        ];
        $newSignal = new SignalData($newSignal['id'], $newSignal['name'], $newSignal['detail']);
        SignalManager::replaceSignal($newSignal, $oldSignal, 'detail test_1');
        SignalUpdated::dispatch(
            [
                'id' => $newSignal->getId() ?? '',
                'name' => $newSignal->getName() ?? '',
                'detail' => $newSignal->getDetail() ?? '',
            ],
            [
                'id' => $oldSignal->getId() ?? '',
                'name' => $oldSignal->getName() ?? '',
                'detail' => $oldSignal->getDetail() ?? '',
            ]
        );
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('SignalUpdated', 'last_modified');
    }

    /**
     * This test Template Created
     */
    public function testTemplateCreated()
    {
        $this->addGlobalSignalProcess();
        $fields = [
            'name' => 'template_1',
            'description' => 'description_1',
        ];
        ProcessTemplates::factory()->create($fields);
        TemplateCreated::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TemplateCreated', 'created_at');
    }

    /**
     * This test Template Deleted
     */
    public function testTemplateDeleted()
    {
        $this->addGlobalSignalProcess();
        $templates = ProcessTemplates::factory()->create();
        TemplateDeleted::dispatch($templates);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TemplateDeleted', 'deleted_at');
    }

    /**
     * This test Template Published
     */
    public function testTemplatePublished()
    {
        $this->addGlobalSignalProcess();
        $fields = [
            'name' => 'template_1',
            'description' => 'description_1',
        ];
        ProcessTemplates::factory()->create($fields);
        TemplatePublished::dispatch($fields);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TemplatePublished', 'created_at');
    }

    /**
     * This test Template Updated
     */
    public function testTemplateUpdated()
    {
        $this->addGlobalSignalProcess();
        // When the template is a process
        $process = Process::factory()->create([
            'is_template' => 1
        ]);
        TemplateUpdated::dispatch([], [], true, $process);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TemplateUpdated', 'last_modified');
        // When the template is a process
        $process = ProcessTemplates::factory()->create();
        TemplateUpdated::dispatch([], [], true, $process);
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TemplateUpdated', 'last_modified', 2);
    }

    /**
     * This test Token Created
     */
    public function testTokenCreated()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => true
        ]);
        $token = $user->createToken('API Token');
        TokenCreated::dispatch($token->token, $user, 'API token');
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TokenCreated', 'created_at', 1);
    }

    /**
     * This test Token Deleted
     */
    public function testTokenDeleted()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => true
        ]);
        $token = $user->createToken('API Token');
        TokenDeleted::dispatch($token->token, $user, 'API token');
        // Review the asserts about the response of Security Log
        $this->checkAssertsSegurityLog('TokenDeleted', 'deleted_at', 1);
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
