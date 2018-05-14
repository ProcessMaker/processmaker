<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Trigger;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TriggerManagerTest extends ApiTestCase
{
    const API_ROUTE = '/api/1.0/project/';
    const DEFAULT_PASS = 'password';

    protected static $user;
    protected static $process;

    /**
     * Init variables User and Process
     */
    private function initProcess(): void
    {
        self::$user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        self::$process = factory(Process::class)->create([
            'creator_user_id' => self::$user->id
        ]);
    }


    /**
     * Create new trigger in process
     *
     * @return Trigger
     */
    public function testCreateTrigger(): Trigger
    {
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $data = [];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        $faker = Faker::create();
        $data = [
            'tri_title' => $faker->sentence(3),
            'tri_description' => $faker->sentence(6),
            'tri_param' => $faker->words($faker->randomDigitNotNull)
        ];
        //Post saved correctly
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $trigger = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'id',
            'uid',
            'title',
            'description',
            'process_id',
            'type',
            'webbot',
            'param'
        ]);

        //Post title trigger duplicated
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return $trigger;
    }

    /**
     * Get a list of triggers in a project.
     *
     * @depends testCreateTrigger
     */
    public function testListTriggers(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add triggers to process
        $faker = Faker::create();
        factory(Trigger::class, 10)->create([
            'process_id' => self::$process->id,
            'param' => $faker->words($faker->randomDigitNotNull)
        ]);

        //List triggers
        $url = self::API_ROUTE . self::$process->uid . '/triggers';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonstructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
        ]);

    }

    /**
     * Get a trigger of a project.
     *
     * @param Trigger $trigger
     *
     * @depends testCreateTrigger
     */
    public function testGetTrigger(Trigger $trigger): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load trigger
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonstructure([
            'id',
            'uid',
            'title',
            'description',
            'process_id',
            'type',
            'webbot',
            'param'
        ]);

        //trigger not belong to process.
        $trigger = factory(Trigger::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update trigger in process
     *
     * @param Trigger $trigger
     *
     * @depends testCreateTrigger
     */
    public function testUpdateTrigger(Trigger $trigger): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'tri_title' => '',
            'tri_description' => $faker->sentence(6),
            'tri_webbot' => $faker->sentence(2),
            'tri_param' => $faker->words(3),
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['tri_title'] = $faker->sentence(2);
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete trigger in process
     *
     * @param Trigger $trigger
     *
     * @depends testCreateTrigger
     */
    public function testDeleteTrigger(Trigger $trigger): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove trigger
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $trigger = factory(Trigger::class)->make();

        //trigger not exist
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
