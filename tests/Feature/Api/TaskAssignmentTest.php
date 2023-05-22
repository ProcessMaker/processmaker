<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    use ProcessTestingTrait;
    use RequestHelper;

    const API_TEST_URL = '/task_assignments';

    const STRUCTURE = [
        'id',
        'process_id',
        'process_task_id',
        'assignment_id',
        'assignment_type',
        'updated_at',
        'created_at',
    ];

    /**
     * Test to verify the parameters that are required to create an assignment
     */
    public function testNotCreatedForParameterRequired()
    {
        //Post should have the parameter required
        $response = $this->apiCall('POST', self::API_TEST_URL, []);

        //Validate the header status code
        $response->assertStatus(422);
        $this->assertArrayHasKey('message', $response->json());
    }

    /**
     * Create new task assignment type user successfully
     */
    public function testCreateTaskAssignmentUser()
    {
        $process = Process::factory()->create();
        $task_uid = Faker::create()->uuid;
        $user = User::factory()->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $user->id,
            'assignment_type' => 'ProcessMaker\Models\User',
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved
        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();

        $this->assertEquals($user->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('ProcessMaker\Models\User', $assignment->assignment_type);
    }

    /**
     * Create new task assignment type Group successfully
     */
    public function testCreateGroupMembershipForGroup()
    {
        $process = Process::factory()->create();
        $task_uid = Faker::create()->uuid;
        $group = Group::factory()->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $group->id,
            'assignment_type' => 'ProcessMaker\Models\Group',
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved
        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();

        $this->assertEquals($group->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('ProcessMaker\Models\Group', $assignment->assignment_type);
    }

    /**
     * Update task assignment successfully
     */
    public function testUpdateTaskAssignment()
    {
        // Create an task assignment
        $processTaskAssignment = ProcessTaskAssignment::factory()->create();
        $processTaskAssignment->fresh();
        $process = Process::factory()->create();
        $task_uid = Faker::create()->uuid;
        $group = Group::factory()->create();

        $response = $this->apiCall('PUT', self::API_TEST_URL . '/' . $processTaskAssignment->id, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $group->id,
            'assignment_type' => 'ProcessMaker\Models\Group',
        ]);

        $response->assertStatus(200);

        $data['id'] = $processTaskAssignment->id;
        $this->assertDatabaseHas('process_task_assignments', $data);

        //evaluate response
        $this->assertEquals($group->id, $response->json(['assignment_id']));
        $this->assertEquals($task_uid, $response->json(['process_task_id']));
        $this->assertEquals('ProcessMaker\Models\Group', $response->json(['assignment_type']));

        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();
        $this->assertEquals($group->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('ProcessMaker\Models\Group', $assignment->assignment_type);
    }

    /**
     * Invalid user assignment is reassigned to the process manager
     */
    public function testInvalidUserAssignmentReassignToProcessManager()
    {
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/InvalidUserAssignment.bpmn'),
        ]);
        $process->manager_id = User::factory()->create()->id;
        $process->save();
        $instance = $this->startProcess($process, 'node_1');
        $this->assertEquals($process->manager_id, $instance->tokens()->where('status', 'ACTIVE')->first()->user_id);
    }

    /**
     * Invalid group assignment (empty group) is catch and reassigned to the process manager
     */
    public function testEmptyGroupAssignmentReassignToProcessManager()
    {
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/InvalidGroupAssignment.bpmn'),
        ]);
        $process->manager_id = User::factory()->create()->id;
        $process->save();
        $group = Group::factory()->create([
            'id' => 100,
        ]);
        $this->assertEquals(0, $group->groupMembers()->count());
        $instance = $this->startProcess($process, 'node_1');
        $this->assertEquals($process->manager_id, $instance->tokens()->where('status', 'ACTIVE')->first()->user_id);
    }

    /**
     * Invalid group assignment (group does not exists) is catch and reassigned to the process manager
     */
    public function testInvalidGroupAssignmentReassignToProcessManager()
    {
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/InvalidGroupAssignment.bpmn'),
        ]);
        $process->manager_id = User::factory()->create()->id;
        $process->save();
        $instance = $this->startProcess($process, 'node_1');
        $this->assertEquals($process->manager_id, $instance->tokens()->where('status', 'ACTIVE')->first()->user_id);
    }

    /**
     * Invalid previous users assignment is catch and reassigned to the process manager
     */
    public function testInvalidPreviousUsersAssignmentReassignToProcessManager()
    {
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/InvalidPreviousUserAssignment.bpmn'),
        ]);
        $process->manager_id = User::factory()->create()->id;
        $process->save();
        $instance = $this->startProcess($process, 'node_1');
        $this->assertEquals($process->manager_id, $instance->tokens()->where('status', 'ACTIVE')->first()->user_id);
    }

    /**
     * Invalid user by ID assignment is catch and reassigned to the process manager
     */
    public function testInvalidUserByIDAssignmentReassignToProcessManager()
    {
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/InvalidUserByIDAssignment.bpmn'),
        ]);
        $process->manager_id = User::factory()->create()->id;
        $process->save();
        $instance = $this->startProcess($process, 'node_1');
        $this->assertEquals($process->manager_id, $instance->tokens()->where('status', 'ACTIVE')->first()->user_id);
    }
}
