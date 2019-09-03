<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Providers\WorkflowServiceProvider as PM;

/**
 * Tests routes related to processes / CRUD related methods
 *
 * @group process_tests
 */
class ConvertBPMNTest extends TestCase
{
    use WithFaker;
    use RequestHelper;
    use ResourceAssertionsTrait;

    public $withPermissions = true;

    /**
     * Test convert subProcess to callActivity
     */
    public function testConvertSubProcess()
    {

        $process = factory(Process::class)->create([
            'status' => 'ACTIVE',
            'bpmn' => file_get_contents(__DIR__ . '/processes/CP.01.00 Create new customer.bpmn'),
        ]);
        $this->assertNotContains('subProcess', $process->bpmn);
        $this->assertEquals(2, Process::count());
    }
}
