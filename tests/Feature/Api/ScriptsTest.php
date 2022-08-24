<?php

namespace Tests\Feature\Api;

use Database\Seeders\PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;

use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ScriptVersion;
use ProcessMaker\Models\User;
use ProcessMaker\PolicyExtension;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

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
        'description',
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
        $user = User::factory()->create(['is_administrator' => true]);
        $category = ScriptCategory::factory()->create(['status' => 'ACTIVE']);

        //Post saved correctly
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'script_executor_id' => ScriptExecutor::initialExecutor('php')->id,
            'code' => '123',
            'description' => 'Description',
            'script_category_id' => $category->getkey(),
            'run_as_user_id' => $user->id,
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

        $err = function ($response) {
            return $response->json()['errors']['script_category_id'][0];
        };

        $params['script_category_id'] = '';
        $response = $this->apiCall('POST', $url, $params);
        $this->assertEquals('The Script category id field is required.', $err($response));

        $category1 = ScriptCategory::factory()->create(['status' => 'ACTIVE']);
        $category2 = ScriptCategory::factory()->create(['status' => 'ACTIVE']);

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
        $script = Script::factory()->create([
            'title' => 'Script Title',
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'language' => 'php',
            'code' => $faker->sentence($faker->randomDigitNotNull),
            'script_category_id' => $script->script_category_id,
        ]);
        $response->assertStatus(422);
        $response->assertSeeText('The Name has already been taken');
    }

    /**
     * Can not create a script with an existing key
     */
    public function testNotCreateScriptWithKeyExists()
    {
        $script = Script::factory()->create([
            'key' => 'some-key',
        ]);

        $response = $this->apiCall('POST', self::API_TEST_SCRIPT, [
            'title' => 'Script Title',
            'key' => 'some-key',
            'code' => '123',
            'language' => 'php',
            'script_category_id' => $script->script_category_id,
        ]);
        $response->assertStatus(422);
        $response->assertSeeText('The Key has already been taken');
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
        Script::factory()->count($total)->create([
            'code' => $faker->sentence($faker->randomDigitNotNull),
        ]);

        // Create script with a key set. These should NOT be in the results.
        Script::factory()->create([
            'key' => 'some-key',
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
        $newEntity = Script::factory()->create(['title' => $name]);

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
        Script::factory()->create([
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
        $script = Script::factory()->create();

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

        $script = Script::factory()->create(['code' => $faker->sentence(50)])->id;

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
        $user = User::factory()->create(['is_administrator' => true]);

        //Post saved success
        $yesterday = \Carbon\Carbon::now()->subDay();
        $script = Script::factory()->create([
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
            'script_category_id' => $script->script_category_id,
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
        $script1 = Script::factory()->create([
            'title' => 'Some title',
        ]);

        $script2 = Script::factory()->create();

        $url = self::API_TEST_SCRIPT . '/' . $script2->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => 'Some title',
        ]);
        //Validate the answer is correct
        $response->assertStatus(422);
        $response->assertSeeText('The Name has already been taken');
    }

    /**
     * Copy Script
     */
    public function testDuplicateScript()
    {
        $faker = Faker::create();
        $user = User::factory()->create(['is_administrator' => true]);

        $code = '{"foo":"bar"}';
        $script = Script::factory()->create([
            'code' => $code,
        ]);
        $url = self::API_TEST_SCRIPT . '/' . $script->id;
        $response = $this->apiCall('PUT', $url . '/duplicate', [
            'title' => 'TITLE',
            'language' => 'php',
            'description' => $faker->sentence(5),
            'run_as_user_id' => $user->id,
            'script_category_id' => $script->script_category_id,
        ]);
        $new_script = Script::find($response->json()['id']);
        $this->assertEquals($code, $new_script->code);
    }

    /**
     * Test the preview function
     */
    public function testPreviewScript()
    {
        Event::fake([
            ScriptResponseEvent::class,
        ]);

        $url = route('api.scripts.preview', $this->getScript('lua')->id);
        $response = $this->apiCall('POST', $url, ['data' => '{}', 'code' => 'return {response=1}']);
        $response->assertStatus(200);
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;
            $nonce = $event->nonce;

            return $response['output'] === ['response' => 1];
        });

        $url = route('api.scripts.preview', $this->getScript('php')->id);
        $response = $this->apiCall('POST', $url, [
            'data' => '{}',
            'code' => '<?php return ["response"=>1];',
            'nonce' => '123abc',
        ]);
        $response->assertStatus(200);

        // Assertion: The script output is sent to usr through broadcast channel
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;
            $nonce = $event->nonce;

            return $response['output'] === ['response' => 1] && $nonce === '123abc';
        });
    }

    /**
     * Test the preview function
     */
    public function testPreviewScriptFail()
    {
        Event::fake([
            ScriptResponseEvent::class,
        ]);
        $script = $this->getScript('php');
        // manually override language
        $script->language = 'foo';
        $script->script_executor_id = 123;
        $script->saveOrFail();

        $url = route('api.scripts.preview', $script->id);
        $response = $this->apiCall('POST', $url, ['data' => 'foo', 'config' => 'foo', 'code' => 'foo']);

        // Assertion: An exception is notified to usr through broadcast channel
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;
            $nonce = $event->nonce;

            return $response['exception'] === ScriptLanguageNotSupported::class;
        });
    }

    /**
     * Delete script in process
     */
    public function testDeleteScript()
    {
        //Remove script
        $url = self::API_TEST_SCRIPT . '/' . Script::factory()->create()->id;
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
        $url = self::API_TEST_SCRIPT . '/' . Script::factory()->make()->id;
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
        $url = self::API_TEST_SCRIPT . '/' . Script::factory()->create([
            'code' => $code,
        ])->id;
        $response = $this->apiCall('PUT', $url . '/duplicate', [
            'title' => 'TITLE',
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
        return Script::factory()->create([
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
        $category = ScriptCategory::factory()->create([
            'name' => $name,
            'status' => 'active',
        ]);

        Script::factory()->create([
            'script_category_id' => $category->getKey(),
            'status' => 'active',
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

    public function testUpdateScriptCategories()
    {
        $screen = Script::factory()->create();
        $url = route('api.scripts.update', $screen);
        $params = [
            'title' => 'Title Script',
            'language' => 'php',
            'description' => 'Description.',
            'run_as_user_id' => User::factory()->create(['status' => 'ACTIVE', 'is_administrator' => true])->getKey(),
            'script_category_id' => ScriptCategory::factory()->create()->getKey() . ',' . ScriptCategory::factory()->create()->getKey(),
        ];
        $response = $this->apiCall('PUT', $url, $params);
        $response->assertStatus(204);
    }

    public function testExecutePolicy()
    {
        (new PermissionSeeder)->run();
        $asp = new AuthServiceProvider(app());
        $asp->boot();
        $this->user = User::factory()->create();
        $this->user->giveDirectPermission('view-scripts');
        app()->instance(PolicyExtension::class, null); // clear in case packages are installed in test context

        ImportProcess::dispatchNow(
            file_get_contents(__DIR__ . '/../../Fixtures/process_with_script_watcher.json')
        );
        $process = Process::orderBy('id', 'desc')->first();
        $script = Script::orderBy('id', 'desc')->first();

        WorkflowManager::triggerStartEvent(
            $process,
            $process->getDefinitions()->getEvent('node_1'),
            []
        );

        $task = ProcessRequestToken::orderBy('id', 'desc')->first();

        $url = route('api.scripts.execute', [$script]);
        $response = $this->apiCall('post', $url);
        $response->assertStatus(200);

        app(PolicyExtension::class)->add('execute', Script::class, function ($user, $script) {
            return false;
        });

        $response = $this->apiCall('post', $url);
        $response->assertStatus(403);
    }

    public function testExecuteVersion()
    {
        $this->markTestSkipped('Skip version locking for now');

        Event::fake([
            ScriptResponseEvent::class,
        ]);

        $script = Script::factory()->create([
            'run_as_user_id' => $this->user->id,
            'language' => 'php',
            'code' => '<?php return["version" => "original"];',
        ]);
        $task = ProcessRequestToken::factory()->create();

        Carbon::setTestNow(Carbon::now()->addMinute(1));
        $script->update(['code' => '<?php return["version" => "new"];']);

        $url = route('api.scripts.execute', [$script, 'task_id' => $task->id]);
        $response = $this->apiCall('post', $url);
        $response->assertStatus(200);

        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;

            return $response['output']['version'] === 'original';
        });

        Carbon::setTestNow();
    }
}
