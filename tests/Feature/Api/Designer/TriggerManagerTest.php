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
            'user_id' => self::$user->id
        ]);
    }


    /**
     * Create new trigger in process
     *
     * @return Trigger
     */
    public function testCreateTrigger(): Trigger
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Post should have the parameter title
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);

        $faker = Faker::create();
        $data = [
            'title' => $faker->sentence(3),
            'description' => $faker->sentence(6),
            'param' => $faker->words($faker->randomDigitNotNull)
        ];
        //Post saved correctly
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $trigger = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
            'title',
            'description',
            'type',
            'webbot',
            'param'
        ]);

        //duplicate titles are not allowed
        $url = self::API_ROUTE . self::$process->uid . '/trigger';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Trigger::where('uid', $trigger->uid)->first();
    }

    /**
     * Get a list of triggers in a project.
     *
     * @depends testCreateTrigger
     */
    public function testListTriggers(): void
    {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
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

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //verify count of data
        $this->assertEquals(11, $response->original->meta->total);
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
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load trigger
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'uid',
            'title',
            'description',
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
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'title' => '',
            'description' => $faker->sentence(6),
            'webbot' => $faker->sentence(2),
            'param' => $faker->words(3),
        ];
        //The post must have the required parameters
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['title'] = $faker->sentence(2);
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(204);
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
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test must be refactored to support database transaction style testing.'
        );
 
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove trigger
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(204);

        $trigger = factory(Trigger::class)->make();

        //trigger not exist
        $url = self::API_ROUTE . self::$process->uid . '/trigger/' . $trigger->uid;
        $response = $this->api('DELETE', $url);
        $response->assertStatus(404);
    }

}
