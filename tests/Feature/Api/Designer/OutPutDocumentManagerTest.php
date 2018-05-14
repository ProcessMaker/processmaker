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
     * Create new OutPut Document in process
     *
     * @return OutPutDocument
     */
    public function testCreateOutPutDocument(): OutPutDocument
    {
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $data = [];
        //Post should have the parameter title, description, filename
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        $data = [];
        //Post should have the parameter defined in model constants
        $faker = Faker::create();
        $data['out_doc_report_generator'] =  $faker->sentence(1);
        $data['out_doc_generate'] =  $faker->sentence(1);
        $data['out_doc_type'] =  $faker->sentence(1);
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
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


        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $document = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
            'process_id',
            'title',
            'description',
            'filename',
            'template',
            'report_generator',
            'landscape',
            'media',
            'left_margin',
            'right_margin',
            'top_margin',
            'bottom_margin',
            'generate',
            'type',
            'current_revision',
            'field_mapping',
            'versioning',
            'destination_path',
            'tags',
            'pdf_security_enabled',
            'pdf_security_open_password',
            'pdf_security_owner_password',
            'pdf_security_permissions',
            'open_type'
        ]);

        //Post title duplicated
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return $document;
    }

    /**
     * Get a list of OutPut Document in a project.
     *
     * @depends testCreateOutPutDocument
     */
    public function testListOutPutDocuments(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add OutPut Document to process
        factory(OutPutDocument::class, 10)->create([
            'process_id' => self::$process->id
        ]);

        //List OutPut Document
        $url = self::API_ROUTE . self::$process->uid . '/output-documents';
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
     * Get a OutPut Document of a project.
     *
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateOutPutDocument
     */
    public function testGetOutPutDocument(OutPutDocument $outPutDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load OutPutDocument
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'uid',
            'process_id',
            'title',
            'description',
            'filename',
            'template',
            'report_generator',
            'landscape',
            'media',
            'left_margin',
            'right_margin',
            'top_margin',
            'bottom_margin',
            'generate',
            'type',
            'current_revision',
            'field_mapping',
            'versioning',
            'destination_path',
            'tags',
            'pdf_security_enabled',
            'pdf_security_open_password',
            'pdf_security_owner_password',
            'pdf_security_permissions',
            'open_type'
        ]);

        //OutPutDocument not belong to process.
        $outPutDocument = factory(OutPutDocument::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update OutPut Document in process
     *
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateOutPutDocument
     */
    public function testUpdateOutPutDocument(OutPutDocument $outPutDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

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
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['out_doc_title'] = $faker->sentence(2);
        $data['out_doc_description'] = $faker->sentence(2);
        $data['out_doc_filename'] = $faker->sentence(2);
        $data['out_doc_pdf_security_permissions'] =  '';
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete OutPut Document in process
     *
     * @param OutPutDocument $outPutDocument
     *
     * @depends testCreateOutPutDocument
     */
    public function testDeleteOutPutDocument(OutPutDocument $outPutDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove OutPutDocument
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $outPutDocument = factory(OutPutDocument::class)->make();

        //OutPutDocument not exist
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outPutDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
