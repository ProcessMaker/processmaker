<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\ApiCallWithUser;

class ScriptsTest extends TestCase
{
    use DatabaseTransactions;
    use ApiCallWithUser;

    const API_TEST_SCRIPT = '/api/1.0/scripts';
    const DEFAULT_PASS = 'password';

    const STRUCTURE = [
        'uuid',
        'title',
        'description',
        'language',
        'code'
    ];

    /**
     * Test verify the parameter required to create a script
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_SCRIPT;
        $response = $this->apiCall('POST', $url, []);
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
            'description' => $faker->sentence(6),
            'language' => 'PHP',
            'code' => '123',
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
            'description' => $faker->sentence(6),
            'code' => $faker->sentence($faker->randomDigitNotNull)
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
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
        $this->assertEquals('description', $meta['sort_by']);
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
        $url = self::API_TEST_SCRIPT . '/' . $script->uuid_text;
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

        $script = factory(Script::class)->create(['code' => $faker->sentence(50)])->uuid_text;

        //The post must have the required parameters
        $url = self::API_TEST_SCRIPT . '/' . $script;

        $response = $this->apiCall('PUT', $url, [
            'title' => '',
            'description' => $faker->sentence(6),
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
        $script = factory(Script::class)->create();
        $url = self::API_TEST_SCRIPT . '/' . $script->uuid_text;
        $response = $this->apiCall('PUT', $url, [
            'title' => $script->title,
            'description' => $faker->sentence(6),
            'language' => 'LUA',
            'code' => $faker->sentence(3),
        ]);
        //Validate the answer is correct
        $response->assertStatus(204);
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

        $url = self::API_TEST_SCRIPT . '/' . $script2->uuid_text;
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
        $url = self::API_TEST_SCRIPT.'/preview/?data=adkasdlasj&config=&code=adkasdlasj&language=LUA';
        $response = $this->apiCall('GET', $url, []);
        $response->assertStatus(200);

        $url = self::API_TEST_SCRIPT.'/preview/?data=adkasdlasj&config=&code=adkasdlasj&language=PHP';
        $response = $this->apiCall('GET', $url, []);
        $response->assertStatus(200);
    }

    /**
     * Delete script in process
     */
    public function testDeleteScript()
    {
        //Remove script
        $url = self::API_TEST_SCRIPT . '/' . factory(Script::class)->create()->uuid_text;
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
        $url = self::API_TEST_SCRIPT . '/' . factory(Script::class)->make()->uuid_text;
        $response = $this->apiCall('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(405);
    }
}
