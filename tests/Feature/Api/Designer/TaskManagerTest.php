<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TaskManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_TASK = '/api/1.0/process/';
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
        'type',
        'assign_type',
        'routing_type',
        'priority_variable',
        'assign_variable',
        'group_variable',
        'is_start_task',
        'routing_screen_template',
        'timing_control_configuration',
        'script_id',
        'self_service_timeout_configuration',
        'custom_title',
        'custom_description',
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
     * Test verify the parameter required for create Task
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $url = self::API_TEST_TASK . $this->process->uid . '/task';
        $response = $this->api('POST', $url, []);

        //validating the answer is an error
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new Task correctly
     */
    public function testCreateTask()
    {
        //Post saved correctly
        $faker = Faker::create();

        $url = self::API_TEST_TASK . $this->process->uid . '/task';
        $response = $this->api('POST', $url, [
            'title' => 'Title Task',
            'description' => $faker->sentence(10),
            'type' => $faker->randomElement([Task::TYPE_NORMAL, Task::TYPE_ADHOC, Task::TYPE_SUB_PROCESS, Task::TYPE_HIDDEN, Task::TYPE_GATEWAY, Task::TYPE_WEB_ENTRY_EVENT, Task::TYPE_END_MESSAGE_EVENT, Task::TYPE_START_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT, Task::TYPE_SCRIPT_TASK, Task::TYPE_START_TIMER_EVENT, Task::TYPE_INTERMEDIATE_CATCH_TIMER_EVENT, Task::TYPE_END_EMAIL_EVENT, Task::TYPE_INTERMEDIATE_THROW_EMAIL_EVENT, Task::TYPE_SERVICE_TASK]),
            'assign_type' => $faker->randomElement([Task::ASSIGN_TYPE_BALANCED, Task::ASSIGN_TYPE_MANUAL, Task::ASSIGN_TYPE_EVALUATE, Task::ASSIGN_TYPE_REPORT_TO, Task::ASSIGN_TYPE_SELF_SERVICE, Task::ASSIGN_TYPE_STATIC_MI, Task::ASSIGN_TYPE_CANCEL_MI, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED]),
            'routing_type' => $faker->randomElement([Task::ROUTE_TYPE_NORMAL, Task::ROUTE_TYPE_FAST, Task::ROUTE_TYPE_AUTOMATIC]),
            'timing_control_configuration' => [
                'duration' => 10,
            ],
            'self_service_timeout_configuration' => [
                'self_service_timeout' => 10
            ],
        ]);
        //validating the answer is correct.
        $response->assertStatus(201);
        //Check structure of response.
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Can not create a Task with an existing title
     */
    public function testNotCreateTaskWithTitleExists()
    {
        $title = 'Title Task';
        factory(Task::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //Post title duplicated
        $faker = Faker::create();
        $url = self::API_TEST_TASK . $this->process->uid . '/task';
        $response = $this->api('POST', $url, [
            'title' => $title,
            'description' => $faker->sentence(10),
            'type' => $faker->randomElement([Task::TYPE_NORMAL, Task::TYPE_ADHOC, Task::TYPE_SUB_PROCESS, Task::TYPE_HIDDEN, Task::TYPE_GATEWAY, Task::TYPE_WEB_ENTRY_EVENT, Task::TYPE_END_MESSAGE_EVENT, Task::TYPE_START_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT, Task::TYPE_SCRIPT_TASK, Task::TYPE_START_TIMER_EVENT, Task::TYPE_INTERMEDIATE_CATCH_TIMER_EVENT, Task::TYPE_END_EMAIL_EVENT, Task::TYPE_INTERMEDIATE_THROW_EMAIL_EVENT, Task::TYPE_SERVICE_TASK]),
            'assign_type' => $faker->randomElement([Task::ASSIGN_TYPE_BALANCED, Task::ASSIGN_TYPE_MANUAL, Task::ASSIGN_TYPE_EVALUATE, Task::ASSIGN_TYPE_REPORT_TO, Task::ASSIGN_TYPE_SELF_SERVICE, Task::ASSIGN_TYPE_STATIC_MI, Task::ASSIGN_TYPE_CANCEL_MI, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED]),
            'routing_type' => $faker->randomElement([Task::ROUTE_TYPE_NORMAL, Task::ROUTE_TYPE_FAST, Task::ROUTE_TYPE_AUTOMATIC])
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }


    /**
     * Get a list of Task in process without query parameters.
     */
    public function testListTask()
    {
        //add Task to process
        factory(Task::class, 10)->create([
            'process_id' => $this->process->id
        ]);

        //List Task
        $url = self::API_TEST_TASK . $this->process->uid . '/tasks';
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

        //Verify the structure
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * Get a list of Task with parameters
     */
    public function testListTaskWithQueryParameter()
    {
        $title = 'search Title Task';
        factory(Task::class)->create([
            'title' => $title,
            'process_id' => $this->process->id
        ]);

        //List Document with filter option
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=description&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_TEST_TASK . $this->process->uid . '/tasks?' . $query;
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
     * Get a Task of a project.
     */
    public function testGetTask()
    {
        //load Task
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . factory(Task::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);

        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Task not belong to process.
     */
    public function testGetTaskNotBelongProcess()
    {
        $task = factory(Task::class)->create();
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . $task->uid;
        $response = $this->api('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * Update Task parameter are required
     */
    public function testUpdateTaskParametersRequired()
    {
        //Post should have the parameter title
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . factory(Task::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('PUT', $url, [
            'title' => '',
            'description' => ''
        ]);
        //Validate the answer is incorrect
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Update Task in process successfully
     */
    public function testUpdateTask()
    {
        //Post saved success
        $faker = Faker::create();
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . factory(Task::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('PUT', $url, [
            'title' => $faker->sentence(2),
            'description' => $faker->sentence(5),
            'type' => $faker->randomElement([Task::TYPE_NORMAL, Task::TYPE_ADHOC, Task::TYPE_SUB_PROCESS, Task::TYPE_HIDDEN, Task::TYPE_GATEWAY, Task::TYPE_WEB_ENTRY_EVENT, Task::TYPE_END_MESSAGE_EVENT, Task::TYPE_START_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_THROW_MESSAGE_EVENT, Task::TYPE_INTERMEDIATE_CATCH_MESSAGE_EVENT, Task::TYPE_SCRIPT_TASK, Task::TYPE_START_TIMER_EVENT, Task::TYPE_INTERMEDIATE_CATCH_TIMER_EVENT, Task::TYPE_END_EMAIL_EVENT, Task::TYPE_INTERMEDIATE_THROW_EMAIL_EVENT, Task::TYPE_SERVICE_TASK]),
            'assign_type' => $faker->randomElement([Task::ASSIGN_TYPE_BALANCED, Task::ASSIGN_TYPE_MANUAL, Task::ASSIGN_TYPE_EVALUATE, Task::ASSIGN_TYPE_REPORT_TO, Task::ASSIGN_TYPE_SELF_SERVICE, Task::ASSIGN_TYPE_STATIC_MI, Task::ASSIGN_TYPE_CANCEL_MI, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE, Task::ASSIGN_TYPE_MULTIPLE_INSTANCE_VALUE_BASED]),
            'routing_type' => $faker->randomElement([Task::ROUTE_TYPE_NORMAL, Task::ROUTE_TYPE_FAST, Task::ROUTE_TYPE_AUTOMATIC])
        ]);
        //Validate the answer is correct
        $response->assertStatus(200);
    }

    /**
     * Delete Task in process
     */
    public function testDeleteTask()
    {
        //Remove Task
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . factory(Task::class)->create(['process_id' => $this->process->id])->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(204);
    }

    /**
     * Delete Task in process
     */
    public function testDeleteTaskNotExist()
    {
        //task not exist
        $url = self::API_TEST_TASK . $this->process->uid . '/task/' . factory(Task::class)->make()->uid;
        $response = $this->api('DELETE', $url);
        //Validate the answer is correct
        $response->assertStatus(404);
    }

}
