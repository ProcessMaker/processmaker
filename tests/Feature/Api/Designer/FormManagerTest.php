<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class FormManagerTest extends ApiTestCase
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
        'process_id',
        'title',
        'description',
        'content',
        'label'
    ];

    /**
     * Create user and process
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::where('code', Role::PROCESSMAKER_ADMIN)->first()->id
        ]);

        $this->process = factory(Process::class)->create([
            'creator_user_id' => $this->user->id
        ]);
    }

    /**
     * Test verify the parameter required for create form
     */
    public function TestNotCreatedForParameterRequired(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        //Post should have the parameter required
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, []);
        //validating the answer is an error
        $response->assertStatus(422);
    }

    /**
     * Create new Form correctly
     */
    public function testCreateForm(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        //Post saved correctly
        $faker = Faker::create();
        $data['title'] = 'Title Form';
        $data['description'] = $faker->sentence(10);

        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create a form with an existing title
     */
    public function testNotCreateFormWithTitleExists(): Void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        //Post title duplicated
        $faker = Faker::create();
        $data['title'] = 'Title Form';
        $data['description'] = $faker->sentence(10);

        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(422);
    }

    /**
     * Test copy/import should receive a array copy_import and fields process_uid and form_uid are required.
     */
    public function testCopyImportFormWithErrorParameters(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        $data = [
            'copy_import' => 'test'
        ];
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(422);
    }

    /**
     * Test copy/import the process not exists
     */
    public function testCopyImportFormProcessNotExists(): void
    {
        //Process not exist
        $data['copy_import'] = [
            'process_uid' => 'processNotExists',
            'form_uid' => factory(Form::class)->create()->uid
        ];
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);
    }

    /**
     * Test copy/import the Form not exists
     */
    public function testCopyImportFormNotExists(): void
    {
        //Process not exist
        $data['copy_import'] = [
            'process_uid' => factory(Process::class)->create()->uid,
            'form_uid' => 'formNotExists'
        ];
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);
    }

    /**
     * Test copy/import the Form not exists
     */
    public function testCopyImportNotBelongProcess(): void
    {
        //Process not exist
        $data['copy_import'] = [
            'process_uid' => factory(Process::class)->create()->uid,
            'form_uid' => factory(Form::class)->create()->uid
        ];
        $url = self::API_TEST_FORM . $this->process->uid . '/form';
        $response = $this->api('POST', $url, $data);
        $response->assertStatus(404);
    }

    /**
     * Copy/import Form in process successfully
     */
    public function testCopyImportForm(): void
    {
        $faker = Faker::create();
        $data['title'] = $faker->sentence(3);
        $data['description'] = $faker->sentence(10);

        $process = factory(Process::)

        $data['copy_import'] = [
            'process_uid' => $this->process->uid,
            'uid' => $Form->uid
        ];
        $process = factory(Process::class)->create();

        $url = self::API_TEST_FORM . $process->uid->toString() . '/form';
        $response = $this->api('POST', $url, $data);
        //validating the answer is correct.
        $response->assertStatus(201);
        $FormCreated = $response->original;
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }



    /**
     * Get a list of Form in a project.
     *
     * @depends testCreateForm
     */
    public function testListForm(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        //add Form to process
        factory(Form::class, 10)->create([
            'process_id' => $this->process->id
        ]);

        //List Form
        $url = self::API_TEST_FORM . $this->process->uid . '/forms';
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify count of data
        $this->assertEquals(10, $response->original->meta->total);

        //verify structure paginate
        $response->assertJsonStructure([
            'data',
            'meta',
        ]);
    }

    /**
     * Get a list of Form with parameters
     */
    public function testListFormWithQueryParameter(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);
        $title = 'search Title Form';

        $form = factory(Form::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List Document with filter option
        $query = '?current_page=1&per_page=5&sort_by=description&sort_order=DESC';
        $query .= '&filter=' . urlencode($title);
        $url = self::API_TEST_FORM . $this->process->uid . '/forms';
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
        $this->assertEquals(5, $response->original->meta->per_page);
        $this->assertEquals(1, $response->original->meta->current_page);
        $this->assertEquals(1, $response->original->meta->total_pages);
        $this->assertEquals($title, $response->original->meta->filter);
        $this->assertEquals('description', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

    /**
     * Get a Form of a project.
     */
    public function testGetForm(): void
    {

        $this->auth($this->user->username, self::DEFAULT_PASS);

        $form = factory(Form::class)->create([
            'process_id' => $this->process->id
        ]);

        //load Form
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . $form->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Update Form parameter are required
     */
    public function testUpdateFormParametersRequired(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        $form = factory(Form::class)->create([
            'process_id' => $this->process->id
        ]);

        $data = [
            'title' => '',
            'description' => ''
        ];
        //Post should have the parameter tri_title
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . $form->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is incorrect
        $response->assertStatus(422);
    }

    /**
     * Update Form in process successfully
     */
    public function testUpdateForm(): void
    {
        //Post saved success
        $faker = Faker::create();
        $data['title'] = $faker->sentence(2);
        $data['description'] = $faker->sentence(5);
        $data['content'] = '';

        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . $Form->uid;
        $response = $this->api('PUT', $url, $data);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Form in process
     */
    public function testDeleteForm(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        $form = factory(Form::class)->create([
            'process_id' => $this->process->id
        ]);
        //Remove Form
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . $form->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Form in process
     */
    public function testDeleteFormNotExist(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

        $form = factory(Form::class)->create();
        //form not exist
        $url = self::API_TEST_FORM . $this->process->uid . '/form/' . $form->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
