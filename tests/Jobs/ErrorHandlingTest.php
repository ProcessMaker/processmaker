<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Queue;
use Mockery;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    private function runAssertions($settings)
    {
        Queue::fake();

        extract($settings);
        $errorHandling = ['timeout' => $bpmnTimeout, 'retry_attempts' => $bpmnRetryAttempts, 'retry_wait_time' => $bpmnRetryWaitTime];
        $element = Mockery::mock(\ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface::class);
        $element->shouldReceive('getProperty')->andReturn(json_encode($errorHandling));

        $script = Script::factory()->create(['timeout' => $modelTimeout, 'retry_attempts' => $modelRetryAttempts, 'retry_wait_time' => $modelRetryWaitTime]);

        $process = Process::factory()->create();
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);

        $job = new RunScriptTask($process, $processRequest, $token, ['foo' => 'baz'], $attempt);
        $errorHandling = new ErrorHandling($element, $token);
        $errorHandling->setDefaultsFromScript($script->getLatestVersion());
        $this->assertEquals($expectedTimeout, $errorHandling->timeout());
        $errorHandling->handleRetries($job, new \RuntimeException('error'));

        $expectedData = ['foo' => 'baz'];

        $this->assertQueue($expectedNextAttempt, $expectedWaitTime, $expectedData);

        // Test handleRetriesForScriptMicroservice

        $metadata = [
            'script_task' => [
                'job_class' => RunScriptTask::class,
                'data' => ['foo' => 'bar'],
                'attempt_num' => $attempt,
                'message' => 'error',
            ],
        ];

        Queue::fake();

        $expectedData = ['foo' => 'bar'];
        $errorHandling = new ErrorHandling($element, $token);
        $errorHandling->setDefaultsFromScript($script->getLatestVersion());
        $errorHandling->handleRetriesForScriptMicroservice(new \RuntimeException('error'), $metadata);

        $this->assertQueue($expectedNextAttempt, $expectedWaitTime, $expectedData);
    }

    private function assertQueue($expectedNextAttempt, $expectedWaitTime, $expectedData)
    {
        if ($expectedNextAttempt !== false) {
            Queue::assertPushed(RunScriptTask::class, function ($job) use ($expectedNextAttempt, $expectedWaitTime, $expectedData) {
                return $job->attemptNum === $expectedNextAttempt &&
                       $job->delay === $expectedWaitTime &&
                       $job->data === $expectedData;
            });
        } else {
            Queue::assertNotPushed(RunScriptTask::class);
        }
    }

    public function testRetry()
    {
        $this->runAssertions([
            'attempt' => 3,
            'expectedTimeout' => 15,
            'expectedNextAttempt' => 4, // retry the job
            'expectedWaitTime' => 5,

            'modelTimeout' => 8,
            'modelRetryAttempts' => 2,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => 15,
            'bpmnRetryAttempts' => 3,
            'bpmnRetryWaitTime' => 5,
        ]);
    }

    public function testRetryUseModelSettings()
    {
        $this->runAssertions([
            'attempt' => 3,
            'expectedTimeout' => 15,
            'expectedNextAttempt' => 4, // retry the job
            'expectedWaitTime' => 5,

            'modelTimeout' => 8,
            'modelRetryAttempts' => 99,
            'modelRetryWaitTime' => 6,
            'bpmnTimeout' => 15,
            'bpmnRetryAttempts' => '', // use modelRetryAttempts
            'bpmnRetryWaitTime' => 5,
        ]);
    }

    public function testDoNotRetry()
    {
        $this->runAssertions([
            'attempt' => 4,
            'expectedTimeout' => 15,
            'expectedNextAttempt' => false, // do not retry the job because attempt is 4 and bpmnRetryAttempts is 3
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
            'attempt' => 3,
            'expectedTimeout' => 15,
            'expectedNextAttempt' => 4,
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
            'expectedNextAttempt' => 4,
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
