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

    /**
     * Create process
     * @return Process
     */
    public function testCreateProcess(): Process
    {
        $process = factory(Process::class)->create();
        $this->assertNotNull($process);
        $this->assertNotNull($process->PRO_UID);
        return $process;
    }

    /**
     * create User
     * @return User
     */
    public function testCreateUser(): User
    {
        $user = factory(User::class)->create([
            'USR_PASSWORD' => Hash::make(self::DEFAULT_PASS),
            'USR_ROLE' => Role::PROCESSMAKER_ADMIN
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($user->USR_UID);
        $this->assertNotNull($user->USR_ID);
        return $user;
    }

    /**
     * Create new trigger in process
     *
     * @param Process $process
     * @param User $user
     *
     * @return Trigger
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     */
    public function testCreateTrigger(Process $process, User $user): Trigger
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $structure = [
            'TRI_ID',
            'TRI_UID',
            'TRI_TITLE',
            'TRI_DESCRIPTION',
            'PRO_ID',
            'PRO_UID',
            'TRI_TYPE',
            'TRI_WEBBOT',
            'TRI_PARAM'
        ];

        $data = [];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger';
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
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $triggerId = $response->json('TRI_ID');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title trigger duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Trigger::where('TRI_ID', $triggerId)->get()->first();
    }

    /**
     * Get a list of triggers in a project.
     *
     * @param Process $process
     * @param User $user
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateTrigger
     */
    public function testListTriggers(Process $process, User $user): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'current_page',
            'data',
            'first_page_url',
            'from',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
        ];
        //add triggers to process
        $faker = Faker::create();
        factory(Trigger::class, 10)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRO_ID' => $process->PRO_ID,
            'TRI_PARAM' => $faker->words($faker->randomDigitNotNull)
        ]);

        //List triggers
        $url = self::API_ROUTE . $process->PRO_UID . '/triggers';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);

    }

    /**
     * Get a trigger of a project.
     *
     * @param Process $process
     * @param User $user
     * @param Trigger $trigger
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateTrigger
     */
    public function testGetTrigger(Process $process, User $user, Trigger $trigger): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'TRI_UID',
            'TRI_TITLE',
            'TRI_DESCRIPTION',
            'PRO_ID',
            'PRO_UID',
            'TRI_TYPE',
            'TRI_WEBBOT',
            'TRI_PARAM'
        ];
        //load trigger
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);

        //trigger not belong to process.
        $trigger = factory(Trigger::class)->create();
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update trigger in process
     *
     * @param Process $process
     * @param User $user
     * @param Trigger $trigger
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateTrigger
     */
    public function testUpdateTrigger(Process $process, User $user, Trigger $trigger): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'tri_title' => '',
            'tri_description' => $faker->sentence(6),
            'tri_webbot' => $faker->sentence(2),
            'tri_param' => $faker->words(3),
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['tri_title'] = $faker->sentence(2);
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete trigger in process
     *
     * @param Process $process
     * @param User $user
     * @param Trigger $trigger
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateTrigger
     */
    public function testDeleteTrigger(Process $process, User $user, Trigger $trigger): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        //Remove trigger
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        $trigger = factory(Trigger::class)->make();

        //trigger not exist
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
