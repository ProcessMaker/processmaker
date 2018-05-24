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

    const API_ROUTE = '/api/1.0/project/';
    const DEFAULT_PASS = 'password';

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
     * Create new Input Document in process
     *
     * @return InputDocument
     */
    public function testCreateInputDocument(): InputDocument
    {
        $this->markTestSkipped('These tests need to be refactored to deal with database transactions');
        $this->initProcess();
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //The post must have the required parameters
        $url = self::API_ROUTE . self::$process->uid . '/input-document';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post should have the parameter FORM_NEEDED only types InputDocument::FORM_NEEDED_TYPE
        $faker = Faker::create();
        $data['form_needed'] = $faker->sentence(2);
        $url = self::API_ROUTE . self::$process->uid . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is an error
        $response->assertStatus(422);

        //Post saved correctly
        $data['title'] = $faker->sentence(3);
        $data['form_needed'] = $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE));
        $url = self::API_ROUTE . self::$process->uid . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $document = $response->original;
        //Check structure of response.
        $response->assertJsonStructure([
            'uid',
            'title',
            'description',
            'form_needed',
            'original',
            'published',
            'versioning',
            'destination_path',
            'tags',
        ]);

        //Duplicate titles are not allowed
        $url = self::API_ROUTE . self::$process->uid . '/input-document';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(422);
        return InputDocument::where('uid', $document->uid)->first();
    }

    /**
     * Get a list of Input Document in a project.
     *
     * @depends testCreateInputDocument
     */
    public function testListInputDocuments(): void
    {
        $this->markTestSkipped('These tests need to be refactored to deal with database transactions');
        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //add Input Document to process
        factory(InputDocument::class, 10)->create([
            'process_id' => self::$process->id
        ]);

        //List Input Document
        $url = self::API_ROUTE . self::$process->uid . '/input-documents';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $this->assertEquals(11, $response->original->meta->total);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

    }

    /**
     * Get a Input Document of a project.
     *
     * @param InputDocument $inputDocument
     *
     * @depends testCreateInputDocument
     */
    public function testGetInputDocument(InputDocument $inputDocument): void
    {
        $this->markTestSkipped('These tests need to be refactored to deal with database transactions');

        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //load InputDocument
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure([
            'uid',
            'title',
            'description',
            'form_needed',
            'original',
            'published',
            'versioning',
            'destination_path',
            'tags',
        ]);

        //InputDocument not belong to process.
        $inputDocument = factory(InputDocument::class)->create();
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is incorrect
        $response->assertStatus(404);

    }

    /**
     * Update Input Document in process
     *
     * @param InputDocument $inputDocument
     *
     * @depends testCreateInputDocument
     */
    public function testUpdateInputDocument(InputDocument $inputDocument): void
    {
        $this->markTestSkipped('These tests need to be refactored to deal with database transactions');

        $this->auth(self::$user->username, self::DEFAULT_PASS);

        $faker = Faker::create();
        $data = [
            'title' => '',
            'description' => $faker->sentence(6),
            'form_needed' => $faker->randomElement(array_keys(InputDocument::FORM_NEEDED_TYPE)),
            'original' => $faker->randomElement(InputDocument::DOC_ORIGINAL_TYPE),
            'published' => $faker->randomElement(InputDocument::DOC_PUBLISHED_TYPE),
            'versioning' => $faker->randomElement([0, 1]),
            'tags' => $faker->randomElement(InputDocument::DOC_TAGS_TYPE),
        ];
        //Post should have the parameter tri_title
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);

        //Post saved success
        $data['title'] = $faker->sentence(2);
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Input Document in process
     *
     * @param InputDocument $inputDocument
     *
     * @depends testCreateInputDocument
     */
    public function testDeleteInputDocument(InputDocument $inputDocument): void
    {
        $this->markTestSkipped('These tests need to be refactored to deal with database transactions');

        $this->auth(self::$user->username, self::DEFAULT_PASS);

        //Remove InputDocument
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);

        $inputDocument = factory(InputDocument::class)->make();

        //InputDocument not exist
        $url = self::API_ROUTE . self::$process->uid . '/input-document/' . $inputDocument->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
