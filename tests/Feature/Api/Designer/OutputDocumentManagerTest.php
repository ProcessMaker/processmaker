<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\OutputDocument as Document;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class OutputDocumentManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const DEFAULT_PASS = 'password';
    const DEFAULT_PASS_OWNER = 'password2';

    protected static $user;
    protected static $process;

    const API_OUTPUT_DOCUMENT_ROUTE = '/api/1.0/process/';

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
     *  Init data user and process
     */
    protected function setUp(): void
    {
        parent::setUp();
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
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Post should have the parameter title, description, filename
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post should have the parameter defined in model constants
        $faker = Faker::create();
        $data['report_generator'] = $faker->sentence(1);
        $data['generate'] = $faker->sentence(1);
        $data['type'] = $faker->sentence(1);
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document';
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

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $document = $response->original;
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);

        //Post title duplicated
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document';
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
        $data = factory(Document::class, 11)->create([
            'process_id' => self::$process->id
        ]);

        //List Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-documents';
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

        //List Document with parameters pages and sort
        $query = '?current_page=2&per_page=5&sort_by=description&sort_order=DESC';

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-documents' . $query;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify response in meta
        $this->assertEquals(11, $response->original->meta->total);
        $this->assertEquals(5, $response->original->meta->count);
        $this->assertEquals(5, $response->original->meta->per_page);
        $this->assertEquals(2, $response->original->meta->current_page);
        $this->assertEquals(3, $response->original->meta->total_pages);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }

        //List Document with filter option
        $query = '?current_page=1&per_page=5&sort_by=description&sort_order=DESC';
        $filter = substr($data[0]->title, 0, strlen($data[0]->title) / 2);
        $query .= '&filter=' . $filter;
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-documents' . $query;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify response in meta
        $this->assertGreaterThanOrEqual(1, $response->original->meta->total);
        $this->assertGreaterThanOrEqual(1, $response->original->meta->count);
        $this->assertEquals(5, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertGreaterThanOrEqual(1, $response->original->meta->total_pages);
        $this->assertEquals($filter, $response->original->meta->filter);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

    /**
     * Get a Document of a project.
     */
    public function testGetDocument(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $document = factory(Document::class)->create([
            'process_id' => self::$process->id
        ]);

        //load Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);

        //output-document not belong to process.
        $document = factory(Document::class)->create();
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Document in process
     */
    public function testUpdateDocument(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $document = factory(Document::class)->create([
            'process_id' => self::$process->id
        ]);

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
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['title'] = $faker->sentence(2);
        $data['description'] = $faker->sentence(2);
        $data['filename'] = $faker->sentence(2);
        $data['properties']['pdf_security_permissions'] = [];
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete $document in process
     */
    public function testDeleteDocument(): void
    {
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $document = factory(Document::class)->create([
            'process_id' => self::$process->id
        ]);

        //Remove Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $document = factory(Document::class)->make();

        //output-document not exist
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . self::$process->uid . '/output-document/' . $document->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
