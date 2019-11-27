<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Notifications\ScriptResponseNotification;
use ProcessMaker\Exception\ScriptLanguageNotSupported;

class ScriptsTest extends TestCase
{
    use RequestHelper;

    const API_TEST_SCRIPT = '/scripts';

    const STRUCTURE = [
        'id',
        'title',
        'language',
        'code',
        'script_category_id',
        'description'
    ];

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Test verify the parameter required to create a script
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url);
        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new script in process
     */
    public function testCreateScript()
    {
        $faker = Faker::create();
        $user = factory(User::class)->create(['is_administrator' => true]);
        $category = factory(ScriptCategory::class)->create(['status' => 'ACTIVE']);

        //Post saved correctly
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'language' => 'php',
            'code' => '123',
            'description' => 'Description',
            'script_category_id' => $category->getkey(),
            'run_as_user_id' => $user->id
        ]);
        //validating the answer is correct.
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    public function testCreateCategoryRequired()
    {
        $url = route('api.scripts.store');
        $params = [
            'title' => 'Title',
            'language' => 'php',
            'code' => '123',
            'description' => 'Description',
            'run_as_user_id' => $this->user->id,
        ];

        $err = function($response) {
            return $response->json()['errors']['script_category_id'][0]; 
        };

        $params['script_category_id'] = '';
        $response = $this->apiCall('POST', $url, $params);
        $this->assertEquals('The script category id field is required.', $err($response));

        $category1 = factory(ScriptCategory::class)->create(['status' => 'ACTIVE']);
        $category2 = factory(ScriptCategory::class)->create(['status' => 'ACTIVE']);

        $params['script_category_id'] = $category1->id . ',foo';
        $response = $this->apiCall('POST', $url, $params);
        $this->assertEquals('Invalid category', $err($response));

        $params['script_category_id'] = $category1->id . ',' . $category2->id;
        $response = $this->apiCall('POST', $url, $params);
        $response->assertStatus(201);
        
        $params['script_category_id'] = $category1->id;
        $params['title'] = 'other title';
        $response = $this->apiCall('POST', $url, $params);
        $response->assertStatus(201);
    }

    /**
     * Can not create a script with an existing title
     */
    public function testNotCreateScriptWithTitleExists()
    {
        $script = factory(Script::class)->create([
            'title' => 'Script Title',
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'language' => 'php',
            'code' => $faker->sentence($faker->randomDigitNotNull),
            'script_category_id' => $script->script_category_id
        ]);
        $response->assertStatus(422);
        $response->assertSeeText('The name has already been taken');
    }

    /**
     * Can not create a script with an existing key
     */
    public function testNotCreateScriptWithKeyExists()
    {
        $script = factory(Script::class)->create([
            'key' => 'some-key',
        ]);

        $response = $this->apiCall('POST', self::API_TEST_SCRIPT, [
            'title' => 'Script Title',
            'key' => 'some-key',
            'code' => '123',
            'language' => 'php',
            'script_category_id' => $script->script_category_id
        ]);
        $response->assertStatus(422);
        $response->assertSeeText('The key has already been taken');
    }

    /**
     * Get a list of scripts in a project.
     */
    public function testListScripts()
    {
        //add scripts to process
        Script::query()->delete();
        $faker = Faker::create();
        $total = $faker->randomDigitNotNull;
        factory(Script::class, $total)->create([
            'code' => $faker->sentence($faker->randomDigitNotNull)
        ]);

        // Create script with a key set. These should NOT be in the results.
        factory(Script::class)->create([
            'key' => 'some-key'
        ]);

        //List scripts
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);

        //verify count of data
        $this->assertEquals($total, $response->json()['meta']['total']);
    }

    /**
     * Test to verify that the list dates are in the correct format (yyyy-mm-dd H:i+GMT)
     */
    public function testScriptListDates()
    {
        $name = 'tetScriptTimezone';
        $newEntity = factory(Script::class)->create(['title' => $name]);

        $route = self::API_TEST_SCRIPT . '?filter=' . $name;
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
     * Get a list of Scripts with parameters
     */
    public function testListScriptsWithQueryParameter()
    {
        $title = 'search script title';
        factory(Script::class)->create([
            'title' => $title,
        ]);

        //List Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_SCRIPT . $query;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data' => ['*' => self::STRUCTURE],
            'meta',
        ]);
        //verify response in meta
        $json = $response->json();
        $meta = $json['meta'];
        $this->assertEquals(1, $meta['total']);
        $this->assertEquals($perPage, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(1, $meta['last_page']);

        $this->assertEquals($title, $meta['filter']);
        $this->assertEquals('DESC', $meta['sort_order']);
    }

    /**
     * Get a script of a project.
     */
    public function testGetScript()
    {
        //add scripts to process
        $script = factory(Script::class)->create();

        //load script
        $url = self::API_TEST_SCRIPT . '/' . $script->id;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Parameters required for update of script
     */
    public function testUpdateScriptParametersRequired()
    {
        $faker = Faker::create();

        $script = factory(Script::class)->create(['code' => $faker->sentence(50)])->id;

        //The post must have the required parameters
        $url = self::API_TEST_SCRIPT . '/' . $script;

        $response = $this->apiCall('PUT', $url, [
            'title' => '',
            'language' => 'php',
            'code' => $faker->sentence(3),
        ]);

        //Validate the answer is incorrect
        $response->assertStatus(422);
    }

    /**
     * Update script in process
     */
    public function testUpdateScript()
    {
        $faker = Faker::create();
        $user = factory(User::class)->create(['is_administrator' => true]);

        //Post saved success
        $yesterday = \Carbon\Carbon::now()->subDay();
        $script = factory(Script::class)->create([
            'description' => 'ufufu',
            'created_at' => $yesterday,
        ]);
        $original_attributes = $script->getAttributes();

        $url = self::API_TEST_SCRIPT . '/' . $script->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => $script->title,
            'language' => 'lua',
            'description' => 'jdbsdfkj',
            'code' => $faker->sentence(3),
            'run_as_user_id' => $user->id,
            'script_category_id' => $script->script_category_id
        ]);

        //Validate the answer is correct
        $response->assertStatus(204);

        // assert it creates a script version
        $script->refresh();
        $version = $script->versions()->first();
        $this->assertEquals($version->key, $script->key);
        $this->assertEquals($version->title, $original_attributes['title']);
        $this->assertEquals($version->language, $original_attributes['language']);
        $this->assertEquals($version->code, $original_attributes['code']);
        $this->assertLessThan(3, $version->updated_at->diffInSeconds($script->updated_at));
    }

    /**
     * Update script in process with same title
     */
    public function testUpdateScriptTitleExists()
    {
        $script1 = factory(Script::class)->create([
            'title' => 'Some title',
        ]);

        $script2 = factory(Script::class)->create();

        $url = self::API_TEST_SCRIPT . '/' . $script2->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => 'Some title',
        ]);
        //Validate the answer is correct
        $response->assertStatus(422);
        $response->assertSeeText('The name has already been taken');
    }

    /**
     * Copy Script
     */
    public function testDuplicateScript()
    {
        $faker = Faker::create();
        $user = factory(User::class)->create(['is_administrator' => true]);

        $code = '{"foo":"bar"}';
        $script = factory(Script::class)->create([
            'code' => $code
        ]);
        $url = self::API_TEST_SCRIPT . '/' . $script->id;
        $response = $this->apiCall('PUT', $url . '/duplicate', [
            'title' => 'TITLE',
            'language' => 'php',
            'description' => $faker->sentence(5),
            'run_as_user_id' => $user->id,
            'script_category_id' => $script->script_category_id
        ]);
        $new_script = Script::find($response->json()['id']);
        $this->assertEquals($code, $new_script->code);
    }

    /**
     * Test the preview function
     */
    public function testPreviewScript()
    {
        Notification::fake();
        if (!file_exists(config('app.processmaker_scripts_home')) || !file_exists(config('app.processmaker_scripts_docker'))) {
            $this->markTestSkipped(
                'This test requires docker'
            );
        }

        $url = route('api.scripts.preview', $this->getScript('lua')->id);
        $response = $this->apiCall('POST', $url, ['data' => '{}', 'code' => 'return {response=1}']);
        $response->assertStatus(200);

        $url = route('api.scripts.preview', $this->getScript('php')->id);
        $response = $this->apiCall('POST', $url, ['data' => '{}', 'code' => '<?php return ["response"=>1];']);
        $response->assertStatus(200);

        // Assertion: The script output is sent to usr through broadcast channel
        Notification::assertSentTo(
            [$this->user],
            ScriptResponseNotification::class,
            function ($notification, $channels) {
                $response = $notification->getResponse();
                return $response['output'] === ['response' => 1];
            }
        );
    }

    /**
     * Test the preview function
     */
    public function testPreviewScriptFail()
    {
        Notification::fake();
        $url = route('api.scripts.preview', $this->getScript('foo')->id);
        $response = $this->apiCall('POST', $url, ['data' => 'foo', 'config' => 'foo', 'code' => 'foo']);

        // Assertion: An exception is notified to usr through broadcast channel
        Notification::assertSentTo(
            [$this->user],
            ScriptResponseNotification::class,
            function ($notification, $channels) {
                $response = $notification->getResponse();
                return $response['exception'] === ScriptLanguageNotSupported::class && in_array('broadcast', $channels);
            }
        );
    }

    /**
     * Delete script in process
     */
    public function testDeleteScript()
    {
        //Remove script
        $url = self::API_TEST_SCRIPT . '/' . factory(Script::class)->create()->id;
        $response = $this->apiCall('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * The script does not exist in process
     */
    public function testDeleteScriptNotExist()
    {
        //Script not exist
        $url = self::API_TEST_SCRIPT . '/' . factory(Script::class)->make()->id;
        $response = $this->apiCall('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(405);
    }

    /**
     * test that script without user to run as assigned generates an error
     */
    public function testScriptWithoutUser()
    {
        $faker = Faker::create();
        $code = '{"foo":"bar"}';
        $url = self::API_TEST_SCRIPT . '/' . factory(Script::class)->create([
            'code' => $code
        ])->id;
        $response = $this->apiCall('PUT', $url . '/duplicate', [
            'title' => "TITLE",
            'language' => 'php',
            'description' => $faker->sentence(5),
        ]);
        $response->assertStatus(422);
    }

    /**
     * A helper method to generate a script object from the factory
     *
     * @param string $language
     * @return Script
     */
    private function getScript($language)
    {
        return factory(Script::class)->create([
            'run_as_user_id' => $this->user->id,
            'language' => $language,
        ]);
    }

    /**
     * Get a list of Screen filter by category
     */
    public function testFilterByCategory()
    {
        $name = 'Search title Category Screen';
        $category = factory(ScriptCategory::class)->create([
            'name' => $name,
            'status' => 'active'
        ]);
 

        factory(Script::class)->create([
            'script_category_id' => $category->getKey(),
            'status' => 'active'
        ]);

        //List Screen with filter option
        $query = '?filter=' . urlencode($name);
        $url = self::API_TEST_SCRIPT . $query;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        //verify response in meta
        $this->assertEquals(1, $json['meta']['total']);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals($name, $json['meta']['filter']);
        //verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);

        
        //List Screen without peers
        $name = 'Search category that does not exist';
        $query = '?filter=' . urlencode($name);
        $url = self::API_TEST_SCRIPT . $query;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        //verify response in meta
        $this->assertEquals(0, $json['meta']['total']);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals($name, $json['meta']['filter']);
        //verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
    }
}
