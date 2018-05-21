<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Document;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class DocumentManagerTest extends ApiTestCase
{
    const DEFAULT_PASS = 'password';
    const DEFAULT_PASS_OWNER = 'password2';

    protected static $user;
    protected static $process;

    const STRUCTURE = [
        'uid',
        'title',
        'description',
        'filename',
        'template',
        'report_generator',
        'type',
        'versioning',
        'current_revision',
        'tags',
        'generate',
        'properties',
    ];

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
     * Create new Document in process
     *
     * @return Document
     */
    public function testCreateDocument(): Document
    {
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Post should have the parameter title, description, filename
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post should have the parameter defined in model constants
        $faker = Faker::create();
        $data['report_generator'] = $faker->sentence(1);
        $data['generate'] = $faker->sentence(1);
        $data['type'] = $faker->sentence(1);
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $data['title'] = $faker->sentence(3);
        $data['description'] = $faker->sentence(3);
        $data['filename'] = $faker->sentence(3);
        $data['report_generator'] = $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE);
        $data['generate'] = $faker->randomElement(Document::DOC_GENERATE_TYPE);
        $data['type'] = $faker->randomElement(Document::DOC_TYPE);
        $data['properties']['pdf_security_permissions'] = $faker->randomElements(Document::PDF_SECURITY_PERMISSIONS_TYPE, 2, false);
        $data['properties']['pdf_security_open_password'] = self::DEFAULT_PASS;
        $data['properties']['pdf_security_owner_password'] = self::DEFAULT_PASS_OWNER;

        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $document = $response->original;
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);

        //Post title duplicated
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return Document::where('uid', $document->uid)->first();
    }

    /**
     * Get a list of Document in a project.
     *
     * @depends testCreateDocument
     */
    public function testListDocuments(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add Document to process
        factory(Document::class, 10)->create([
            'process_id' => self::$process->id
        ]);

        //List Document
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-documents';
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
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

    /**
     * Get a Document of a project.
     *
     * @param Document $Document
     *
     * @depends testCreateDocument
     */
    public function testGetDocument(Document $Document): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load Document
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);

        //Document not belong to process.
        $Document = factory(Document::class)->create();
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Document in process
     *
     * @param Document $Document
     *
     * @depends testCreateDocument
     */
    public function testUpdateDocument(Document $Document): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'title' => '',
            'description' => '',
            'filename' => '',
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE)
        ];
        //The post should have the required parameters
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['title'] = $faker->sentence(2);
        $data['description'] = $faker->sentence(2);
        $data['filename'] = $faker->sentence(2);
        $data['properties']['pdf_security_permissions'] = [];
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete $Document in process
     *
     * @param Document $Document
     *
     * @depends testCreateDocument
     */
    public function testDeleteDocument(Document $Document): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove Document
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $Document = factory(Document::class)->make();

        //Document not exist
        $url = self::API_ROUTE . 'process/' . self::$process->uid . '/-document/' . $Document->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
