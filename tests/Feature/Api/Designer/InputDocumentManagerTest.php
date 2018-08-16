<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class InputDocumentManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_INPUT_DOCUMENT = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    protected $user;
    protected $process;

    const STRUCTURE = [
        'uid',
        'title',
        'description',
        'form_needed',
        'original',
        'published',
        'versioning',
        'destination_path',
        'tags',
    ];

    /**
     * Create user and process
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $this->auth($this->user->username, self::DEFAULT_PASS);
    }

    /**
     * Test verify the parameter required for create InputDocument
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document';
        $response = $this->api('POST', $url, []);

        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Test verify the constants required for create InputDocument
     */
    public function testNotCreatedForConstantsParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document';
        $response = $this->api('POST', $url, [
            'title' => 'Title Test Input Document',
            'form_needed' => 'other type'
        ]);

        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Create new Input Document in process
     */
    public function testCreateInputDocument()
    {
        $faker = Faker::create();
        //Post saved correctly
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document';
        $response = $this->api('POST', $url, [
            'title' => 'Title Test Input Document',
            'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE))
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create a Input Document with an existing title
     */
    public function testNotCreateInputDocumentWithTitleExists()
    {
        factory(InputDocument::class)->create([
            'title' => 'Title Test Input Document',
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document';
        $response = $this->api('POST', $url, [
            'title' => 'Title Test Input Document',
            'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE))
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Get a list of Input Document in a project.
     */
    public function testListInputDocuments()
    {
        $total = Faker::create()->randomDigitNotNull;
        //add Input Document to process
        factory(InputDocument::class, $total)->create([
            'process_id' => $this->process->id
        ]);

        //List Input Document
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-documents';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $this->assertEquals($total, $response->original->meta->total);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));

    }

    /**
     * Get a list of input Document with parameters
     */
    public function testListInputDocumentWithQueryParameter()
    {
        $title = 'search Title Input document';
        factory(InputDocument::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List input Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-documents?' . $query;
        $response = $this->api('GET', $url);
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
     * Get a Input Document of a project.
     */
    public function testGetInputDocument()
    {
        //load InputDocument
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->create([
                'process_id' => $this->process->id
            ])->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * The InputDocument not belongs to process.
     */
    public function testGetInputDocumentNotBelongToProcess()
    {
        //load Input Document
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->create()->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Parameters required for update of input Document
     */
    public function testUpdateInputDocumentParametersRequired()
    {
        $faker = Faker::create();
        //The post must have the required parameters
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->create([
                'process_id' => $this->process->id
            ])->uid;
        $response = $this->api('PUT', $url, [
            'title' => '',
            'description' => $faker->sentence(6),
            'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
            'original' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
            'published' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
            'versioning' => $faker->randomElement([0, 1]),
            'tags' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE),
        ]);
        //Validate the answer is incorrect
        $response->assertStatus(422);
    }

    /**
     * Update Input Document in process
     */
    public function testUpdateInputDocument()
    {
        $faker = Faker::create();
        //Post saved success
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->create([
                'process_id' => $this->process->id
            ])->uid;
        $response = $this->api('PUT', $url, [
            'title' => $faker->sentence(2),
            'description' => $faker->sentence(6),
            'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
            'original' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
            'published' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
            'versioning' => $faker->randomElement([0, 1]),
            'tags' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE),
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Input Document in process
     */
    public function testDeleteInputDocument()
    {
        //Remove InputDocument
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * The Input Document does not exist in process
     */
    public function testDeleteInputDocumentNotExist()
    {
        //InputDocument not exist
        $url = self::API_TEST_INPUT_DOCUMENT . $this->process->uid . '/input-document/' . factory(InputDocument::class)->make()->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
