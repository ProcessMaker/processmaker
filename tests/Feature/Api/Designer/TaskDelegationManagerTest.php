<?php

namespace Tests\Feature\Api\Designer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        'data',
        'app_overdue_percentage',
        'user',
        'task',
    ];

    /**
     *  Init data user and process
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

        $this->task = factory(Task::class)->create([
            'process_id' => $this->process->id
        ]);
    }

    /**
     * Test validate structure table
     */
    public function testStructureTable(): void
    {
        $db = DB::connection()->getSchemaBuilder()->getColumnListing('delegations');
        $structure = [
            'id',
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
            'data',
            'app_overdue_percentage',
            'user_id',
        ];
        sort($db);
        sort($structure);
        $this->assertEquals(json_encode($structure), json_encode($db));
    }

    /**
     * Get a Task and delegations.
     */
    public function testGetDelegationTask(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);

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
    public function testGetListDelegationsTask(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);
        //add delegations
        factory(Delegation::class, 11)->create([
            'task_id' => $this->task->id
        ]);
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
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

    /**
     * List Tasks with query parameters
     */
    public function testListTasksWithQueryParameter(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);
        //add Task to process
        //add delegations
        factory(Delegation::class, 11)->create([
            'task_id' => $this->task->id
        ]);
        //List Task with parameters pages and sort
        $query = '?current_page=2&per_page=5&sort_by=delegate_date&sort_order=DESC';
        $url = self::API_ROUTE_TASK . $this->task->uid . $query;
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
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

    /**
     * List Tasks with filter
     */
    public function testListTaskWithFilter(): void
    {
        $this->auth($this->user->username, self::DEFAULT_PASS);
        //add Task to process
        factory(Delegation::class)->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id
        ]);
        //List Task with filter option
        $query = '?current_page=1&per_page=5&sort_by=delegate_date&sort_order=DESC&filter=' . urlencode($this->user->firstname);
        $url = self::API_ROUTE_TASK . $this->process->uid . '/output-Tasks' . $query;
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
        $this->assertEquals($this->user->firstname, $response->original->meta->filter);
        $this->assertEquals('delegate_date', $response->original->meta->sort_by);
        $this->assertEquals('DESC', $response->original->meta->sort_order);
        //verify structure of model
        foreach ($response->json('data') as $item) {
            $response->assertJsonStructure(self::STRUCTURE, $item);
        }
    }

}