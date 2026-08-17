<?php

namespace Tests\Feature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group as TestGroup;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Jobs\BpmnAction;
use ProcessMaker\Models\ProcessRequestLock;
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
        $source->setInternalContext(['lock' => $lock]);

        $source->transferInternalContext($target);

        $lockProperty = new ReflectionProperty(BpmnAction::class, 'lock');
        $this->assertNull($lockProperty->getValue($source));
        $this->assertSame($lock, $lockProperty->getValue($target));
    }
}
