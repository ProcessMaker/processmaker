<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class InputDocumentManagerTest extends ApiTestCase
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
     * Create new Input Document in process
     *
     * @param Process $process
     * @param User $user
     *
     * @return InputDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     */
    public function testCreateInputDocument(Process $process, User $user): InputDocument
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $structure = [
            'INP_DOC_UID',
            'PRO_ID',
            'PRO_UID',
            'INP_DOC_TITLE',
            'INP_DOC_DESCRIPTION',
            'INP_DOC_FORM_NEEDED',
            'INP_DOC_ORIGINAL',
            'INP_DOC_PUBLISHED',
            'INP_DOC_VERSIONING',
            'INP_DOC_DESTINATION_PATH',
            'INP_DOC_TAGS',
            'INP_DOC_TYPE_FILE',
            'INP_DOC_MAX_FILESIZE',
            'INP_DOC_MAX_FILESIZE_UNIT'
        ];

        $data = [];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        $data = [];
        //Post should have the parameter INP_DOC_FORM_NEEDED only types InputDocument::FORM_NEEDED_TYPE
        $faker = Faker::create();
        $data['inp_doc_form_needed'] =  $faker->sentence(2);
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $data['inp_doc_title'] =  $faker->sentence(3);
        $data['inp_doc_form_needed'] =  $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE));
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('INP_DOC_ID');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return InputDocument::where('INP_DOC_ID', $id)->get()->first();
    }

    /**
     * Get a list of Input Document in a project.
     *
     * @param Process $process
     * @param User $user
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateInputDocument
     */
    public function testListInputDocuments(Process $process, User $user): void
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
        //add Input Document to process
        $faker = Faker::create();
        factory(InputDocument::class, 10)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRO_ID' => $process->PRO_ID
        ]);

        //List Input Document
        $url = self::API_ROUTE . $process->PRO_UID . '/input-documents';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);

    }

    /**
     * Get a Input Document of a project.
     *
     * @param Process $process
     * @param User $user
     * @param InputDocument $inputDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateInputDocument
     */
    public function testGetInputDocument(Process $process, User $user, InputDocument $inputDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'INP_DOC_UID',
            'PRO_ID',
            'PRO_UID',
            'INP_DOC_TITLE',
            'INP_DOC_DESCRIPTION',
            'INP_DOC_FORM_NEEDED',
            'INP_DOC_ORIGINAL',
            'INP_DOC_PUBLISHED',
            'INP_DOC_VERSIONING',
            'INP_DOC_DESTINATION_PATH',
            'INP_DOC_TAGS',
            'INP_DOC_TYPE_FILE',
            'INP_DOC_MAX_FILESIZE',
            'INP_DOC_MAX_FILESIZE_UNIT'
        ];
        //load InputDocument
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $inputDocument->INP_DOC_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonstructure($structurePaginate);

        //InputDocument not belong to process.
        $inputDocument = factory(InputDocument::class)->create();
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $inputDocument->INP_DOC_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Input Document in process
     *
     * @param Process $process
     * @param User $user
     * @param InputDocument $inputDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateInputDocument
     */
    public function testUpdateInputDocument(Process $process, User $user, InputDocument $inputDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'inp_doc_title' => '',
            'inp_doc_description' => $faker->sentence(6),
            'inp_doc_form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
            'inp_doc_original' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
            'inp_doc_published' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
            'inp_doc_versioning' => $faker->randomElement([0, 1]),
            'inp_doc_tags' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE),
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $inputDocument->INP_DOC_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['inp_doc_title'] = $faker->sentence(2);
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $inputDocument->INP_DOC_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Input Document in process
     *
     * @param Process $process
     * @param User $user
     * @param InputDocument $InputDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateInputDocument
     */
    public function testDeleteInputDocument(Process $process, User $user, InputDocument $InputDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        //Remove InputDocument
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $InputDocument->INP_DOC_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        $InputDocument = factory(InputDocument::class)->make();

        //InputDocument not exist
        $url = self::API_ROUTE . $process->PRO_UID . '/input-document/' . $InputDocument->INP_DOC_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
