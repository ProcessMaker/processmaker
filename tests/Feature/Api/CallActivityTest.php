<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\ProcessTestingTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Support\Facades\Artisan;

class CallActivityTest extends TestCase
{
    use RequestHelper;
    use ProcessTestingTrait;

    /**
     * Tests the a process with call activity to a external process definition
     *
     * @group process_tests
     */
    public function testCallActivity()
    {
        // Script task requires passport installed (oauth token)
        Artisan::call('passport:install',['-vvv' => true]);

        // Create the processes
        $parent = $this->createProcess([
            'id' => 1,
            'bpmn' => file_get_contents(__DIR__ . '/processes/parent.bpmn')
        ]);
        $child = $this->createProcess([
            'id' => 2,
            'bpmn' => file_get_contents(__DIR__ . '/processes/child.bpmn')
        ]);

        // Start a process instance
        $instance = $this->startProcess($parent, 'node_1');
        $subInstance = ProcessRequest::get()[1];

        // Get active tokens
        $activeTokens = $instance->tokens()->where('status', 'ACTIVE')->get();
        $activeSubTokens = $subInstance->tokens()->where('status', 'ACTIVE')->get();

        // Assert both processes are COMPLETED
        $this->assertCount(0, $activeTokens);
        $this->assertCount(0, $activeSubTokens);
        $this->assertEquals('COMPLETED', $instance->status);
        $this->assertEquals('COMPLETED', $subInstance->status);
    }
}
