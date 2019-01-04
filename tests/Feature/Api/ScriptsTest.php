<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class ScriptsTest extends TestCase
{
    use RequestHelper;

    const API_TEST_SCRIPT = '/scripts';

    const STRUCTURE = [
        'id',
        'title',
        'language',
        'code',
        'description'
    ];

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
        //Post saved correctly
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'language' => 'php',
            'code' => '123',
            'description' => 'Description'
        ]);
        //validating the answer is correct.
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create a script with an existing title
     */
    public function testNotCreateScriptWithTitleExists()
    {
        factory(Script::class)->create([
            'title' => 'Script Title',
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, [
            'title' => 'Script Title',
            'language' => 'php',
            'code' => $faker->sentence($faker->randomDigitNotNull)
        ]);
        $response->assertStatus(422);
        $response->assertSeeText('The title has already been taken');
    }

    /**
     * Can not create a script with an existing key
     */
    public function testNotCreateScriptWithKeyExists()
    {
        factory(Script::class)->create([
            'key' => 'some-key',
        ]);

        $response = $this->apiCall('POST', self::API_TEST_SCRIPT, [
            'title' => 'Script Title',
            'key' => 'some-key',
            'code' => '123',
            'language' => 'php',
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
        //Post saved success
        $yesterday = \Carbon\Carbon::now()->subDay();
        $script = factory(Script::class)->create([
            "created_at" => $yesterday,
        ]);
        $original_attributes = $script->getAttributes();
        $url = self::API_TEST_SCRIPT . '/' . $script->id;
        $response = $this->apiCall('PUT', $url, [
            'title' => $script->title,
            'language' => 'lua',
            'code' => $faker->sentence(3),
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
        $this->assertEquals((string)$version->created_at, (string)$yesterday);
        $this->assertEquals($version->updated_at, $script->updated_at);
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
        $response->assertSeeText('The title has already been taken');
    }

    /**
     * Test the preview function
     */
    public function testPreviewScript()
    {
        if (!file_exists(config('app.bpm_scripts_home')) || !file_exists(config('app.bpm_scripts_docker'))) {
            $this->markTestSkipped(
                'This test requires docker'
            );
        }
        $url = route('api.script.preview', ['data' => '{}', 'code' => 'return {response=1}', 'language' => 'lua']);
        $response = $this->apiCall('POST', $url, []);
        $response->assertStatus(200);

        $url = route('api.script.preview', ['data' => '{}', 'code' => '<?php return ["response"=>1];', 'language' => 'php']);
        $response = $this->apiCall('POST', $url, []);
        $response->assertStatus(200);

        $response->assertJsonStructure(['output' => ['response']]);

    }

    /**
     * Test the preview function
     */
    public function testPreviewScriptFail()
    {
        $url = self::API_TEST_SCRIPT . '/preview/?data=adkasdlasj&config=&code=adkasdlasj&language=JAVA';
        $response = $this->apiCall('POST', $url, []);
        $response->assertStatus(500);
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
}
