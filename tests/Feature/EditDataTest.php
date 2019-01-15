<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use PermissionSeeder;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Test edit data
 */
class EditDataTest extends TestCase
{

    use RequestHelper;

    protected function setUp()
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

        // Seed the permissions
        (new PermissionSeeder)->run($this->admin);

        // Add create request permission
        $permission = Permission::byGuardName('requests.create');
        factory(PermissionAssignment::class)->create([
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id,
            'permission_id' => $permission->id,
        ]);
        factory(PermissionAssignment::class)->create([
            'assignable_type' => User::class,
            'assignable_id' => $this->admin->id,
            'permission_id' => $permission->id,
        ]);
        $this->admin->refresh();
        $this->user->refresh();
        $this->flushSession();
    }

    /**
     * Assign the required permission to the user and group.
     *
     */
    private function assignPermissions()
    {
        // Add edit data permission
        $permission = Permission::byGuardName('requests.edit_data');
        // Assign to user
        factory(PermissionAssignment::class)->create([
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id,
            'permission_id' => $permission->id,
        ]);
        // Assign to group
        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_id' => $this->group->id,
            'permission_id' => $permission->id,
        ]);
        $this->user->refresh();
        $this->flushSession();
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
     * Test edit data with permissions
     */
    public function testEditDataWithPermissions()
    {
        $this->actingAs($this->user);
        $this->assignPermissions();
        $this->assertTrue($this->user->hasPermission('requests.edit_data'));

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Summary');
        //$response->assertSee('Data');
    }

    /**
     * Test edit data without permissions
     */
    public function testEditDataWithoutPermissions()
    {
        $this->actingAs($this->user);
        $this->assertFalse($this->user->hasPermission('requests.edit_data'));

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Summary');
        $response->assertDontSee('Data');
    }

    /**
     * Test edit data with admin user
     */
    public function testEditDataWithAdmin()
    {
        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->hasPermission('requests.edit_data'));

        $process = $this->createSingleTaskProcessUserAssignment($this->user);
        $request = $this->startProcess($process, 'StartEventUID');

        $response = $this->call('GET', 'requests/' . $request->id);
        $response->assertStatus(200);
        $response->assertViewIs('requests.show');
        $response->assertSee('Summary');
        //$response->assertSee('Data');
    }
}
