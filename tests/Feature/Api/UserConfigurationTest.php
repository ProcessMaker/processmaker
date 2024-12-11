<?php

namespace Tests\Feature\Api;

use ProcessMaker\Http\Controllers\Api\UserConfigurationController;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserConfigurationTest extends TestCase
{
    use RequestHelper;

    const API_CONFIGURATION_URL = '/users/configuration';

    const API_FILTERS_URL = '/users/filters';

    const STRUCTURE = [
        'user_id',
        'ui_configuration',
        'ui_filters',
    ];

    /**
     * Test get deafult user configuration
     */
    public function testGetDefaultUserConfiguration()
    {
        // Call the api GET
        $response = $this->apiCall('GET', self::API_CONFIGURATION_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        // Verify structure
        $response->assertJsonStructure(self::STRUCTURE);
        // Verify default values for ui_configuration
        $defaultValues = json_encode(UserConfigurationController::DEFAULT_USER_CONFIGURATION);
        $this->assertEquals($response->json()['ui_configuration'], $defaultValues);
        // Verify default values for ui_filters
        $defaultValues = json_encode(UserConfigurationController::DEFAULT_USER_FILTER);
        $this->assertEquals($response->json()['ui_filters'], $defaultValues);
    }

    /**
     * Test store user configuration and get the new values
     */
    public function testStoreUserConfigurationAndGetNewValues()
    {
        // Call the api PUT
        $values = [
            'launchpad' => [
                'isMenuCollapse' => false,
            ],
            'cases' => [
                'isMenuCollapse' => false,
            ],
            'requests' => [
                'isMenuCollapse' => false,
            ],
            'tasks' => [
                'isMenuCollapse' => false,
            ],
        ];

        $response = $this->apiCall('PUT', self::API_CONFIGURATION_URL, ['ui_configuration' => $values]);
        // Validate the header status code
        $response->assertStatus(200);
        // Call the api GET
        $response = $this->apiCall('GET', self::API_CONFIGURATION_URL);
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
     * Test store user configuration and get the new values
     */
    public function testStoreUserFiltersAndGetNewValues()
    {
        // Call the api PUT
        $values = [
            'cases' => [
                'filters' => [
                    'subject' => [
                        'type' => 'Field',
                        'value' => 'case_number',
                    ],
                    'operator' => '=',
                    'value' => '885',
                ],
            ],
        ];

        $response = $this->apiCall('PUT', self::API_FILTERS_URL, ['ui_filters' => $values]);
        // Validate the header status code
        $response->assertStatus(200);
        // Call the api GET
        $response = $this->apiCall('GET', self::API_CONFIGURATION_URL);
        // Validate the header status code
        $response->assertStatus(200);
        $this->assertNotEmpty($response);
        // Verify structure
        $response->assertJsonStructure(self::STRUCTURE);
        // Verify default values
        $uiConfig = json_decode($response->json()['ui_filters']);
        $this->assertEquals($uiConfig->cases->filters->value, $values['cases']['filters']['value']);
        $this->assertEquals($uiConfig->cases->filters->operator, $values['cases']['filters']['operator']);
        $this->assertEquals($uiConfig->cases->filters->subject->type, $values['cases']['filters']['subject']['type']);
        $this->assertEquals($uiConfig->cases->filters->subject->value, $values['cases']['filters']['subject']['value']);
    }

    /**
     * Test store user configuration with invalid values
     */
    public function testStoreUserConfigurationWithInvalidValues()
    {
        // With no values
        $response = $this->apiCall('PUT', self::API_CONFIGURATION_URL);

        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui configuration field is required. (and 4 more errors)', $response->json()['message']);

        // An incomplete ui_configuration
        $values = [
            'cases' => [
                'isMenuCollapse' => false,
            ],
            'requests' => [
                'isMenuCollapse' => false,
            ],
            'tasks' => [
                'isMenuCollapse' => false,
            ],
        ];
        $response = $this->apiCall('PUT', self::API_CONFIGURATION_URL, ['ui_configuration' => $values]);
        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui configuration.launchpad field is required.', $response->json()['message']);
    }

    /**
     * Test store user configuration with invalid values
     */
    public function testStoreUserFiltersWithInvalidValues()
    {
        // With no values
        $response = $this->apiCall('PUT', self::API_FILTERS_URL);

        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui filters field is required. (and 1 more error)', $response->json()['message']);

        // An incomplete ui_configuration
        $values = [
            'otherlist' => [
                'filters' => [
                    'subject' => [
                        'type' => 'Field',
                        'value' => 'case_number',
                    ],
                    'operator' => '=',
                    'value' => '885',
                ],
            ],
        ];
        $response = $this->apiCall('PUT', self::API_FILTERS_URL, ['ui_filters' => $values]);
        // Validate the header status code
        $response->assertStatus(422);
        $this->assertEquals('The Ui filters.cases field is required.', $response->json()['message']);
    }
}
