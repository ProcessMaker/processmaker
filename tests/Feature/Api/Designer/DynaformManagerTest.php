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
     *
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
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $structure = [
            'DYN_UID',
            'PRO_ID',
            'PRO_UID',
            'DYN_TITLE',
            'DYN_DESCRIPTION',
            'DYN_CONTENT',
            'DYN_LABEL'
        ];

        $data = [];
        //Post should have the parameter dyn_title
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('DYN_ID');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Dynaform::where('DYN_ID', $id)->get()->first();
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
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $structure = [
            'DYN_UID',
            'PRO_ID',
            'PRO_UID',
            'DYN_TITLE',
            'DYN_DESCRIPTION',
            'DYN_CONTENT',
            'DYN_LABEL'
        ];

        $data = [
            'copy_import' => 'test'
        ];
        //copy_import must be an array and fields prj_uid and dyn_uid are required.
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Process not exist
        $data['copy_import'] = [
            'pro_uid' => 'otheruid',
            'dyn_uid' => $dynaform->DYN_UID
        ];
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform not exist
        $otherProcess = $process;
        $data['copy_import'] = [
            'pro_uid' => $otherProcess->PRO_UID,
            'dyn_uid' => 'otherDynaformUid'
        ];
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Dynaform does not belong to the process.
        $otherProcess = $process;
        $otherDynaform = factory(Dynaform::class)->create();
        $data['copy_import'] = [
            'pro_uid' => $otherProcess->PRO_UID,
            'dyn_uid' => $otherDynaform->DYN_UID
        ];
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(404);

        //Post saved correctly
        $faker = Faker::create();
        $data['dyn_title'] = $faker->sentence(3);
        $data['dyn_description'] = $faker->sentence(10);

        $data['copy_import'] = [
            'pro_uid' => $otherProcess->PRO_UID,
            'dyn_uid' => $dynaform->DYN_UID
        ];
        $process = factory(Process::class)->create();

        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('DYN_ID');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Dynaform::where('DYN_ID', $id)->get()->first();
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
        //add Dynaform to process
        factory(Dynaform::class, 10)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRO_ID' => $process->PRO_ID
        ]);

        //List Dynaform
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaforms';
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
     * @param Dynaform $Dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testGetDynaform(Process $process, User $user, Dynaform $Dynaform): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structure = [
            'DYN_UID',
            'PRO_ID',
            'PRO_UID',
            'DYN_TITLE',
            'DYN_DESCRIPTION',
            'DYN_CONTENT',
            'DYN_LABEL',
            'DYN_UPDATE_DATE'
        ];
        //load Dynaform
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure($structure);

        //Dynaform not belong to process.
        $Dynaform = factory(Dynaform::class)->create();
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Dynaform in process
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $Dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testUpdateDynaform(Process $process, User $user, Dynaform $Dynaform): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'dyn_title' => '',
            'dyn_description' => ''
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['dyn_title'] = $faker->sentence(2);
        $data['dyn_description'] = $faker->sentence(5);
        $data['dyn_content'] = '';
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Dynaform in process
     *
     * @param Process $process
     * @param User $user
     * @param Dynaform $Dynaform
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateDynaform
     */
    public function testDeleteDynaform(Process $process, User $user, Dynaform $Dynaform): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        //Remove Dynaform
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        $Dynaform = factory(Dynaform::class)->make();

        //Dynaform not exist
        $url = self::API_ROUTE . $process->PRO_UID . '/dynaform/' . $Dynaform->DYN_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
