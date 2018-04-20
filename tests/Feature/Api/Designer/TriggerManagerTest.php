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
        $data = ['tri_title' => $faker->sentence(3)];
        //Post saved correctly
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $trigger = $response->json();
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title trigger duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);

        return $trigger;
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
    public function testUpdateTrigger(Process $process, User $user, Trigger $trigger)
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
        $url = self::API_ROUTE . $process->PRO_UID . '/trigger/' . $trigger->TRI_UID;
        $response = $this->api('PUT', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

    }

}
