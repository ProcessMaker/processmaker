<?php

namespace Tests\Feature\Api;

use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\SettingsMenus;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use ResourceAssertionsTrait;
    use WithFaker;
    use RequestHelper;

    protected $resource = 'setting';

    protected $structure = [
        'config',
        'process_request_id',
        'user_id',
        'element_id',
        'element_type',
        'element_name',
        'status',
        'completed_at',
        'due_at',
        'initiated_at',
        'riskchanges_at',
        'updated_at',
        'created_at',
    ];

    /**
     * Test get settings menus
     */
    public function testGetSettingsMenus()
    {
        SettingsMenus::query()->delete();
        // Create
        SettingsMenus::factory()->create([
            'menu_group' => SettingsMenus::EMAIL_MENU_GROUP,
        ]);
        SettingsMenus::factory()->create([
            'menu_group' => SettingsMenus::LOG_IN_AUTH_MENU_GROUP,
        ]);
        SettingsMenus::factory()->create([
            'menu_group' => SettingsMenus::USER_SETTINGS_MENU_GROUP,
        ]);
        SettingsMenus::factory()->create([
            'menu_group' => SettingsMenus::INTEGRATIONS_MENU_GROUP,
        ]);
        $route = route('api.settings.menu_groups');
        $response = $this->apiCall('GET', $route);
        // Verify the status
        $response->assertStatus(200);
        $this->assertCount(4, $response['data']);
    }

    /**
     * Test get settings menus group related
     */
    public function testGetSettingsMenusGroup()
    {
        // Get setting menus
        $menus = SettingsMenus::factory()->create();
        Setting::factory()->create([
            'key' => 'test.properties',
            'name' => 'test',
            'format' => 'test',
            'group' => 'UserTest',
            'group_id' => $menus->id,
        ]);
        $route = route('api.settings.menu_groups');
        $response = $this->apiCall('GET', $route);
        // Verify the status
        $response->assertStatus(200);
        $this->assertNotEmpty($response['data']);
        $this->assertNotEmpty($response['data'][0]['groups']);
    }

    /**
     * Test update settings for specific group
     */
    public function testUpdateSettingsForSpecificGroup()
    {
        $menus = SettingsMenus::factory()->create();
        $group = 'Custom group';
        Setting::factory()->create([
            'key' => 'test.properties',
            'name' => 'test',
            'format' => 'test',
            'group' => $group,
            'group_id' => null,
        ]);

        // Update
        Setting::updateSettingsGroup($group, $menus->id);
        $matches = Setting::where('group', $group)->where('group_id', $menus->id)->get()->toArray();
        $this->assertNotEmpty($matches);
    }

    /**
     * Test extended properties variable valid name validation
     */
    public function testUpdateExtendedPropertiesWithValidVariableName()
    {
        $this->markTestSkipped('Not using validation in backend yet, because there are some config data that should not be validated as LDAP config...');

        $setting = Setting::factory()->create(['key' => 'users.properties']);
        $params = [
            // Test data different valid variable names
            'config' => [
                '_myVar' => 'This is my variable 1',
                'myVar' => 'This is my variable 2',
                'myVar1' => 'This is my variable 3',
            ],
            'key' => $setting->key,
            'id' => $setting->id,
        ];

        //Update setting config
        $route = route('api.settings.update', [$setting->id]);
        $response = $this->apiCall('PUT', $route, $params);
        //Verify the status
        $response->assertStatus(204);
        //Verify variables were updated
        $this->assertDatabaseHas('settings', ['config' => '{"_myVar":"This is my variable 1","myVar":"This is my variable 2","myVar1":"This is my variable 3"}']);
    }

    /**
     * Test extended properties variable invalid name validation
     */
    public function testUpdateExtendedPropertiesWithInvalidVariableName()
    {
        $this->markTestSkipped('Not using validation in backend yet, because there are some config data that should not be validated as LDAP config...');

        $setting = Setting::factory()->create(['key' => 'users.properties']);
        $params = [
            // Test data different valid variable names
            'config' => [
                '1myVar' => 'This is my variable 1',
                'myVar space' => 'This is my variable 2',
                'my-Var' => 'This is my variable 3',
            ],
            'key' => $setting->key,
            'id' => $setting->id,
        ];
        //Update setting config
        $route = route('api.settings.update', [$setting->id]);
        $response = $this->apiCall('PUT', $route, $params);
        //Verify the status
        $response->assertStatus(422);
        //Verify response error
        $response->assertJson(
            [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'config.1myVar' => [
                        'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
                    ],
                    'config.myVar space' => [
                        'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
                    ],
                    'config.my-Var' => [
                        'Name has to start with a letter and can contain only letters, numbers, and underscores (_).',
                    ],
                ],
            ]
        );
        //Verify variable were not updated
        $this->assertDatabaseMissing('settings', ['config' => '{"1myVar":"This is my variable 1","myVar space":"This is my variable 2"}']);
    }
}
