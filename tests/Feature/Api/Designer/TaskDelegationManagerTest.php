<?php

namespace Tests\Feature\Api\Designer;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\Delegation;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class TaskDelegationManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const DEFAULT_PASS = 'password';

    protected $user;
    protected $process;
    protected $task;

    const API_ROUTE_TASK = '/api/1.0/tasks';
    const STRUCTURE = [
        'uid',
        'application_id',
        'index',
        'previous',
        'last_index',
        'task_id',
        'type',
        'thread',
        'thread_status',
        'priority',
        'delegate_date',
        'init_date',
        'finish_date',
        'task_due_date',
        'risk_date',
        'duration',
        'queue_duration',
        'delay_duration',
        'started',
        'finished',
        'delayed',
        'app_overdue_percentage',
        'user',
        'task',
    ];

    /**
     *  Init data user and process
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::PROCESSMAKER_ADMIN
        ]);

        $this->process = factory(Process::class)->create([
            'user_id' => $this->user->id
        ]);

        $this->task = factory(Task::class)->create([
            'process_id' => $this->process->id
        ]);

        $this->auth($this->user->username, self::DEFAULT_PASS);
    }

    /**
     * Get a Task and delegations.
     */
    public function testGetDelegationTask()
    {
        //add delegation
        factory(Delegation::class)->create([
            'task_id' => $this->task->id
        ]);

        $url = self::API_ROUTE_TASK . '/' . $this->task->uid;
        $response = $this->api('GET', $url);
        //Validate the answer is correct
        $response->assertStatus(200);
        //verify structure paginate
        $response->assertJsonStructure(self::STRUCTURE);
    }

    /**
     * Get a list of Task in a project.
     */
    public function testGetListDelegationsTask()
    {
        //add delegations
        factory(Delegation::class, 11)->create();
        //List Delegations
        $url = self::API_ROUTE_TASK;
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
        //verify structure of data
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * List Tasks with query parameters
     */
    public function testListTasksWithQueryParameter()
    {
        //add delegations
        factory(Delegation::class, 11)->create([
            'task_id' => $this->task->id
        ]);
        //List Task with parameters pages and sort
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=2&per_page=' . $perPage . '&order_by=delegate_date&order_direction=DESC';
        $url = self::API_ROUTE_TASK . $query;
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
        $this->assertEquals($perPage, $response->original->meta->per_page);
        $this->assertEquals(2, $response->original->meta->current_page);
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of data
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * List Tasks with filter user
     */
    public function testListTaskWithFilterUser()
    {
        //add delegations
        $filter = 'First name filter';
        $user = factory(User::class)->create([
            'firstname' => $filter,
            'password' => Hash::make(self::DEFAULT_PASS),
            'role_id' => Role::PROCESSMAKER_ADMIN
        ]);
        factory(Delegation::class)->create([
            'user_id' => $user->id,
            'task_id' => $this->task->id
        ]);
        factory(Delegation::class, 5)->create();
        //List Delegations with filter options
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=delegate_date&order_direction=DESC&filter=' . urlencode($filter);
        $url = self::API_ROUTE_TASK . $query;
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
        $this->assertEquals($filter, $response->original->meta->filter);
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of data
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * List Tasks with filter tasks
     */
    public function testListTaskWithFilterTask()
    {
        //add delegations
        factory(Delegation::class)->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id
        ]);
        factory(Delegation::class, 5)->create();
        //List Delegations with filter options
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=delegate_date&order_direction=DESC&filter=' . urlencode($this->task->title);
        $url = self::API_ROUTE_TASK . $query;
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
        $this->assertEquals($this->task->title, $response->original->meta->filter);
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of data
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

    /**
     * List Tasks with filter process
     */
    public function testListTaskWithFilterApplication()
    {
        //add delegations
        $title = 'Application title for search';
        $application = factory(Application::class)->create([
            'APP_TITLE' => $title
        ]);
        factory(Delegation::class)->create([
            'application_id' => $application->id,
            'user_id' => $this->user->id,
            'task_id' => $this->task->id
        ]);
        factory(Delegation::class, 5)->create();
        //List Delegations with filter options
        $perPage = Faker::create()->randomDigitNotNull;
        $query = '?page=1&per_page=' . $perPage . '&order_by=delegate_date&order_direction=DESC&filter=' . urlencode($title);
        $url = self::API_ROUTE_TASK . $query;
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
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of data
        $response->assertJsonStructure(['*' => self::STRUCTURE], $response->json('data'));
    }

}
