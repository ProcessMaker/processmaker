<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class DynaformManagerTest extends ApiTestCase
{
    const API_ROUTE = '/api/1.0/project/';
    const DEFAULT_PASS = 'password';

    /**
     * @var User
     */
    protected static $user;
    /**
     * @var Process
     */
    protected static $process;

    /**
     * Create user and process
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

     * Create new Dynaform in process
     *
     * @return Dynaform
     */
    public function testCreateDynaform(): Dynaform
    {
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $data = [];
        //Post should have the parameter dyn_title
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $dynaform = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ]);

        //Post title duplicated
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return $dynaform;
    }

    /**
     * Copy/import Dynaform in process
     *
     * @param Dynaform $dynaform
     *
     * @return Dynaform
     *
     * @depends testCreateDynaform
     */
    public function testCopyImportDynaform(Dynaform $dynaform): Dynaform
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $data = [
            'copy_import' => 'test'
        ];
        //copy_import must be an array and fields pro_uid and dyn_uid are required.
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Process not exist
        $data['copy_import'] = [
            'pro_uid' => 'otheruid',
            'dyn_uid' => $dynaform->uid
        ];
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform not exist
        $otherProcess = self::$process;
        $data['copy_import'] = [
            'pro_uid' => $otherProcess->uid,
            'dyn_uid' => 'otherDynaformUid'
        ];
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform does not belong to the process.
        $otherDynaform = factory(Dynaform::class)->create();
        $data['copy_import'] = [
            'pro_uid' => self::$process->uid,
            'dyn_uid' => $otherDynaform->uid
        ];
        $url = self::API_ROUTE . self::$process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $data['copy_import'] = [
            'pro_uid' => self::$process->uid,
            'dyn_uid' => $dynaform->uid
        ];
        $process = factory(Process::class)->create();

        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $dynaformCreated = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ]);

        //Post title duplicated
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return $dynaformCreated;
    }

    /**
     * Get a list of Dynaform in a project.
     *
     * @depends testCreateDynaform
     */
    public function testListDynaform(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add Dynaform to process
        factory(Dynaform::class, 10)->create([
            'process_id' => self::$process->id
        ]);

        //List Dynaform
        $url = self::API_ROUTE . self::$process->uid . '/dynaforms';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonStructure([
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
     * Get a Dynaform of a project.
     *
     * @param Dynaform $dynaform
     *
     * @depends testCreateDynaform
     */
    public function testGetDynaform(Dynaform $dynaform): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load Dynaform
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ]);

        //Dynaform not belong to process.
        $dynaform = factory(Dynaform::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Dynaform in process
     *
     * @param Dynaform $dynaform
     *
     * @depends testCreateDynaform
     */
    public function testUpdateDynaform(Dynaform $dynaform): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'dyn_title' => '',
            'dyn_description' => ''
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['dyn_title'] = $faker->sentence(2);
        $data['dyn_description'] = $faker->sentence(5);
        $data['dyn_content'] = '';
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Dynaform in process
     *
     * @param Dynaform $dynaform
     *
     * @depends testCreateDynaform
     */
    public function testDeleteDynaform(Dynaform $dynaform): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove Dynaform
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $dynaform = factory(Dynaform::class)->make();

        //Dynaform not exist
        $url = self::API_ROUTE . self::$process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
