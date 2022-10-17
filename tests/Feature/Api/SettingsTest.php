<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Setting;
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
