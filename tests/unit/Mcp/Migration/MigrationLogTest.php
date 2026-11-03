<?php

namespace Tests\Unit\Mcp\Migration;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Mcp\Migration\MigrationLog;
use Tests\TestCase;

class MigrationLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function testStartAndCompleteStep(): void
    {
        $log = MigrationLog::forProUid('pro-123');
        $log->start([
            'source' => [
                'pro_uid' => 'pro-123',
                'title' => 'Invoice Approval',
            ],
        ]);

        $log->setProcessId(42);
        $log->completeStep(MigrationLog::STEP_CREATE_PROCESS);

        $loaded = MigrationLog::load('pro-123');
        $this->assertNotNull($loaded);
        $this->assertTrue($loaded->isStepDone(MigrationLog::STEP_CREATE_PROCESS));
        $this->assertSame(42, $loaded->getProcessId());
        $this->assertSame('in_progress', $loaded->getStatus());
    }

    public function testFailMarksLogAsResumable(): void
    {
        $log = MigrationLog::forProUid('pro-456');
        $log->start(['source' => ['pro_uid' => 'pro-456', 'title' => 'Broken']]);
        $log->setProcessId(10);
        $log->completeStep(MigrationLog::STEP_CREATE_PROCESS);
        $log->fail(MigrationLog::STEP_CREATE_SCRIPTS, 'Script error');

        $loaded = MigrationLog::load('pro-456');
        $this->assertNotNull($loaded);
        $this->assertSame('failed', $loaded->getStatus());
        $this->assertTrue($loaded->canResume());
        $this->assertSame('Script error', $loaded->toSummary()['error']);
    }

    public function testArtifactsArePersisted(): void
    {
        $log = MigrationLog::forProUid('pro-789');
        $log->start(['source' => ['pro_uid' => 'pro-789', 'title' => 'Artifacts']]);
        $log->addArtifact('scripts', [
            'pm3_uid' => 'tri-1',
            'pm4_id' => 5,
            'title' => 'Trigger',
        ]);

        $loaded = MigrationLog::load('pro-789');
        $this->assertCount(1, $loaded->getArtifacts('scripts'));
        $this->assertSame('tri-1', $loaded->getArtifacts('scripts')[0]['pm3_uid']);
    }

    public function testListAllReturnsSummaries(): void
    {
        $first = MigrationLog::forProUid('pro-a');
        $first->start(['source' => ['pro_uid' => 'pro-a', 'title' => 'A']]);
        $first->setProcessId(1);

        $second = MigrationLog::forProUid('pro-b');
        $second->start(['source' => ['pro_uid' => 'pro-b', 'title' => 'B']]);
        $second->setProcessId(2);

        $logs = MigrationLog::listAll();
        $this->assertCount(2, $logs);
        $proUids = array_column($logs, 'pro_uid');
        $this->assertContains('pro-a', $proUids);
        $this->assertContains('pro-b', $proUids);
    }
}
