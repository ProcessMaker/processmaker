<?php

namespace Tests\Unit\Services;

use ProcessMaker\Models\Script;
use ProcessMaker\Services\ScriptExecutionModeResolver;
use Tests\TestCase;

class ScriptExecutionModeResolverTest extends TestCase
{
    public function testScriptsDefaultToBareExecutionMode(): void
    {
        $script = Script::factory()->create();

        $this->assertSame(
            Script::EXECUTION_MODE_BARE,
            $script->fresh()->execution_mode
        );
    }

    public function testDefaultsToBare(): void
    {
        config(['core-service-task.modules_enabled' => true]);

        $script = new Script([
            'allow_in_process' => true,
            'execution_mode' => Script::EXECUTION_MODE_BARE,
        ]);

        $this->assertSame(
            Script::EXECUTION_MODE_BARE,
            (new ScriptExecutionModeResolver())->resolve($script)
        );
    }

    public function testAllowsModulesForExplicitlyConfiguredScript(): void
    {
        config(['core-service-task.modules_enabled' => true]);

        $script = new Script([
            'allow_in_process' => true,
            'execution_mode' => Script::EXECUTION_MODE_MODULES,
        ]);

        $this->assertSame(
            Script::EXECUTION_MODE_MODULES,
            (new ScriptExecutionModeResolver())->resolve($script)
        );
    }

    public function testFallsBackToBareWhenModulesAreDisabled(): void
    {
        config(['core-service-task.modules_enabled' => false]);

        $script = new Script([
            'allow_in_process' => true,
            'execution_mode' => Script::EXECUTION_MODE_MODULES,
        ]);

        $this->assertSame(
            Script::EXECUTION_MODE_BARE,
            (new ScriptExecutionModeResolver())->resolve($script)
        );
    }

    public function testDisallowsModulesForScriptsNotAllowedInProcess(): void
    {
        config(['core-service-task.modules_enabled' => true]);

        $script = new Script([
            'allow_in_process' => false,
            'execution_mode' => Script::EXECUTION_MODE_MODULES,
        ]);

        $this->assertSame(
            Script::EXECUTION_MODE_BARE,
            (new ScriptExecutionModeResolver())->resolve($script)
        );
    }
}
