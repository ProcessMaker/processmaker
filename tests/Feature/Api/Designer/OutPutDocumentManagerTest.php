<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\OutPutDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class OutPutDocumentManagerTest extends ApiTestCase
{
    const API_ROUTE = '/api/1.0/project/';
    const DEFAULT_PASS = 'password';
    const DEFAULT_PASS_OWNER = 'password2';

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
     * Create new OutPut Document in process
     *
     * @param Process $process
     * @param User $user
     *
     * @return OutPutDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     */
    public function testCreateOutPutDocument(Process $process, User $user): OutPutDocument
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $structure = [
            'OUT_DOC_UID',
            'PRO_ID',
            'PRO_UID',
            'OUT_DOC_TITLE',
            'OUT_DOC_DESCRIPTION',
            'OUT_DOC_FILENAME',
            'OUT_DOC_TEMPLATE',
            'OUT_DOC_REPORT_GENERATOR',
            'OUT_DOC_LANDSCAPE',
            'OUT_DOC_MEDIA',
            'OUT_DOC_LEFT_MARGIN',
            'OUT_DOC_RIGHT_MARGIN',
            'OUT_DOC_TOP_MARGIN',
            'OUT_DOC_BOTTOM_MARGIN',
            'OUT_DOC_GENERATE',
            'OUT_DOC_TYPE',
            'OUT_DOC_CURRENT_REVISION',
            'OUT_DOC_FIELD_MAPPING',
            'OUT_DOC_VERSIONING',
            'OUT_DOC_DESTINATION_PATH',
            'OUT_DOC_TAGS',
            'OUT_DOC_PDF_SECURITY_ENABLED',
            'OUT_DOC_PDF_SECURITY_OPEN_PASSWORD',
            'OUT_DOC_PDF_SECURITY_OWNER_PASSWORD',
            'OUT_DOC_PDF_SECURITY_PERMISSIONS',
            'OUT_DOC_OPEN_TYPE'
        ];

        $data = [];
        //Post should have the parameter out_doc_title, out_doc_description, out_doc_filename
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        $data = [];
        //Post should have the parameter defined in model constants
        $faker = Faker::create();
        $data['out_doc_report_generator'] =  $faker->sentence(1);
        $data['out_doc_generate'] =  $faker->sentence(1);
        $data['out_doc_type'] =  $faker->sentence(1);
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $data['out_doc_title'] =  $faker->sentence(3);
        $data['out_doc_description'] =  $faker->sentence(3);
        $data['out_doc_filename'] =  $faker->sentence(3);
        $data['out_doc_report_generator'] =  $faker->randomElement(OutPutDocument::DOC_REPORT_GENERATOR_TYPE);
        $data['out_doc_generate'] =  $faker->randomElement(OutPutDocument::DOC_GENERATE_TYPE);
        $data['out_doc_type'] =  $faker->randomElement(OutPutDocument::DOC_TYPE);
        $data['out_doc_pdf_security_permissions'] =  $faker->randomElements(OutPutDocument::PDF_SECURITY_PERMISSIONS_TYPE, 2, false);
        $data['out_doc_pdf_security_open_password'] =  self::DEFAULT_PASS;
        $data['out_doc_pdf_security_owner_password'] =  self::DEFAULT_PASS_OWNER;


        $url = self::API_ROUTE . $process->PRO_UID . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $id = $response->json('OUT_DOC_ID');
        //Check structure of response.
        $response->assertJsonStructure($structure);

        //Post title duplicated
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return OutPutDocument::where('OUT_DOC_ID', $id)->get()->first();
    }

    /**
     * Get a list of OutPut Document in a project.
     *
     * @param Process $process
     * @param User $user
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateOutPutDocument
     */
    public function testListOutPutDocuments(Process $process, User $user): void
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
        //add OutPut Document to process
        $faker = Faker::create();
        factory(OutPutDocument::class, 10)->create([
            'PRO_UID' => $process->PRO_UID,
            'PRO_ID' => $process->PRO_ID
        ]);

        //List OutPut Document
        $url = self::API_ROUTE . $process->PRO_UID . '/output-documents';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $response->assertJsonCount(11, 'data');

        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);

    }

    /**
     * Get a OutPut Document of a project.
     *
     * @param Process $process
     * @param User $user
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateOutPutDocument
     */
    public function testGetOutPutDocument(Process $process, User $user, OutPutDocument $outPutDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);
        $structurePaginate = [
            'OUT_DOC_UID',
            'PRO_ID',
            'PRO_UID',
            'OUT_DOC_TITLE',
            'OUT_DOC_DESCRIPTION',
            'OUT_DOC_FILENAME',
            'OUT_DOC_TEMPLATE',
            'OUT_DOC_REPORT_GENERATOR',
            'OUT_DOC_LANDSCAPE',
            'OUT_DOC_MEDIA',
            'OUT_DOC_LEFT_MARGIN',
            'OUT_DOC_RIGHT_MARGIN',
            'OUT_DOC_TOP_MARGIN',
            'OUT_DOC_BOTTOM_MARGIN',
            'OUT_DOC_GENERATE',
            'OUT_DOC_TYPE',
            'OUT_DOC_CURRENT_REVISION',
            'OUT_DOC_FIELD_MAPPING',
            'OUT_DOC_VERSIONING',
            'OUT_DOC_DESTINATION_PATH',
            'OUT_DOC_TAGS',
            'OUT_DOC_PDF_SECURITY_ENABLED',
            'OUT_DOC_PDF_SECURITY_OPEN_PASSWORD',
            'OUT_DOC_PDF_SECURITY_OWNER_PASSWORD',
            'OUT_DOC_PDF_SECURITY_PERMISSIONS',
            'OUT_DOC_OPEN_TYPE'
        ];
        //load OutPutDocument
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure($structurePaginate);

        //OutPutDocument not belong to process.
        $outPutDocument = factory(OutPutDocument::class)->create();
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update OutPut Document in process
     *
     * @param Process $process
     * @param User $user
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateOutPutDocument
     */
    public function testUpdateOutPutDocument(Process $process, User $user, OutPutDocument $outPutDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'out_doc_title' => '',
            'out_doc_description' => '',
            'out_doc_filename' => '',
            'out_doc_report_generator' => $faker->randomElement(OutPutDocument::DOC_REPORT_GENERATOR_TYPE),
            'out_doc_generate' => $faker->randomElement(OutPutDocument::DOC_GENERATE_TYPE),
            'out_doc_type' => $faker->randomElement(OutPutDocument::DOC_TYPE)
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['out_doc_title'] = $faker->sentence(2);
        $data['out_doc_description'] = $faker->sentence(2);
        $data['out_doc_filename'] = $faker->sentence(2);
        $data['out_doc_pdf_security_permissions'] =  $faker->randomElements(OutPutDocument::PDF_SECURITY_PERMISSIONS_TYPE, 2, false);
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete OutPut Document in process
     *
     * @param Process $process
     * @param User $user
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateProcess
     * @depends testCreateUser
     * @depends testCreateOutPutDocument
     */
    public function testDeleteOutPutDocument(Process $process, User $user, OutPutDocument $outPutDocument): void
    {
        $this->auth($user->USR_USERNAME, self::DEFAULT_PASS);

        //Remove OutPutDocument
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        $outPutDocument = factory(OutPutDocument::class)->make();

        //OutPutDocument not exist
        $url = self::API_ROUTE . $process->PRO_UID . '/output-document/' . $outPutDocument->OUT_DOC_UID;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
