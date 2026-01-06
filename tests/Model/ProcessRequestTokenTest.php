<?php

namespace Tests\Model;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use stdClass;
use Tests\TestCase;

class ProcessRequestTokenTest extends TestCase
{
    public function testSetStagePropertiesInRecord()
    {
        // Create a partial mock of the token
        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getInstance'])
            ->getMock();

        // Fake BPMN XML with a sequenceFlow and pm:config
        $bpmnXml = <<<'XML'
                    <bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:pm="http://processmaker.com/BPMN/Configuration">
                        <bpmn:sequenceFlow id="flow_1" targetRef="element_123" pm:config='{"stage": {"id": 7, "name": "Review"}}'/>
                    </bpmn:definitions>
                    XML;

        // Simulate getInstance returning a process with BPMN XML
        $instance = new stdClass();
        $instance->process = new stdClass();
        $instance->process->bpmn = $bpmnXml;

        $token->method('getInstance')->willReturn($instance);
        $token->element_id = 'element_123';

        // Act
        $token->setStagePropertiesInRecord();

        // Assert
        $this->assertEquals(7, $token->stage_id);
        $this->assertEquals('Review', $token->stage_name);
    }

    /**
     * Test getUsersFromProcessVariable with direct users only
     */
    public function testGetUsersFromProcessVariableWithDirectUsers()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Create users
        $user1 = User::factory()->create(['status' => 'ACTIVE']);
        $user2 = User::factory()->create(['status' => 'ACTIVE']);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return 'assigned_users_var';
                }
                if ($key === 'assignedGroups') {
                    return null;
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Form data with direct users
        $formData = [
            'assigned_users_var' => [$user1->id, $user2->id],
        ];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains($user1->id, $result);
        $this->assertContains($user2->id, $result);
    }

    /**
     * Test getUsersFromProcessVariable with groups only
     */
    public function testGetUsersFromProcessVariableWithGroups()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Create groups and users
        $group1 = Group::factory()->create(['status' => 'ACTIVE']);
        $group2 = Group::factory()->create(['status' => 'ACTIVE']);
        $user1 = User::factory()->create(['status' => 'ACTIVE']);
        $user2 = User::factory()->create(['status' => 'ACTIVE']);
        $user3 = User::factory()->create(['status' => 'ACTIVE']);

        // Add users to groups
        GroupMember::factory()->create([
            'group_id' => $group1->id,
            'member_id' => $user1->id,
            'member_type' => User::class,
        ]);
        GroupMember::factory()->create([
            'group_id' => $group1->id,
            'member_id' => $user2->id,
            'member_type' => User::class,
        ]);
        GroupMember::factory()->create([
            'group_id' => $group2->id,
            'member_id' => $user3->id,
            'member_type' => User::class,
        ]);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return null;
                }
                if ($key === 'assignedGroups') {
                    return 'assigned_groups_var';
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Form data with groups
        $formData = [
            'assigned_groups_var' => [$group1->id, $group2->id],
        ];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains($user1->id, $result);
        $this->assertContains($user2->id, $result);
        $this->assertContains($user3->id, $result);
    }

    /**
     * Test getUsersFromProcessVariable with users and groups combined
     */
    public function testGetUsersFromProcessVariableWithUsersAndGroups()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Create users
        $directUser = User::factory()->create(['status' => 'ACTIVE']);

        // Create group with users
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        $groupUser = User::factory()->create(['status' => 'ACTIVE']);

        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $groupUser->id,
            'member_type' => User::class,
        ]);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return 'assigned_users_var';
                }
                if ($key === 'assignedGroups') {
                    return 'assigned_groups_var';
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Form data with both users and groups
        $formData = [
            'assigned_users_var' => [$directUser->id],
            'assigned_groups_var' => [$group->id],
        ];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains($directUser->id, $result);
        $this->assertContains($groupUser->id, $result);
    }

    /**
     * Test getUsersFromProcessVariable with empty form data
     */
    public function testGetUsersFromProcessVariableWithEmptyFormData()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return 'assigned_users_var';
                }
                if ($key === 'assignedGroups') {
                    return 'assigned_groups_var';
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Empty form data
        $formData = [];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getUsersFromProcessVariable with non-array values (should be ignored)
     */
    public function testGetUsersFromProcessVariableWithNonArrayValues()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return 'assigned_users_var';
                }
                if ($key === 'assignedGroups') {
                    return 'assigned_groups_var';
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Form data with non-array values
        $formData = [
            'assigned_users_var' => 'not_an_array',
            'assigned_groups_var' => 123,
        ];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert - should return empty array since values are not arrays
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getUsersFromProcessVariable filters invalid user IDs
     */
    public function testGetUsersFromProcessVariableFiltersInvalidIds()
    {
        // Create process and token
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create(['process_id' => $process->id]);

        // Create valid user
        $validUser = User::factory()->create(['status' => 'ACTIVE']);

        // Mock the BPMN definition and activity
        $activity = $this->createMock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $activity->method('getProperty')
            ->willReturnCallback(function ($key, $default) {
                if ($key === 'assignedUsers') {
                    return 'assigned_users_var';
                }
                if ($key === 'assignedGroups') {
                    return null;
                }

                return $default;
            });

        $bpmnDefinition = $this->createMock(\ProcessMaker\Nayra\Storage\BpmnElement::class);
        $bpmnDefinition->method('getBpmnElementInstance')
            ->willReturn($activity);

        $token = $this->getMockBuilder(ProcessRequestToken::class)
            ->onlyMethods(['getBpmnDefinition'])
            ->getMock();

        $token->process_id = $process->id;
        $token->process_request_id = $request->id;
        $token->element_id = 'task_1';
        $token->process = $process;

        $token->expects($this->atLeastOnce())
            ->method('getBpmnDefinition')
            ->willReturn($bpmnDefinition);

        // Form data with valid and invalid IDs
        $formData = [
            'assigned_users_var' => [
                $validUser->id,
                null,
                '',
                0,
                -1,
                'invalid_string',
            ],
        ];

        // Act
        $result = $token->getUsersFromProcessVariable($formData);

        // Assert - should only contain valid user ID
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertContains($validUser->id, $result);
        $this->assertNotContains(null, $result);
        $this->assertNotContains(0, $result);
        $this->assertNotContains(-1, $result);
    }
}
