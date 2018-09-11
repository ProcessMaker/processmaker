<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Form;
use ProcessMaker\Models\User;
use Tests\TestCase;

class FormManagerTest extends TestCase
{
    use DatabaseTransactions;

    const API_TEST_FORM = '/api/1.0/forms';
    const DEFAULT_PASS = 'password';

    /**
     * @var User
     */
    protected $user;

    const STRUCTURE = [
        'title',
        'description',
        'config',
    ];

    /**
     * Create user and process
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
        ]);
    }

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_FORM;
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, []);

        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create form successfully
     */
    public function testCreateForm()
    {
        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_FORM;
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => 'Title Form',
            'description' => $faker->sentence(10)
        ]);

        $response->assertStatus(201);
    }


    /**
     * Can not create a form with an existing title
     */
    public function testNotCreateFormWithTitleExists()
    {
        factory(Form::class)->create([
            'title' => 'Title Form',
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_FORM;
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => 'Title Form',
            'description' => $faker->sentence(10)
        ]);
        $response->assertStatus(422);
        var_dump($response);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Form in process without query parameters.
     */
    public function testListForm()
    {
        //add Form to process
        $faker = Faker::create();
        factory(Form::class, 10)->create();

        //List Form
        $url = self::API_TEST_FORM;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $this->assertEquals(10, $response->original->meta->total);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a list of Form with parameters
     */
    public function testListFormWithQueryParameter()
    {
        $title = 'search Title Form';
        factory(Form::class)->create([
            'title' => $title,
        ]);

        //List Form with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_FORM . $query;
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
     * Get a Form of a process.
     */
    public function testGetForm()
    {
        //load Form
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create([
                'config' => [
                    'field' => 'field 1',
                    'field 2' => [
                        'data1' => 'text',
                        'data2' => 'text 2'
                    ]
                ]
            ])->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Update Form parameter are required
     */
    public function testUpdateFormParametersRequired()
    {
        //Post should have the parameter title
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create()->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => '',
            'description' => ''
        ]);
        //Validate the answer is incorrect
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Update Form in process successfully
     */
    public function testUpdateForm()
    {
        //Post saved success
        $faker = Faker::create();
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create()->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => $faker->sentence(2),
            'description' => $faker->sentence(5),
            'config' => '',
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Update Form with same title
     */
    public function testUpdateSameTitleForm()
    {
        //Post saved success
        $faker = Faker::create();
        $title = 'Some title';
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create([
            'title' => $title,
        ])->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => $title,
            'description' => $faker->sentence(5),
            'config' => '',
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Form in process
     */
    public function testDeleteForm()
    {
        //Remove Form
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create()->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Form in process
     */
    public function testDeleteFormNotExist()
    {
        //form not exist
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->make()->uuid_text;
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(405);
    }

}
