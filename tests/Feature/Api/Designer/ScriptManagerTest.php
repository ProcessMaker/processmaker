<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Script;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ScriptManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_SCRIPT = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    protected $user;
    protected $process;

    const STRUCTURE = [
        'uid',
        'title',
        'description',
        'language',
        'code'
    ];

    /**
     * Create user and process
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $this->auth($this->user->username, self::DEFAULT_PASS);
    }

    /**
     * Test verify the parameter required to create a script
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script';
        $response = $this->api('POST', $url, []);

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
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script';
        $response = $this->api('POST', $url, [
            'title' => 'Script Title',
            'description' => $faker->sentence(6),
            'language' => 'php'
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
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
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script';
        $response = $this->api('POST', $url, [
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
        $faker = Faker::create();
        $total = $faker->randomDigitNotNull;
        factory(Script::class, $total)->create([
            'process_id' => $this->process->id,
            'code' => $faker->sentence($faker->randomDigitNotNull)
        ]);

        //List scripts
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/scripts';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //verify count of data
        $this->assertEquals($total, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a list of Scripts with parameters
     */
    public function testListScriptsWithQueryParameter()
    {
        $title = 'search script title';
        factory(Script::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/scripts?' . $query;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify response in meta
        $this->assertEquals(1, $response->original->meta->total);
        $this->assertEquals(1, $response->original->meta->count);
        $this->assertEquals($perPage, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertEquals(1, $response->original->meta->total_pages);
        $this->assertEquals($title, $response->original->meta->filter);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a script of a project.
     */
    public function testGetScript()
    {
        //add scripts to process
        $faker = Faker::create();

        $script = factory(Script::class)->create([
                'process_id' => $this->process->id,
                'code' => $faker->sentence($faker->randomDigitNotNull)
        ]);

        //load script
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . $script->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * The script not belongs to process.
     */
    public function testGetScriptNotBelongToProcess()
    {
        //load script
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . factory(Script::class)->create()->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Parameters required for update of script
     */
    public function testUpdateScriptParametersRequired()
    {
        $faker = Faker::create();
        //The post must have the required parameters
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . factory(Script::class)->create([
                'process_id' => $this->process->id,
                'code' => $faker->sentence($faker->randomDigitNotNull)
            ])->uid;
        $response = $this->api('PUT', $url, [
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
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . factory(Script::class)->create([
                'process_id' => $this->process->id,
                'code' => $faker->sentence($faker->randomDigitNotNull)
            ])->uid;
        $response = $this->api('PUT', $url, [
            'title' => $faker->sentence(2)
        ]);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete script in process
     */
    public function testDeleteScript()
    {
        //Remove script
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . factory(Script::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * The script does not exist in process
     */
    public function testDeleteScriptNotExist()
    {
        //Script not exist
        $url = self::API_TEST_SCRIPT . $this->process->uid . '/script/' . factory(Script::class)->make()->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
