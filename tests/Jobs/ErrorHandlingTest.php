<?php

namespace Tests\Jobs;

use Mockery;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    private function runAssertions($settings)
    {
        extract($settings);
        $errorHandling = ['timeout' => $bpmnTimeout, 'retry_attempts' => $bpmnRetryAttempts, 'retry_wait_time' => $bpmnRetryWaitTime];
        $element = Mockery::mock();
        $element->shouldReceive('getProperty')->andReturn(json_encode($errorHandling));
        $job = Mockery::mock(RunScriptTask::class);
        $job->shouldReceive('attempts')->andReturn($attempt);
        $job->shouldReceive('release')->with($expectedWaitTime)->times($expectedReleaseCount);

        $script = Script::factory()->create(['timeout' => $modelTimeout, 'retry_attempts' => $modelRetryAttempts, 'retry_wait_time' => $modelRetryWaitTime]);
        $token = ProcessRequestToken::factory()->create();

        $errorHandling = new ErrorHandling($element, $script, $token);
        $this->assertEquals($expectedTimeout, $errorHandling->timeout());
        $errorHandling->handleRetries($job, new \RuntimeException('error'));
    }

    public function testRetry()
    {
        $this->runAssertions([
            'attempt' => 3,
            'expectedTimeout' => 15,
            'expectedReleaseCount' => 1, // retry the job
            'expectedWaitTime' => 5,

            'modelTimeout' => 8,
            'modelRetryAttempts' => 2,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => 15,
            'bpmnRetryAttempts' => 3,
            'bpmnRetryWaitTime' => 5,
        ]);
    }

    public function testDoNotRetry()
    {
        $this->runAssertions([
            'attempt' => 4,
            'expectedTimeout' => 15,
            'expectedReleaseCount' => 0, // do not retry the job because attempt is 4 and bpmnRetryAttempts is 3
            'expectedWaitTime' => 5,

            'modelTimeout' => 8,
            'modelRetryAttempts' => 2,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => 15,
            'bpmnRetryAttempts' => 3,
            'bpmnRetryWaitTime' => 5,
        ]);
    }

    public function testRetryWaitFromModel()
    {
        $this->runAssertions([
            'attempt' => 4,
            'expectedTimeout' => 15,
            'expectedReleaseCount' => 0,
            'expectedWaitTime' => 6, // use modelRetryWaitTime because bpmnWaitTime is empty

            'modelTimeout' => 8,
            'modelRetryAttempts' => 2,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => 15,
            'bpmnRetryAttempts' => 3,
            'bpmnRetryWaitTime' => '',
        ]);
    }

    public function testTimeoutFromModel()
    {
        $this->runAssertions([
            'attempt' => 3,
            'expectedTimeout' => 8, // use modelTimeout because bpmnTimeout is empty
            'expectedReleaseCount' => 1,
            'expectedWaitTime' => 5,

            'modelTimeout' => 8,
            'modelRetryAttempts' => 2,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => '',
            'bpmnRetryAttempts' => 3,
            'bpmnRetryWaitTime' => 5,
        ]);
    }
}
