<?php

namespace Tests\Model;

use Mockery;
use ProcessMaker\Models\Script;
use ProcessMaker\ScriptRunners\MockRunner;
use Tests\TestCase;

class ScriptTest extends TestCase
{
    public function testUseTimeoutFromModeler()
    {
        $this->mockWithExpectedTimeout(123);

        $script = Script::factory()->create([
            'code' => 'foo',
            'timeout' => 5,
            'retry_attempts' => 3,
            'retry_wait_time' => 1
        ]);
        $script->runScript([], [], '', ['timeout' => 123]);
    }

    public function testUseTimeoutFromSetting()
    {
        $this->mockWithExpectedTimeout(5);

        $script = Script::factory()->create([
            'code' => 'foo',
            'timeout' => 5,
            'retry_attempts' => 3,
            'retry_wait_time' => 1
        ]);
        $script->runScript([], [], '', ['timeout' => '']);
    }

    private function mockWithExpectedTimeout($timeout)
    {
        $mock = Mockery::mock(MockRunner::class);
        $mock->shouldReceive('run')->once()->with('foo', [], [], $timeout, Mockery::any());
        $mock->shouldReceive('setTokenId')->once();
        app()->bind(MockRunner::class, function () use ($mock) {
            return $mock;
        });
    }

    /**
     * This test performs a test of retry_attempts and the retry_wait_time for script execution.
     */
    public function testUseRetryAttemptsAndRetryWaitTime()
    {
        //Mockery needs to know about this retry.
        $times = 3;
        $this->mockWithExpectedTimeoutWithException(5, $times);

        $this->expectException(\RuntimeException::class);

        $script = Script::factory()->create([
            'code' => 'foo',
            'timeout' => 2,
            'retry_attempts' => $times,
            'retry_wait_time' => 1
        ]);
        $script->runScript([], [], '', ['timeout' => '']);
    }

    /**
     * It is necessary to pass this value to Mockery because we are attempting retries. Mockery needs 
     * to know about this retry.
     */
    private function mockWithExpectedTimeoutWithException($timeout, $times)
    {
        $mock = Mockery::mock(MockRunner::class);
        $mock->shouldReceive('setTokenId')->times($times);
        $mock->shouldReceive('run')->times(0)->with('foo', [], [], $timeout, Mockery::any())->andReturnUsing(function () {
            throw new \RuntimeException('Error occurred');
        });

        app()->bind(MockRunner::class, function () use ($mock) {
            return $mock;
        });
    }
}