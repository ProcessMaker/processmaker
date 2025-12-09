<?php

namespace Tests\Unit\ProcessMaker\Models;

use Mockery;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ProcessTest extends TestCase
{
    public function testGetConsolidatedUsers()
    {
        $process = Process::factory()->create();

        $groupA = Group::factory()->create(['name' => 'Group A', 'status' => 'ACTIVE']);
        $groupB = Group::factory()->create(['name' => 'Group B', 'status' => 'ACTIVE']);

        $groupAUser = User::factory()->create(['status' => 'ACTIVE']);
        $groupBUser = User::factory()->create(['status' => 'ACTIVE']);

        $groupA->groupMembers()->create(['member_id' => $groupAUser->id, 'member_type' => User::class]);
        $groupB->groupMembers()->create(['member_id' => $groupBUser->id, 'member_type' => User::class]);

        // Add group B to group A
        $groupA->groupMembers()->create(['member_id' => $groupB->id, 'member_type' => Group::class]);

        $users = [];
        $process->getConsolidatedUsers($groupA->id, $users);

        $this->assertEquals([$groupAUser->id, $groupBUser->id], $users);
    }

    /**
     * Test getAssignableUsersByAssignmentType with manager-only rules
     */
    public function testGetAssignableUsersByAssignmentTypeWithManagerOnlyRules()
    {
        $manager1 = User::factory()->create(['status' => 'ACTIVE']);
        $manager2 = User::factory()->create(['status' => 'ACTIVE']);

        // Create process with multiple managers (array)
        $process = Process::factory()->create([
            'properties' => ['manager_id' => [$manager1->id, $manager2->id]],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'element_id' => 'test_element',
        ]);

        // Mock getAssignmentRule to return different manager-only rules
        $managerOnlyRules = ['previous_task_assignee', 'requester', 'process_manager'];

        foreach ($managerOnlyRules as $rule) {
            $tokenMock = Mockery::mock($token)->makePartial();
            $tokenMock->shouldReceive('getAssignmentRule')->andReturn($rule);

            $result = $process->getAssignableUsersByAssignmentType($tokenMock);

            // Should return both managers
            $this->assertIsArray($result);
            $this->assertCount(2, $result);
            $this->assertContains($manager1->id, $result);
            $this->assertContains($manager2->id, $result);
        }
    }

    /**
     * Test getAssignableUsersByAssignmentType with single manager
     */
    public function testGetAssignableUsersByAssignmentTypeWithSingleManager()
    {
        $manager = User::factory()->create(['status' => 'ACTIVE']);

        // Create process with single manager (not array)
        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('process_manager');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertContains($manager->id, $result);
    }

    /**
     * Test getAssignableUsersByAssignmentType with group-based rules
     */
    public function testGetAssignableUsersByAssignmentTypeWithGroupBasedRules()
    {
        $manager = User::factory()->create(['status' => 'ACTIVE']);
        $assignableUser1 = User::factory()->create(['status' => 'ACTIVE']);
        $assignableUser2 = User::factory()->create(['status' => 'ACTIVE']);

        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $elementId = 'test_element_' . uniqid();

        // Create task assignments
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => $elementId,
            'assignment_id' => $assignableUser1->id,
            'assignment_type' => User::class,
        ]);

        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => $elementId,
            'assignment_id' => $assignableUser2->id,
            'assignment_type' => User::class,
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'element_id' => $elementId,
        ]);

        $groupBasedRules = ['user_group', 'process_variable', 'rule_expression'];

        foreach ($groupBasedRules as $rule) {
            $tokenMock = Mockery::mock($token)->makePartial();
            $tokenMock->shouldReceive('getAssignmentRule')->andReturn($rule);

            $result = $process->getAssignableUsersByAssignmentType($tokenMock);

            // Should return assignable users + manager
            $this->assertIsArray($result);
            $this->assertCount(3, $result);
            $this->assertContains($manager->id, $result);
            $this->assertContains($assignableUser1->id, $result);
            $this->assertContains($assignableUser2->id, $result);
        }
    }

    /**
     * Test getAssignableUsersByAssignmentType with empty element_id
     */
    public function testGetAssignableUsersByAssignmentTypeWithEmptyElementId()
    {
        $manager = User::factory()->create(['status' => 'ACTIVE']);

        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'element_id' => '', // Use empty string instead of null
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('user_group');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        // Should only return manager when element_id is empty
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertContains($manager->id, $result);
    }

    /**
     * Test getAssignableUsersByAssignmentType with null manager_id
     */
    public function testGetAssignableUsersByAssignmentTypeWithNullManager()
    {
        $process = Process::factory()->create([
            'properties' => ['manager_id' => null],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('process_manager');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        // Should return empty array when manager_id is null
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getAssignableUsersByAssignmentType with unknown rule
     */
    public function testGetAssignableUsersByAssignmentTypeWithUnknownRule()
    {
        $manager = User::factory()->create(['status' => 'ACTIVE']);

        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('unknown_rule');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        // Should return empty array for unknown rules
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getAssignableUsersByAssignmentType handles nested arrays correctly
     */
    public function testGetAssignableUsersByAssignmentTypeHandlesNestedArrays()
    {
        $manager1 = User::factory()->create(['status' => 'ACTIVE']);
        $manager2 = User::factory()->create(['status' => 'ACTIVE']);

        // Simulate nested array scenario (shouldn't happen but test the normalization)
        // Note: The accessor will normalize this, but we test the normalization logic
        $process = Process::factory()->create([
            'properties' => ['manager_id' => [[$manager1->id], [$manager2->id]]],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('process_manager');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        // Should flatten and return both managers
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains($manager1->id, $result);
        $this->assertContains($manager2->id, $result);
        // Verify it's a flat array (not nested)
        foreach ($result as $item) {
            $this->assertIsInt($item);
        }
    }

    /**
     * Test getAssignableUsersByAssignmentType removes duplicates
     */
    public function testGetAssignableUsersByAssignmentTypeRemovesDuplicates()
    {
        $manager = User::factory()->create(['status' => 'ACTIVE']);
        $assignableUser = User::factory()->create(['status' => 'ACTIVE']);

        $process = Process::factory()->create([
            'properties' => ['manager_id' => $manager->id],
        ]);

        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);
        $elementId = 'test_element_' . uniqid();

        // Create task assignment with manager as assignable user (duplicate scenario)
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => $elementId,
            'assignment_id' => $manager->id, // Same as manager
            'assignment_type' => User::class,
        ]);

        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => $elementId,
            'assignment_id' => $assignableUser->id,
            'assignment_type' => User::class,
        ]);

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $request->id,
            'element_id' => $elementId,
        ]);

        $tokenMock = Mockery::mock($token)->makePartial();
        $tokenMock->shouldReceive('getAssignmentRule')->andReturn('user_group');

        $result = $process->getAssignableUsersByAssignmentType($tokenMock);

        // Should return unique values (manager should appear only once)
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // manager + assignableUser (no duplicates)
        $this->assertContains($manager->id, $result);
        $this->assertContains($assignableUser->id, $result);
        // Verify no duplicates
        $this->assertEquals(count($result), count(array_unique($result)));
    }
}
