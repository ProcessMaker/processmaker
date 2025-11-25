<?php

namespace Tests\Unit\ProcessMaker\Services;

use InvalidArgumentException;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use ProcessMaker\Services\ConditionalRedirectService;
use Tests\TestCase;

class ConditionalRedirectServiceTest extends TestCase
{
    private ConditionalRedirectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConditionalRedirectService();
    }

    /**
     * Test the resolve method with basic conditions
     */
    public function testResolveWithBasicConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'amount <= 1000',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['amount' => 1500, 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/approval', $result['value']);
        $this->assertEquals('amount > 1000', $result['condition']);
    }

    /**
     * Test the resolve method with second condition matching
     */
    public function testResolveWithSecondConditionMatching()
    {
        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'amount <= 1000',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['amount' => 500, 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('taskList', $result['type']);
        $this->assertNull($result['value']);
        $this->assertEquals('amount <= 1000', $result['condition']);
    }

    /**
     * Test the resolve method with no conditions matching
     */
    public function testResolveWithNoConditionsMatching()
    {
        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'status == "urgent"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['amount' => 500, 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNull($result);
    }

    /**
     * Test the resolve method with string conditions
     */
    public function testResolveWithStringConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'status == "urgent"',
                'type' => 'externalURL',
                'value' => 'https://example.com/urgent'
            ],
            [
                'condition' => 'status == "normal"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['status' => 'urgent', 'priority' => 'high'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/urgent', $result['value']);
    }

    /**
     * Test the resolve method with boolean conditions
     */
    public function testResolveWithBooleanConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'isApproved == true',
                'type' => 'homepageDashboard',
                'value' => null
            ],
            [
                'condition' => 'isApproved == false',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['isApproved' => true, 'user' => 'john'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('homepageDashboard', $result['type']);
        $this->assertNull($result['value']);
    }

    /**
     * Test the resolve method with complex conditions
     */
    public function testResolveWithComplexConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000 and status == "pending"',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'amount <= 1000 or status == "urgent"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['amount' => 1500, 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/approval', $result['value']);
    }

    /**
     * Test the resolve method with empty conditional redirect array
     */
    public function testResolveWithEmptyConditionalRedirect()
    {
        $conditionalRedirect = [];
        $data = ['amount' => 1500];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNull($result);
    }

    /**
     * Test the resolve method with missing condition key
     */
    public function testResolveWithMissingConditionKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition is required');

        $conditionalRedirect = [
            [
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ]
        ];

        $data = ['amount' => 1500];

        $this->service->resolve($conditionalRedirect, $data);
    }

    /**
     * Test the resolve method with null condition
     */
    public function testResolveWithNullCondition()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition is required');

        $conditionalRedirect = [
            [
                'condition' => null,
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ]
        ];

        $data = ['amount' => 1500];

        $this->service->resolve($conditionalRedirect, $data);
    }

    /**
     * Test the resolve method with empty condition
     */
    public function testResolveWithEmptyCondition()
    {
        $conditionalRedirect = [
            [
                'condition' => '',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ]
        ];

        $data = ['amount' => 1500];

        // Empty condition should evaluate to true (empty string is truthy in FEEL), so it should match
        $result = $this->service->resolve($conditionalRedirect, $data);
        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
    }

    /**
     * Test the resolve method with array data containing nested values
     */
    public function testResolveWithNestedData()
    {
        $conditionalRedirect = [
            [
                'condition' => 'user.role == "admin"',
                'type' => 'externalURL',
                'value' => 'https://example.com/admin'
            ],
            [
                'condition' => 'user.role == "user"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = [
            'user' => [
                'role' => 'admin',
                'name' => 'John Doe'
            ],
            'status' => 'active'
        ];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/admin', $result['value']);
    }

    /**
     * Test the resolve method with data from ProcessRequestToken
     */
    public function testResolveWithTokenData()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a process
        $process = Process::factory()->create([
            'user_id' => $user->id
        ]);

        // Create a process request with test data
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => [
                'amount' => 1500,
                'status' => 'pending',
                'priority' => 'high'
            ]
        ]);

        // Create a process request token
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'user_id' => $user->id,
            'element_id' => 'task_1',
            'element_type' => 'task',
            'element_name' => 'Review Task',
            'status' => 'ACTIVE'
        ]);

        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'amount <= 1000',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        // Get the data from the process request and test with resolve() method
        $data = $processRequest->data;
        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/approval', $result['value']);
    }

    /**
     * Test the resolve method with no matching conditions using factory data
     */
    public function testResolveWithNoMatchingConditionsUsingTokenData()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a process
        $process = Process::factory()->create([
            'user_id' => $user->id
        ]);

        // Create a process request with data that won't match any conditions
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => [
                'amount' => 500,
                'status' => 'pending'
            ]
        ]);

        // Create a process request token
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'user_id' => $user->id,
            'element_id' => 'task_1',
            'element_type' => 'task',
            'element_name' => 'Review Task',
            'status' => 'ACTIVE'
        ]);

        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000',
                'type' => 'externalURL',
                'value' => 'https://example.com/approval'
            ],
            [
                'condition' => 'status == "urgent"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        // Get the data from the process request and test with resolve() method
        $data = $processRequest->data;
        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNull($result);
    }

    /**
     * Test the resolve method with multi-instance task data from token
     */
    public function testResolveWithMultiInstanceTaskData()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a process
        $process = Process::factory()->create([
            'user_id' => $user->id
        ]);

        // Create a process request with multi-instance data
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $user->id,
            'data' => [
                'items' => ['item1', 'item2', 'item3'],
                'status' => 'processing'
            ]
        ]);

        // Create a process request token with multi-instance properties
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
            'user_id' => $user->id,
            'element_id' => 'task_1',
            'element_type' => 'task',
            'element_name' => 'Multi-Instance Task',
            'status' => 'ACTIVE',
            'token_properties' => [
                'data' => [
                    'currentItem' => 'item1',
                    'loopCounter' => 0
                ]
            ]
        ]);

        $conditionalRedirect = [
            [
                'condition' => 'loopCounter == 0',
                'type' => 'externalURL',
                'value' => 'https://example.com/first-item'
            ],
            [
                'condition' => 'loopCounter > 0',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        // For multi-instance, we need to combine the process request data with token properties
        $data = array_merge($processRequest->data, $token->token_properties['data']);
        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/first-item', $result['value']);
    }

    /**
     * Test the resolve method with numeric conditions
     */
    public function testResolveWithNumericConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'count >= 5',
                'type' => 'externalURL',
                'value' => 'https://example.com/bulk'
            ],
            [
                'condition' => 'count < 5',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['count' => 7, 'type' => 'batch'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/bulk', $result['value']);
    }

    /**
     * Test the resolve method with date conditions
     */
    public function testResolveWithDateConditions()
    {
        $conditionalRedirect = [
            [
                'condition' => 'dueDate < date("2024-01-01")',
                'type' => 'externalURL',
                'value' => 'https://example.com/overdue'
            ],
            [
                'condition' => 'dueDate >= date("2024-01-01")',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['dueDate' => '2023-12-15', 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/overdue', $result['value']);
    }

    /**
     * Test the resolve method with multiple conditions in one item
     */
    public function testResolveWithMultipleConditionsInOneItem()
    {
        $conditionalRedirect = [
            [
                'condition' => 'amount > 1000 and status == "pending" and priority == "high"',
                'type' => 'externalURL',
                'value' => 'https://example.com/urgent-approval'
            ],
            [
                'condition' => 'amount <= 1000 or status = "completed"',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['amount' => 1500, 'status' => 'pending', 'priority' => 'high'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/urgent-approval', $result['value']);
    }

    /**
     * Test the resolve method with null data values
     */
    public function testResolveWithNullDataValues()
    {
        $conditionalRedirect = [
            [
                'condition' => 'value == null',
                'type' => 'externalURL',
                'value' => 'https://example.com/null-value'
            ],
            [
                'condition' => 'value != null',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['value' => null, 'status' => 'pending'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/null-value', $result['value']);
    }

    /**
     * Test the resolve method with array data values
     */
    public function testResolveWithArrayDataValues()
    {
        $conditionalRedirect = [
            [
                'condition' => '"urgent" in items',
                'type' => 'externalURL',
                'value' => 'https://example.com/urgent-items'
            ],
            [
                'condition' => '"normal" in items',
                'type' => 'taskList',
                'value' => null
            ]
        ];

        $data = ['items' => ['urgent', 'normal', 'low'], 'status' => 'processing'];

        $result = $this->service->resolve($conditionalRedirect, $data);

        $this->assertNotNull($result);
        $this->assertEquals('externalURL', $result['type']);
        $this->assertEquals('https://example.com/urgent-items', $result['value']);
    }
}
