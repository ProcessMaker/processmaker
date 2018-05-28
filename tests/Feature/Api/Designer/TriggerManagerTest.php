<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Trigger;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TriggerManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_TRIGGER = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    protected $user;
    protected $process;

    const STRUCTURE = [
        'uid',
        'title',
        'description',
        'type',
        'webbot',
        'param'
    ];

    /**
     * Create user and process
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->process = factory(Process::class)->create([
            'creator_user_id' => $this->user->id
        ]);

        $this->auth($this->user->username, self::DEFAULT_PASS);
    }

    /**
     * Test verify the parameter required for create Trigger
     */
    public function testNotCreatedForParameterRequired(): void
    {
        //Post should have the parameter required
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger';
        $response = $this->api('POST', $url, []);

        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new trigger in process
     */
    public function testCreateTrigger(): void
    {
        $faker = Faker::create();
        //Post saved correctly
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger';
        $response = $this->api('POST', $url, [
            'title' => 'Title Trigger',
            'description' => $faker->sentence(6),
            'param' => $faker->words($faker->randomDigitNotNull)
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create a Trigger with an existing title
     */
    public function testNotCreateTriggerWithTitleExists(): Void
    {
        factory(Trigger::class)->create([
            'title' => 'Title Trigger',
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger';
        $response = $this->api('POST', $url, [
            'title' => 'Title Trigger',
            'description' => $faker->sentence(6),
            'param' => $faker->words($faker->randomDigitNotNull)
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of triggers in a project.
     */
    public function testListTriggers(): void
    {
        //add triggers to process
        $faker = Faker::create();
        factory(Trigger::class, 10)->create([
            'process_id' => $this->process->id,
            'param' => $faker->words($faker->randomDigitNotNull)
        ]);

        //List triggers
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/triggers';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //verify count of data
        $this->assertEquals(10, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a trigger of a project.
     */
    public function testGetTrigger(): void
    {
        //add trigger to process
        $faker = Faker::create();

        //load trigger
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->create([
                'process_id' => $this->process->id,
                'param' => $faker->words($faker->randomDigitNotNull)
            ])->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * The trigger not belongs to process.
     */
    public function testGetTriggerNotBelongToProcess(): void
    {
        //load trigger
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->create()->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Parameters required for update of Triggers
     */
    public function testUpdateTriggerParametersRequired(): void
    {
        $faker = Faker::create();
        //The post must have the required parameters
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->create([
                'process_id' => $this->process->id,
                'param' => $faker->words($faker->randomDigitNotNull)
            ])->uid;
        $response = $this->api('PUT', $url, [
            'title' => '',
            'description' => $faker->sentence(6),
            'webbot' => $faker->sentence(2),
            'param' => $faker->words(3),
        ]);
        //Validate the answer is incorrect
        $response->assertStatus(422);
    }

    /**
     * Update trigger in process
     */
    public function testUpdateTrigger(): void
    {
        $faker = Faker::create();
        //Post saved success
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->create([
                'process_id' => $this->process->id,
                'param' => $faker->words($faker->randomDigitNotNull)
            ])->uid;
        $response = $this->api('PUT', $url, [
            'title' => $faker->sentence(2)
        ]);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Trigger in process
     */
    public function testDeleteTrigger(): void
    {
        //Remove Trigger
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * The trigger does not exist in process
     */
    public function testDeleteTriggerNotExist(): void
    {
        //Trigger not exist
        $url = self::API_TEST_TRIGGER . $this->process->uid . '/trigger/' . factory(Trigger::class)->make()->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
