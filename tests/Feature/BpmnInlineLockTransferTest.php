<?php

namespace Tests\Feature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Jobs\BpmnAction;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Models\ProcessRequestLock;
use ReflectionMethod;
use ReflectionProperty;

#[TestGroup('process_tests')]
class BpmnInlineLockTransferTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInlineActionBecomesTheOnlyLockOwner(): void
    {
        $source = Mockery::mock(BpmnAction::class)->makePartial();
        $target = Mockery::mock(BpmnAction::class)->makePartial();
        $lock = Mockery::mock(ProcessRequestLock::class);
        $lockProperty = new ReflectionProperty(BpmnAction::class, 'lock');
        $lockProperty->setValue($source, $lock);

        $source->transferInternalContext($target);

        $this->assertNull($lockProperty->getValue($source));
        $this->assertSame($lock, $lockProperty->getValue($target));
    }

    public function testOnlyScriptAndServiceJobsEnableInlineTaskExecution(): void
    {
        $method = new ReflectionMethod(BpmnAction::class, 'allowsInlineTaskExecution');

        $this->assertTrue($method->invoke(Mockery::mock(RunScriptTask::class)->makePartial()));
        $this->assertTrue($method->invoke(Mockery::mock(RunServiceTask::class)->makePartial()));
        $this->assertFalse($method->invoke(Mockery::mock(CompleteActivity::class)->makePartial()));
    }
}
