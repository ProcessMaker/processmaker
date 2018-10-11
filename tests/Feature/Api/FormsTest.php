<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Form;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class FormsTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    const API_TEST_FORM = '/api/1.0/forms';

    const STRUCTURE = [
        'title',
        'description',
        'config',
    ];

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_FORM;
        $response = $this->apiCall('POST', $url, []);

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
        $response = $this->apiCall('POST', $url, [
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
        $response = $this->apiCall('POST', $url, [
            'title' => 'Title Form',
            'description' => $faker->sentence(10)
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Get a list of Form in process without query parameters.
     */
    public function testListForm()
    {
        Form::query()->delete();
        //add Form to process
        $faker = Faker::create();
        factory(Form::class, 10)->create();

        //List Form
        $url = self::API_TEST_FORM;
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $json = $response->json();
        $this->assertEquals(10, $json['meta']['total']);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
        $this->assertArrayNotHasKey('links', $json);

        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
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
        $response = $this->apiCall('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);

        $json = $response->json();

        //verify response in meta
        $this->assertEquals(1, $json['meta']['total']);
        $this->assertEquals($perPage, $json['meta']['per_page']);
        $this->assertEquals(1, $json['meta']['current_page']);

        $this->assertEquals($title, $json['meta']['filter']);
        $this->assertEquals('description', $json['meta']['sort_by']);
        $this->assertEquals('DESC', $json['meta']['sort_order']);
        //verify structure of model
        $response->assertJsonStructure(['*' => self::STRUCTURE], $json['data']);
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
        $response = $this->apiCall('GET', $url);
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
        $response = $this->apiCall('PUT', $url, [
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
        $response = $this->apiCall('PUT', $url, [
            'title' => 'FormTitleTest',
            'description' => $faker->sentence(5),
            'config' => '',
        ]);
        //Validate the answer is correct
        $response->assertStatus(204);
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
        $response = $this->apiCall('PUT', $url, [
            'title' => $title,
            'description' => $faker->sentence(5),
            'config' => '',
        ]);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Form in process
     */
    public function testDeleteForm()
    {
        //Remove Form
        $url = self::API_TEST_FORM . '/' . factory(Form::class)->create()->uuid_text;
        $response = $this->apiCall('DELETE', $url);
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
        $response = $this->apiCall('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(405);
    }

}
