<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class OutputDocumentManagerTest extends ApiTestCase
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
     * Create new OutputDocument in process
     *
     * @return OutputDocument
     */
    public function testCreateOutputDocument(): OutputDocument
    {
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Post should have the parameter title, description, filename
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post should have the parameter defined in model constants
        $faker = Faker::create();
        $data['report_generator'] =  $faker->sentence(1);
        $data['generate'] =  $faker->sentence(1);
        $data['type'] =  $faker->sentence(1);
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $data['title'] =  $faker->sentence(3);
        $data['description'] =  $faker->sentence(3);
        $data['filename'] =  $faker->sentence(3);
        $data['report_generator'] =  $faker->randomElement(OutputDocument::DOC_REPORT_GENERATOR_TYPE);
        $data['generate'] =  $faker->randomElement(OutputDocument::DOC_GENERATE_TYPE);
        $data['type'] =  $faker->randomElement(OutputDocument::DOC_TYPE);
        $data['pdf_security_permissions'] =  $faker->randomElements(OutputDocument::PDF_SECURITY_PERMISSIONS_TYPE, 2, false);
        $data['pdf_security_open_password'] =  self::DEFAULT_PASS;
        $data['pdf_security_owner_password'] =  self::DEFAULT_PASS_OWNER;


        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $document = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
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
        ]);

        //Post title duplicated
        $url = self::API_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return OutputDocument::where('uid', $document['uid'])->first();
    }

    /**
     * Get a list of OutputDocument in a project.
     *
     * @depends testCreateOutputDocument
     */
    public function testListOutputDocuments(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add OutputDocument to process
        factory(OutputDocument::class, 10)->create([
            'process_id' => self::$process->id
        ]);

        //List OutputDocument
        $url = self::API_ROUTE . self::$process->uid . '/output-documents';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify count of data
        $this->assertEquals(11, $response->original['meta']->total);
    }

    /**
     * Get a OutputDocument of a project.
     *
     * @param OutputDocument $outputDocument
     *
     * @depends testCreateOutputDocument
     */
    public function testGetOutputDocument(OutputDocument $outputDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load OutPutDocument
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'uid',
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
        ]);

        //OutPutDocument not belong to process.
        $outputDocument = factory(OutputDocument::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update OutputDocument in process
     *
     * @param OutputDocument $outputDocument
     *
     * @depends testCreateOutputDocument
     */
    public function testUpdateOutputDocument(OutputDocument $outputDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'title' => '',
            'description' => '',
            'filename' => '',
            'report_generator' => $faker->randomElement(OutputDocument::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(OutputDocument::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(OutputDocument::DOC_TYPE)
        ];
        //The post should have the required parameters
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['title'] = $faker->sentence(2);
        $data['description'] = $faker->sentence(2);
        $data['filename'] = $faker->sentence(2);
        $data['pdf_security_permissions'] =  '';
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete $outputDocument in process
     *
     * @param OutputDocument $outputDocument
     *
     * @depends testCreateOutputDocument
     */
    public function testDeleteOutputDocument(OutputDocument $outputDocument): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove OutPutDocument
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $outputDocument = factory(OutputDocument::class)->make();

        //OutPutDocument not exist
        $url = self::API_ROUTE . self::$process->uid . '/output-document/' . $outputDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
