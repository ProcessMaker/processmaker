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
     * create User
     * @return User
     *
     */
    public function testCreateUser(): User
    {
        $user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);
        $this->assertNotNull($user);
        $this->assertNotNull($user->uid);
        $this->assertNotNull($user->id);
        return $user;
    }

    /**
     * Create process
     * @param User $user
     *
     * @depends testCreateUser
     * @return Process
     */
    public function testCreateProcess(User $user): Process
    {
        $process = factory(Process::class)->create([
            'creator_user_id' => $user->id
        ]);
        $this->assertNotNull($process);
        $this->assertNotNull($process->id);
        return $process;
    }



    /**
     * Create new Dynaform in process
     *
     * @param Process $process
     * @param User $user
     *
     * @return Dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     */
    public function testCreateDynaform(Process $process, User $user): Dynaform
    {
        $this->auth($user->username, self::DEFAULT_PASS);

        $structure = [
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ];

        $data = [];
        //Post should have the parameter dyn_title
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('id');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Dynaform::where('id', $id)->get()->first();
    }

    /**
     * Copy/import Dynaform in process
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $dynaform
     *
     * @return Dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testCopyImportDynaform(Process $process, User $user, Dynaform $dynaform): Dynaform
    {
        $this->auth($user->username, self::DEFAULT_PASS);

        $structure = [
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ];

        $data = [
            'copy_import' => 'test'
        ];
        //copy_import must be an array and fields pro_uid and dyn_uid are required.
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Process not exist
        $data['copy_import'] = [
            'pro_uid' => 'otheruid',
            'dyn_uid' => $dynaform->uid
        ];
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform not exist
        $otherProcess = $process;
        $data['copy_import'] = [
            'pro_uid' => $otherProcess->uid,
            'dyn_uid' => 'otherDynaformUid'
        ];
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform does not belong to the process.
        $otherProcess = $process;
        $otherDynaform = factory(Dynaform::class)->create();
        $data['copy_import'] = [
            'pro_uid' => $otherProcess->uid,
            'dyn_uid' => $otherDynaform->uid
        ];
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $data['copy_import'] = [
            'pro_uid' => $otherProcess->uid,
            'dyn_uid' => $dynaform->uid
        ];
        $process = factory(Process::class)->create();

        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('id');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->uid . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Dynaform::where('id', $id)->get()->first();
    }

    /**
     * Get a list of Dynaform in a project.
     *
     * @param Process $process
     * @param User $user
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testListDynaform(Process $process, User $user): void
    {
        $this->auth($user->username, self::DEFAULT_PASS);
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
        //add Dynaform to process
        factory(Dynaform::class, 10)->create([
            'process_id' => $process->id
        ]);

        //List Dynaform
        $url = self::API_ROUTE . $process->uid . '/dynaforms';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);

    }

    /**
     * Get a Dynaform of a project.
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testGetDynaform(Process $process, User $user, Dynaform $dynaform): void
    {
        $this->auth($user->username, self::DEFAULT_PASS);
        $structure = [
            'uid',
            'process_id',
            'title',
            'description',
            'content',
            'label'
        ];
        //load Dynaform
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure($structure);

        //Dynaform not belong to process.
        $dynaform = factory(Dynaform::class)->create();
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Dynaform in process
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testUpdateDynaform(Process $process, User $user, Dynaform $dynaform): void
    {
        $this->auth($user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'dyn_title' => '',
            'dyn_description' => ''
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['dyn_title'] = $faker->sentence(2);
        $data['dyn_description'] = $faker->sentence(5);
        $data['dyn_content'] = '';
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Dynaform in process
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testDeleteDynaform(Process $process, User $user, Dynaform $dynaform): void
    {
        $this->auth($user->username, self::DEFAULT_PASS);

        //Remove Dynaform
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        $dynaform = factory(Dynaform::class)->make();

        //Dynaform not exist
        $url = self::API_ROUTE . $process->uid . '/dynaform/' . $dynaform->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
