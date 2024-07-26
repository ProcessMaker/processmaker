<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenTest extends TestCase
{
    use RequestHelper;

    const API_TEST_SCREEN = '/screens';

    const STRUCTURE = [
        'title',
        'description',
        'screen_category_id',
        'config',
    ];

    /**
     * Test verify the parameter required for create screen
     */
    public function testNotCreatedForParameterRequired()
    {
        // Post should have the parameter required
        $url = self::API_TEST_SCREEN;
        $response = $this->apiCall('POST', $url, []);

        // validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create Form screen successfully
     */
    public function testCreateFormScreen()
    {
        // Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCREEN;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Title Screen',
            'type' => 'FORM',
            'description' => $faker->sentence(10),
            'screen_category_id' => ScreenCategory::factory()->create()->id,
        ]);
        $response->assertStatus(201);
    }

    /**
     * Create Form screen successfully
     */
    public function testCreateDisplayScreen()
    {
        // Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCREEN;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Title Screen',
            'type' => 'DISPLAY',
            'description' => $faker->sentence(10),
            'screen_category_id' => ScreenCategory::factory()->create()->id,
        ]);

        $response->assertStatus(201);
    }

    /**
     * Can not create a screen with an existing title
     */
    public function testNotCreateScreenWithTitleExists()
    {
        Screen::factory()->create([
            'title' => 'Title Screen',
        ]);

        // Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCREEN;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Title Screen',
            'description' => $faker->sentence(10),
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Screen in process without query parameters.
     */
    public function testListScreen()
    {
        Screen::query()->delete();
        // add Screen to process
        $faker = Faker::create();
        Screen::factory()->count(10)->create();

        // List Screen
        $url = self::API_TEST_SCREEN;
        $response = $this->apiCall('GET', $url);
        // Validate the answer is correct
        $response->assertStatus(200);
        // verify count of data
        $json = $response->json();
        $this->assertEquals(10, $json['meta']['total']);

        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        $this->assertArrayNotHasKey('links', $json);

        // Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testScreenListDates()
    {
        $title = 'testScreenTimezone';
        $newEntity = Screen::factory()->create(['title' => $title]);
        $route = self::API_TEST_SCREEN . '?filter=' . $title;
        $response = $this->apiCall('GET', $route);

        $this->assertEquals(
            $newEntity->created_at->format('c'),
            $response->getData()->data[0]->created_at
        );

        $this->assertEquals(
            $newEntity->updated_at->format('c'),
            $response->getData()->data[0]->updated_at
        );
    }

    /**
     * Get a list of Screen with parameters
     */
    public function testListScreenWithQueryParameter()
    {
        $title = 'search Title Screen';
        Screen::factory()->create([
            'title' => $title,
        ]);

        // List Screen with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_SCREEN . $query;
        $response = $this->apiCall('GET', $url);
        // Validate the answer is correct
        $response->assertStatus(200);
        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        // verify response in meta
        $this->assertEquals(1, $json['meta']['total']);
        $this->assertEquals($perPage, $json['meta']['per_page']);
        $this->assertEquals(1, $json['meta']['current_page']);

        $this->assertEquals($title, $json['meta']['filter']);
        $this->assertEquals('description', $json['meta']['sort_by']);
        $this->assertEquals('DESC', $json['meta']['sort_order']);
        // verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
    }

    /**
     * Get a Screen of a process.
     */
    public function testGetScreen()
    {
        // load Screen
        $url = self::API_TEST_SCREEN . '/' . Screen::factory()->create([
            'config' => [
                'field' => 'field 1',
                'field 2' => [
                    'data1' => 'text',
                    'data2' => 'text 2',
                ],
            ],
        ])->id;
        $response = $this->apiCall('GET', $url);
        // Validate the answer is correct
        $response->assertStatus(200);

        // verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Update Screen parameter are required
     */
    public function testUpdateScreenParametersRequired()
    {
        // Post should have the parameter title
        $url = self::API_TEST_SCREEN . '/' . Screen::factory()->create()->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => '',
            'description' => '',
        ]);
        // Validate the answer is incorrect
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Update Screen in process successfully
     */
    public function testUpdateScreen()
    {
        $faker = Faker::create();
        $yesterday = now()->yesterday();
        $screen = Screen::factory()->create([
            'created_at' => $yesterday,
            'type' => 'FORM',
        ]);
        $originalAttributes = $screen->getAttributes();
        $url = self::API_TEST_SCREEN . '/' . $screen->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => 'ScreenTitleTest',
            'description' => $faker->sentence(5),
            'config' => '{"foo":"bar"}',
            'type' => $screen->type,
            'screen_category_id' => $screen->screen_category_id,
        ]);

        // Validate the answer is correct.
        $response->assertStatus(204);

        // assert it creates a published screen version.
        $screen->refresh();
        $version = $screen->versions()->published()->first();
        $this->assertEquals($version->screen_category_id, $screen->screen_category_id);
        $this->assertEquals($version->title, $originalAttributes['title']);
        $this->assertEquals($version->description, $originalAttributes['description']);
        $this->assertEquals($version->config, null);
        $this->assertLessThan(3, $version->updated_at->diffInSeconds($screen->updated_at));
    }

    /**
     * Update draft Screen.
     */
    public function testUpdateDraftScreen()
    {
        $faker = Faker::create();
        $yesterday = now()->yesterday();
        $screen = Screen::factory()->create([
            'created_at' => $yesterday,
            'type' => 'FORM',
        ]);
        $url = self::API_TEST_SCREEN . "/{$screen->id}/draft";
        $response = $this->apiCall('PUT', $url, [
            'title' => 'ScreenTitleTest',
            'description' => $faker->sentence(5),
            'config' => '{"foo":"bar"}',
            'computed' => '[{"id": 2, "name": "test", "type": "expression", "formula": "test", "property": "test"}]',
            'custom_css' => '.class { font-size: large; }',
            'watchers' => '[{"input_data":"{}","script_configuration":"{}","synchronous":false,"show_async_loading":false,"run_onload":false,"name":"Watchers","watching":"form_input_1","script":{"id":"script-4","uuid":"98a4376e-efb8-4c10-83fd-01199398eddc","key":null,"title":"Autosave","description":"Autosave","language":"php","code":"","timeout":60,"run_as_user_id":1,"status":"ACTIVE","script_category_id":"1","script_executor_id":3},"script_id":"4","script_key":null,"uid":"16794186944621"}]',
            'type' => $screen->type,
            'screen_category_id' => $screen->screen_category_id,
        ]);

        // Validate the answer is correct.
        $response->assertStatus(204);

        // assert it creates a published screen version.
        $screen->refresh();
        $draft = $screen->versions()->draft()->first();
        $this->assertEquals($draft->config, '{"foo":"bar"}');
        $this->assertEquals($draft->computed, '[{"id": 2, "name": "test", "type": "expression", "formula": "test", "property": "test"}]');
        $this->assertEquals($draft->custom_css, '.class { font-size: large; }');
        $this->assertEquals($draft->watchers, '[{"input_data":"{}","script_configuration":"{}","synchronous":false,"show_async_loading":false,"run_onload":false,"name":"Watchers","watching":"form_input_1","script":{"id":"script-4","uuid":"98a4376e-efb8-4c10-83fd-01199398eddc","key":null,"title":"Autosave","description":"Autosave","language":"php","code":"","timeout":60,"run_as_user_id":1,"status":"ACTIVE","script_category_id":"1","script_executor_id":3},"script_id":"4","script_key":null,"uid":"16794186944621"}]');
        $this->assertLessThan(3, $draft->updated_at->diffInSeconds($screen->updated_at));
    }

    /**
     * Close draft screen.
     */
    public function testCloseDraftScreen()
    {
        $screen = Screen::factory()->create();

        // Create a draft.
        $url = self::API_TEST_SCREEN . "/{$screen->id}/draft";
        $response = $this->apiCall('PUT', $url, [
            'title' => 'ScreenTitleTest',
            'config' => '{"foo":"bar"}',
        ]);

        // Close the draft.
        $response = $this->apiCall('POST', route('api.screens.close', ['screen' => $screen->id]));
        $response->assertStatus(204);
        $this->assertTrue($screen->versions()->draft()->doesntExist());
    }

    /**
     * Update Screen Type
     */
    public function testUpdateScreenType()
    {
        $faker = Faker::create();
        $type = 'FORM';
        $screen = Screen::factory()->create([
            'type' => $type,
        ]);
        $url = self::API_TEST_SCREEN . '/' . $screen->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => 'ScreenTitleTest',
            'type' => 'DETAIL',
            'description' => $faker->sentence(5),
            'config' => '',
            'screen_category_id' => $screen->screen_category_id,
        ]);
        $response->assertStatus(204);
    }

    /**
     * Copy Screen
     */
    public function testDuplicateScreen()
    {
        $faker = Faker::create();
        $config = '{"foo":"bar"}';
        $screen = Screen::factory()->create([
            'config' => $config,
        ]);
        $url = self::API_TEST_SCREEN . '/' . $screen->id . '/duplicate';
        $response = $this->apiCall('PUT', $url, [
            'title' => 'TITLE',
            'type' => 'FORM',
            'description' => $faker->sentence(5),
            'screen_category_id' => $screen->screen_category_id,
        ]);
        $new_screen = Screen::find($response->json()['id']);
        $this->assertEquals($config, $new_screen->config);
    }

    /**
     * Update Screen with same title
     */
    public function testUpdateSameTitleScreen()
    {
        // Post saved success
        $faker = Faker::create();
        $title = 'Some title';
        $screen = Screen::factory()->create([
            'title' => $title,
            'type' => 'DISPLAY',
        ]);
        $url = self::API_TEST_SCREEN . '/' . $screen->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => $title,
            'description' => $faker->sentence(5),
            'config' => '',
            'type' => 'DISPLAY',
            'screen_category_id' => $screen->screen_category_id,
        ]);
        // Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Screen in process
     */
    public function testDeleteScreen()
    {
        // Remove Screen
        $url = self::API_TEST_SCREEN . '/' . Screen::factory()->create()->id;
        $response = $this->apiCall('DELETE', $url);
        // Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Screen in process
     */
    public function testDeleteScreenNotExist()
    {
        // screen not exist
        $url = self::API_TEST_SCREEN . '/' . Screen::factory()->make()->id;
        $response = $this->apiCall('DELETE', $url);
        // Validate the answer is correct
        $response->assertStatus(405);
    }

    public function testCategoryNotRequiredIfAlreadySavedInDatabase()
    {
        $screen = Screen::factory()->create();
        $url = route('api.screens.update', $screen);
        $params = [
            'title' => 'Title Screen',
            'type' => 'FORM',
            'description' => 'Description.',
        ];
        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(204);
    }

    public function testCreateCategoryRequired()
    {
        $url = route('api.screens.store');
        $params = [
            'title' => 'Title Screen',
            'type' => 'FORM',
            'description' => 'Description.',
        ];

        $err = function ($response) {
            return $response->json()['errors']['screen_category_id'][0];
        };

        $params['screen_category_id'] = '';
        $response = $this->apiCall('POST', $url, $params);
        $this->assertEquals('The Screen category id field is required.', $err($response));

        $category1 = ScreenCategory::factory()->create(['status' => 'ACTIVE']);
        $category2 = ScreenCategory::factory()->create(['status' => 'ACTIVE']);

        $params['screen_category_id'] = $category1->id . ',foo';
        $response = $this->apiCall('POST', $url, $params);
        $this->assertEquals('Invalid category', $err($response));

        $params['screen_category_id'] = $category1->id . ',' . $category2->id;
        $response = $this->apiCall('POST', $url, $params);
        $response->assertStatus(201);

        $params['screen_category_id'] = $category1->id;
        $params['title'] = 'other title';
        $response = $this->apiCall('POST', $url, $params);
        $response->assertStatus(201);
    }

    /**
     * Get a list of Screen filter by category
     */
    public function testFilterByCategory()
    {
        $name = 'Search title Category Screen';
        $category = ScreenCategory::factory()->create([
            'name' => $name,
            'status' => 'active',
        ]);

        Screen::factory()->create([
            'screen_category_id' => $category->getKey(),
            'status' => 'active',
        ]);

        // List Screen with filter option
        $query = '?filter=' . urlencode($name);
        $url = self::API_TEST_SCREEN . $query;
        $response = $this->apiCall('GET', $url);
        // Validate the answer is correct
        $response->assertStatus(200);
        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        // verify response in meta
        $this->assertEquals(1, $json['meta']['total']);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals($name, $json['meta']['filter']);
        // verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);

        // List Screen without peers
        $name = 'Search category that does not exist';
        $query = '?filter=' . urlencode($name);
        $url = self::API_TEST_SCREEN . $query;
        $response = $this->apiCall('GET', $url);
        // Validate the answer is correct
        $response->assertStatus(200);
        // verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        // verify response in meta
        $this->assertEquals(0, $json['meta']['total']);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals($name, $json['meta']['filter']);
        // verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
    }

    public function testUpdateScreenCategories()
    {
        $screen = Screen::factory()->create();
        $url = route('api.screens.update', $screen);
        $params = [
            'title' => 'Title Screen',
            'type' => 'FORM',
            'description' => 'Description.',
            'screen_category_id' => ScreenCategory::factory()->create()->getKey() . ',' . ScreenCategory::factory()->create()->getKey(),
        ];
        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(204);
    }

    public function testWithUserWithoutAuthorization()
    {
        $screen = Screen::factory()->create();
        $url = route('api.screens.update', $screen);
        $params = [
            'title' => 'Title Screen',
            'type' => 'FORM',
            'description' => 'Description.',
            'screen_category_id' => ScreenCategory::factory()->create()->getKey() . ',' . ScreenCategory::factory()->create()->getKey(),
        ];

        // The call is done without an authenticated user so it should return 401
        $response = $this->actingAs(User::factory()->create())
            ->json('PUT', $url, $params);
        $response->assertStatus(401);
    }

    public function testPreviewScreen()
    {
        $this->markTestSkipped('Skip consolidated screen preview');
        $child = Screen::factory()->create([
            'config' => json_decode(
                file_get_contents(__DIR__ . '/../../Fixtures/simple_child_screen.json')
            ),
            'watchers' => [['name' => 'child1'], ['name' => 'child2']],
            'computed' => [['id' => 1, 'name' => 'c1'], ['id' => 2, 'name' => 'c2']],
            'custom_css' => 'foo',
        ]);

        $previewConfig = json_decode(
            file_get_contents(__DIR__ . '/../../Fixtures/simple_parent_screen.json'),
            true
        );
        $previewConfig[0]['items'][1]['config']['screen'] = $child->id;
        $preview = [
            'config' => $previewConfig,
            'watchers' => [['name' => 'parent1'], ['name' => 'parent2']],
            'computed' => [['id' => 1, 'name' => 'p1'], ['id' => 2, 'name' => 'p2']],
            'custom_css' => 'bar',
        ];

        // Post saved success
        $url = self::API_TEST_SCREEN . '/preview';
        $response = $this->apiCall('POST', $url, $preview);

        $screen = $response->json();

        $json = $response->json();
        $this->assertEquals('<p>Parent</p>', $json['config'][0]['items'][0]['config']['content']);
        $this->assertEquals('<p>Child</p>', $json['config'][0]['items'][1]['config']['content']);
        $this->assertEquals('child1', $json['watchers'][2]['name']);
        $this->assertEquals('c2', $json['computed'][3]['name']);
        $this->assertEquals("bar\nfoo", $json['custom_css']);
    }

    /**
     * Test get list of screens with categories but without config field
     */
    public function testListScreensWithCategoriesAndWithoutConfig()
    {
        $category = ScreenCategory::factory()->create();
        $screen1 = Screen::factory()->create([
            'screen_category_id' => $category->id,
        ]);
        $screen2 = Screen::factory()->create([
            'screen_category_id' => $category->id,
        ]);

        $url = self::API_TEST_SCREEN . '?exclude=config&order_by=id';
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(200);

        $json = $response->json();

        // Verify the IDs of screens and categories
        $this->assertEquals($category->id, $json['data'][0]['screen_category_id']);
        $this->assertEquals($category->id, $json['data'][1]['screen_category_id']);
        $this->assertEquals($screen1->id, $json['data'][0]['id']);
        $this->assertEquals($screen2->id, $json['data'][1]['id']);

        // Verify the response does not contain the confing field
        $this->assertArrayNotHasKey('config', $json['data'][0]);
        $this->assertArrayNotHasKey('config', $json['data'][1]);
    }

    /**
     * Test get list of screens with categories with config field
     */
    public function testListScreensWithCategoriesWithoContent()
    {
        $category = ScreenCategory::factory()->create();
        $screen1 = Screen::factory()->create([
            'screen_category_id' => $category->id,
        ]);
        $screen2 = Screen::factory()->create([
            'screen_category_id' => $category->id,
        ]);

        $url = self::API_TEST_SCREEN . '?order_by=id';
        $response = $this->apiCall('GET', $url);
        $response->assertStatus(200);

        $json = $response->json();

        // Verify the IDs of screens and categories
        $this->assertEquals($category->id, $json['data'][0]['screen_category_id']);
        $this->assertEquals($category->id, $json['data'][1]['screen_category_id']);
        $this->assertEquals($screen1->id, $json['data'][0]['id']);
        $this->assertEquals($screen2->id, $json['data'][1]['id']);

        // Verify the response must contain the config field
        $this->assertArrayHasKey('config', $json['data'][0]);
        $this->assertArrayHasKey('config', $json['data'][1]);
    }
}
