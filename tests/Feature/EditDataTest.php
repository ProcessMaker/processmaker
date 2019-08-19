<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use PermissionSeeder;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\TestCase;

/**
 * Test edit data
 */
class EditDataTest extends TestCase
{
    use RequestHelper;
    
    protected function setUp(): void
    {
        parent::setUp();

        // Creates an admin user
        $this->admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        // Creates an user
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Create a group
        $this->group = factory(Group::class)->create(['name' => 'group']);
        factory(GroupMember::class)->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id' => $this->group->id,
        ]);

        //Run the permission seeder
        (new PermissionSeeder)->run();

        // Reboot our AuthServiceProvider. This is necessary so that it can
        // pick up the new permissions and setup gates for each of them.
        $asp = new AuthServiceProvider(app());
        $asp->boot();
    }

    /**
     * Assign the required permission to the user and group.
     *
     */
    private function assignPermissions(Process $process)
    {
        $this->addProcessPermission($process, [$this->user->id], [$this->group->id]);
        $this->user->refresh();
        $this->flushSession();
    }

    /**
     * Add edit data process permission.
     *
     * @param Process $process
     * @param array $users
     * @param array $groups
     */
    private function addProcessPermission(Process $process, array $users, array $groups)
    {
        //Adding method to users array
        $editDataUsers = [];
        foreach ($users as $item) {
            $editDataUsers[$item] = ['method' => 'EDIT_DATA'];
        }

        //Adding method to groups array            
        $editDataGroups = [];
        foreach ($groups as $item) {
            $editDataGroups[$item] = ['method' => 'EDIT_DATA'];
        }

        //Syncing users and groups that can cancel this process            
        $process->usersCanEditData()->sync($editDataUsers,
            ['method' => 'EDIT_DATA']);
        $process->groupsCanEditData()->sync($editDataGroups,
            ['method' => 'EDIT_DATA']);
    }

    /**
     * Create new task assignment type user successfully
     *
     * @param User $userAssigned
     *
     * @return \ProcessMaker\Models\Process
     */
    private function createSingleTaskProcessUserAssignment(User $userAssigned)
    {
        // Create a new process
        $process = factory(Process::class)->create();

        // Load a single task process
        $process->bpmn = Process::getProcessTemplate('SingleTask.bpmn');

        // Create user to be assigned to the task
        $task_uid = 'UserTaskUID';
        $definitions = $process->getDefinitions();
        $task = $definitions->findElementById($task_uid);
        $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS,
            'assignment', 'user');
        $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS,
            'assignedUsers', $userAssigned->id);
        $process->bpmn = $definitions->saveXml();

        // When save the process creates the assignments
        $process->save();
        return $process;
    }

    /**
     * Start a process
     *
     * @param \ProcessMaker\Models\Process $process
     * @param string $startEvent
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequest
     */
    private function startProcess($process, $startEvent, $data = [])
    {
        // Trigger the start event
        $event = $process->getDefinitions()->getEvent($startEvent);
        return WorkflowManager::triggerStartEvent($process, $event, $data);
    }

    /**
     * Complete task
     *
     * @param \ProcessMaker\Models\ProcessRequestToken $task
     * @param array $data
     *
     * @return \ProcessMaker\Models\ProcessRequestToken
     */
    private function completeTask(ProcessRequestToken $task, $data = [])
    {
        //Call the manager to trigger the start event
        $process = $task->process;
        $instance = $task->processRequest;
        WorkflowManager::completeTask($process, $instance, $task, $data);
        return $task->refresh();
    }

    /**
     * Verify edit data disabled without permissions
     */
    public function testEditDataWithoutPermissions()
    {
        $this->actingAs($this->user);
        $this->assertFalse($this->user->hasPermission('requests.edit_data'));

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();
        $this->completeTask($task);

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Summary');
        $response->assertDontSee('<!-- data edit -->');
    }

    /**
     * Test edit data with admin user
     */
    public function testEditDataWithAdmin()
    {
        $this->actingAs($this->admin);

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();
        $this->completeTask($task);

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Summary');
        $response->assertSee('<!-- data edit -->');
    }

    /**
     * Verify edit data disabled without permissions from "In progress" task
     */
    public function testEditDataTaskViewWithoutPermissions()
    {
        $this->actingAs($this->user);

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();

        $response = $this->call('GET', 'tasks/' . $task->id . '/edit');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.edit');
        $response->assertDontSee('<!-- data edit -->');
    }

    /**
     * Test edit data with permissions from "In progress" task
     */
    public function testEditDataWithPermissions()
    {
        $this->actingAs($this->user);

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $this->assignPermissions($process);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();

        $response = $this->call('GET', 'tasks/' . $task->id . '/edit');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.edit');
        $response->assertSee('Form');
        $response->assertSee('<!-- data edit -->');
    }

    /**
     * Test edit data without global permissions
     */
    public function testEditDataWithoutGlobalPermissions()
    {
        //Create process, request, and task
        $this->actingAs($this->user);
        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();

        //Perform web call and make assertions
        $response = $this->webCall('GET', 'tasks/' . $task->id . '/edit');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.edit');
        $response->assertDontSee('<!-- data edit -->');
    }

    /**
     * Test edit data with global permissions
     */
    public function testEditDataWithGlobalPermissions()
    {
        //Create process, request, and task
        $this->actingAs($this->user);
        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();
        
        //Assign global edit task permission to user
        $this->user->permissions()->attach(Permission::byName('edit-task_data')->id);
        $this->user->refresh();
        $this->flushSession();
        
        //Perform web call and make assertions
        $response = $this->webCall('GET', 'tasks/' . $task->id . '/edit');
        $response->assertStatus(200);
        $response->assertViewIs('tasks.edit');
        $response->assertSee('<!-- data edit -->');
    }

    /**
     * Verify Request screen edit data disabled with permissions but request is "Active"
     */
    public function testEditDataRequestActive()
    {
        $this->actingAs($this->user);

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $this->assignPermissions($process);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Completed');
        $response->assertSee('Summary');
        $response->assertDontSee('<!-- data edit -->');
    }

    /**
     * Test Request screen edit data with permissions and request is "Completed"
     */
    public function testEditDataRequestCompleted()
    {
        $this->actingAs($this->user);

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $this->assignPermissions($process);
        $request = $this->startProcess($process, 'StartEventUID');
        $task = $request->tokens()->where('element_id', 'UserTaskUID')->first();
        $this->completeTask($task);

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Completed');
        $response->assertSee('Summary');
        $response->assertSee('<!-- data edit -->');
    }
}
