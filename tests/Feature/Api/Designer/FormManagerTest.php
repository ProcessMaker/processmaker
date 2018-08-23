<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\TestCase;

class FormManagerTest extends TestCase
{
    use DatabaseTransactions;

    const API_TEST_FORM = '/api/1.0/process/';
    const DEFAULT_PASS = 'password';

    /**
     * @var User
     */
    protected $user;
    /**
     * @var Process
     */
    protected $process;

    const STRUCTURE = [
        'uid',
        'title',
        'description',
        'content',
        'label'
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
    }

    /**
     * Test verify the parameter required for create form
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
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
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => 'Title Form',
            'description' => $faker->sentence(10)
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Test copy/import should receive a array copy_import and fields process_uid and form_uid are required.
     */
    public function testCopyImportFormWithErrorParameters()
    {
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'copy_import' => 'test'
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Test copy/import the process not exists
     */
    public function testCopyImportFormProcessNotExists()
    {
        //Process not exist
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'copy_import' => [
                'process_uid' => 'processNotExists',
                'form_uid' => factory(Form::class)->create()->uid
            ]
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Test copy/import the Form not exists
     */
    public function testCopyImportFormNotExists()
    {
        //Process not exist
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'copy_import' => [
                'process_uid' => factory(Process::class)->create()->uid,
                'form_uid' => 'formNotExists'
            ]
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json('error'));
    }

    /**
     * Test copy/import the Form not exists
     */
    public function testCopyImportNotBelongProcess()
    {
        //Process not exist
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'copy_import' => [
                'process_uid' => factory(Process::class)->create()->uid,
                'form_uid' => factory(Form::class)->create()->uid
            ]
        ]);
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Copy/import Form in process successfully
     */
    public function testCopyImportForm()
    {
        $faker = Faker::create();

        $url = self::API_TEST_FORM . factory(Process::class)->create()->uid . '/form';
        $response = $this->actingAs($this->user, 'api')->json('POST', $url, [
            'title' => $faker->sentence(3),
            'description' => $faker->sentence(10),
            'copy_import' => [
                'process_uid' => $this->process->uid,
                'form_uid' => factory(Form::class)->create([
                    'process_id' => $this->process->id
                ])->uid
            ]
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
    }

    /**
     * Get a list of Form in process without query parameters.
     */
    public function testListForm()
    {
        //add Form to process
        $faker = Faker::create();
        factory(Form::class, 10)->create([
            'process_id' => $this->process->id,
            'label' => $faker->sentences(6)
        ]);

        //List Form
        $url = self::API_TEST_FORM . $this->process->uid . '/forms';
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
            'process_id' => $this->process->id
        ]);

        //List Form with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_FORM . $this->process->uid . '/forms?' . $query;
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create([
                'process_id' => $this->process->id,
                'content' => (object)[
                    'field' => 'field 1',
                    'field 2' => (object)[
                        'data1' => 'text',
                        'data2' => 'text 2'
                    ]
                ]
            ])->uid;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Get a Form not belongs to process
     */
    public function testGetFormNotBelongToProcess()
    {
        //load Form
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create()->uid;
        $response = $this->actingAs($this->user, 'api')->json('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Update Form parameter are required
     */
    public function testUpdateFormParametersRequired()
    {
        //Post should have the parameter title
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create(['process_id' => $this->process->id])->uid;
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'title' => $faker->sentence(2),
            'description' => $faker->sentence(5),
            'content' => '',
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->actingAs($this->user, 'api')->json('PUT', $url, [
            'description' => $faker->sentence(5),
            'content' => '',
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->create(['process_id' => $this->process->id])->uid;
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
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . factory(Form::class)->make()->uid;
        $response = $this->actingAs($this->user, 'api')->json('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
