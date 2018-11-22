<?php

namespace Tests\Feature\Api;

use Faker\Factory as Faker;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TaskAssignmentTest extends TestCase
{

    use RequestHelper;

    const API_TEST_URL = '/task_assignments';

    const STRUCTURE = [
        'id',
        'process_id',
        'process_task_id',
        'assignment_id',
        'assignment_type',
        'updated_at',
        'created_at'
    ];

    /**
     * Test verify the parameter required for create form
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
        $process = factory(Process::class)->create();
        $task_uid = Faker::create()->uuid;
        $user = factory(User::class)->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $user->id,
            'assignment_type' => 'USER'
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved
        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();

        $this->assertEquals($user->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('USER', $assignment->assignment_type);
    }

    /**
     * Create new task assignment type Group successfully
     */
    public function testCreateGroupMembershipForGroup()
    {
        $process = factory(Process::class)->create();
        $task_uid = Faker::create()->uuid;
        $group = factory(Group::class)->create();

        $response = $this->apiCall('POST', self::API_TEST_URL, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $group->id,
            'assignment_type' => 'GROUP'
        ]);

        //Validate the header status code
        $response->assertStatus(201);

        // make sure it saved
        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();

        $this->assertEquals($group->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('GROUP', $assignment->assignment_type);
    }

    /**
     * Update task assignment successfully
     */
    public function testUpdateTaskAssignment()
    {
        // Create an task assignment
        $processTaskAssignment = factory(ProcessTaskAssignment::class)->create();
        $processTaskAssignment->fresh();
        $process = factory(Process::class)->create();
        $task_uid = Faker::create()->uuid;
        $group = factory(Group::class)->create();

        $response = $this->apiCall('PUT', self::API_TEST_URL . '/' . $processTaskAssignment->id, [
            'process_id' => $process->id,
            'process_task_id' => $task_uid,
            'assignment_id' => $group->id,
            'assignment_type' => 'GROUP'
        ]);

        $response->assertStatus(200);

        $data['id'] = $processTaskAssignment->id;
        $this->assertDatabaseHas('process_task_assignments', $data);

        //evaluate response
        $this->assertEquals($group->id, $response->json(['assignment_id']));
        $this->assertEquals($task_uid, $response->json(['process_task_id']));
        $this->assertEquals('GROUP', $response->json(['assignment_type']));

        $assignment = ProcessTaskAssignment::where('process_id', $process->id)->first();
        $this->assertEquals($group->id, $assignment->assignment_id);
        $this->assertEquals($task_uid, $assignment->process_task_id);
        $this->assertEquals('GROUP', $assignment->assignment_type);
    }
}
