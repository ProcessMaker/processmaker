<?php

namespace Tests\Feature\Api;

use ProcessMaker\Http\Controllers\Api\UserConfigurationController;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserConfigurationTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/users/configuration';

    const STRUCTURE = [
        'user_id',
        'ui_configuration'
    ];

    /**
     * Test get deafult user configuration
     */
    public function testGetDefaultUserConfiguration()
    {
        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        // Verify structure
        $response->assertJsonStructure(self::STRUCTURE);
        // Verify default values
        $defaultValues = json_encode(UserConfigurationController::DEFAULT_USER_CONFIGURATION);
        $this->assertEquals($response->json()['ui_configuration'], $defaultValues);
    }

    /**
     * Test store user configuration and get the new values
     */
    public function testStoreUserConfigurationAndGetNewValues()
    {
        // Call the api PUT
        $values = [
            "launchpad" => [
               "isMenuCollapse" => false
            ],
            "cases" => [
               "isMenuCollapse" => false
            ],
            "requests" => [
               "isMenuCollapse" => false
            ],
            "tasks" => [
               "isMenuCollapse" => false
            ]
        ];

        $response = $this->apiCall('PUT', self::API_TEST_URL, ['ui_configuration' => $values]);
        // Validate the header status code
        $response->assertStatus(200);

        // Call the api GET
        $response = $this->apiCall('GET', self::API_TEST_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        // Verify structure
        $response->assertJsonStructure(self::STRUCTURE);
        // Verify default values
        $uiConfig = json_decode($response->json()['ui_configuration']);
        $this->assertEquals($uiConfig->launchpad->isMenuCollapse, $values['launchpad']['isMenuCollapse']);
        $this->assertEquals($uiConfig->cases->isMenuCollapse, $values['cases']['isMenuCollapse']);
        $this->assertEquals($uiConfig->requests->isMenuCollapse, $values['requests']['isMenuCollapse']);
        $this->assertEquals($uiConfig->tasks->isMenuCollapse, $values['tasks']['isMenuCollapse']);
    }

    /**
     * Test store user configuration with invalid values
     */
    public function testStoreUserConfigurationWithInvalidValues()
    {
        // With no values
        $response = $this->apiCall('PUT', self::API_TEST_URL);

        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui configuration field is required. (and 4 more errors)', $response->json()['message']);

        // An incomplete ui_configuration
        $values = [
            "cases" => [
               "isMenuCollapse" => false
            ],
            "requests" => [
               "isMenuCollapse" => false
            ],
            "tasks" => [
               "isMenuCollapse" => false
            ]
        ];
        $response = $this->apiCall('PUT', self::API_TEST_URL, ['ui_configuration' => $values]);
        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui configuration.launchpad field is required.', $response->json()['message']);
    }
}
