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
        ]);
        $script->runScript([], [], '', ['timeout' => 123]);
    }

    public function testUseTimeoutFromSetting()
    {
        $this->mockWithExpectedTimeout(5);

        $script = Script::factory()->create([
            'code' => 'foo',
            'timeout' => 5,
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
}
