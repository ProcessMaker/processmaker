<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\OutputDocument as Document;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\User;
use Tests\TestCase;

class OutputDocumentManagerTest extends TestCase
{
    use DatabaseTransactions;

    const DEFAULT_PASS = 'password';
    const DEFAULT_PASS_OWNER = 'password2';

    protected $user;
    protected $process;

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
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),            
        ]);

        $this->process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

    }

    /**
     * Test validate structure table
     */
    public function testStructureTable()
    {
        $db = DB::connection()->getSchemaBuilder()->getColumnListing('output_documents');
        $structure = [
            'id',
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
            'open_type',
            'generate',
            'properties',
            'process_id',
            'created_at',
            'updated_at'
        ];
        sort($db);
        sort($structure);

        $this->assertEquals(json_encode($structure), json_encode($db));
    }

    /**
     * Create document need parameters required
     */
    public function testNoCreateByParametersRequired()
    {
        //Post should have the parameter title, description, filename
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, []);
        $response->assertStatus(422);
    }

    /**
     * Post should have the parameter defined in model constants
     * Document::DOC_REPORT_GENERATOR_TYPE,
     * Document::DOC_GENERATE_TYPE
     * Document::DOC_TYPE
     */
    public function testCreateParameterDefinedConstant()
    {
        $faker = Faker::create();
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'report_generator' => $faker->sentence(1),
            'generate' => $faker->sentence(1),
            'type' => $faker->sentence(1),
        ]);
        //validating the answer is an error
        $response->assertStatus(422);
    }

    /**
     * Create new Document successfully
     */
    public function testCreateDocument()
    {
        //Post saved correctly
        $faker = Faker::create();

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => $faker->sentence(3),
            'description' => $faker->sentence(3),
            'filename' => $faker->sentence(3),
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE),
            'properties' => [
                'pdf_security_permissions' => $faker->randomElements(Document::PDF_SECURITY_PERMISSIONS_TYPE, 2, false),
                'pdf_security_open_password' => self::DEFAULT_PASS,
                'pdf_security_owner_password' => self::DEFAULT_PASS_OWNER,
            ]
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create document with the same title
     */
    public function testNoCreateDocumentWithTitleExists()
    {
        $title = 'Title Output Document';

        //add Document to process
        factory(Document::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => $title,
            'description' => $faker->sentence(3),
            'filename' => $faker->sentence(3),
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE),
            'properties' => [
                'pdf_security_permissions' => $faker->randomElements(Document::PDF_SECURITY_PERMISSIONS_TYPE, 2, false),
                'pdf_security_open_password' => self::DEFAULT_PASS,
                'pdf_security_owner_password' => self::DEFAULT_PASS_OWNER,
            ]
        ]);
        //validating the answer is correct.
        $response->assertStatus(422);
    }

    /**
     * Get a list of Document in a project.
     */
    public function testListDocuments()
    {
        //add Document to process
        factory(Document::class, 11)->create([
            'process_id' => $this->process->id
        ]);

        //List Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-documents';
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify count of data
        $this->assertEquals(11, $response->original->meta->total);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a list of Output Document with parameters
     */
    public function testListOutputDocumentWithQueryParameter()
    {
        $title = 'search Title Output document';
        factory(Document::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List Output Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-documents?' . $query;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify response in meta
        $this->assertEquals(1, $response->original->meta->total);
        $this->assertEquals(1, $response->original->meta->count);
        $this->assertEquals($perPage, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertEquals(1, $response->original->meta->total_pages);
        $this->assertEquals($title, $response->original->meta->filter);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * List documents with filter
     */
    public function testListDocumentWithFilter()
    {
        //add Document to process
        $title = 'Title for search';
        factory(Document::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-documents' . $query;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //verify response in meta
        $this->assertEquals(1, $response->original->meta->total);
        $this->assertEquals(1, $response->original->meta->count);
        $this->assertEquals($perPage, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertEquals(1, $response->original->meta->total_pages);
        $this->assertEquals($title, $response->original->meta->filter);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a Document of process.
     */
    public function testGetDocument()
    {
        $document = factory(Document::class)->create([
            'process_id' => $this->process->id
        ]);

        //load Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * document not belong to process.
     */
    public function testGetDocumentNotBelongProcess()
    {
        $document = factory(Document::class)->create();
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * The update should have the required parameters
     */
    public function testUpdateParametersRequired()
    {
        $document = factory(Document::class)->create([
            'process_id' => $this->process->id
        ]);

        $faker = Faker::create();

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => '',
            'description' => '',
            'filename' => '',
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE)
        ]);
        //Validate the answer is incorrect
        $response->assertStatus(422);
    }

    /**
     * Update Document in process successfully
     */
    public function testUpdateDocument()
    {
        $document = factory(Document::class)->create([
            'process_id' => $this->process->id
        ]);

        $faker = Faker::create();

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => $faker->sentence(2),
            'description' => $faker->sentence(2),
            'filename' => $faker->sentence(2),
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE),
            'properties' => [
                'pdf_security_permissions' => []
            ]
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Update Document in process successfully with same title
     */
    public function testUpdateDocumentSameTitle()
    {
        $document = factory(Document::class)->create([
            'process_id' => $this->process->id
        ]);

        $faker = Faker::create();

        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'description' => $faker->sentence(2),
            'filename' => $faker->sentence(2),
            'report_generator' => $faker->randomElement(Document::DOC_REPORT_GENERATOR_TYPE),
            'generate' => $faker->randomElement(Document::DOC_GENERATE_TYPE),
            'type' => $faker->randomElement(Document::DOC_TYPE),
            'properties' => [
                'pdf_security_permissions' => []
            ]
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete document successfully
     */
    public function testDeleteDocument()
    {
        $document = factory(Document::class)->create([
            'process_id' => $this->process->id
        ]);

        //Remove Document
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }


    /**
     * Test delete Document not exist
     */
    public function testDeleteDocumentNotExist()
    {
        $document = factory(Document::class)->make();
        $url = self::API_OUTPUT_DOCUMENT_ROUTE . $this->process->uid . '/output-document/' . $document->uid;
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
