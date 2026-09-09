<?php

namespace Tests\Unit\ScriptRunners;

use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\ScriptRunner;
use Tests\TestCase;

class ScriptRunnerInProcessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withPersonalAccessClient();
        config([
            'core-service-task.enabled' => true,
            'core-service-task.execution' => 'modules',
            // Ensure local runner path is available for non-in-process fallback construction
            'script-runner-microservice.enabled' => false,
            'script-runners.php.runner' => 'MockRunner',
        ]);
    }

    public function testRunsAllowInProcessScriptViaScriptRuntime(): void
    {
        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return ["via" => "docker"];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => true,
            'timeout' => 30,
        ]);

        $runner = new ScriptRunner($script);
        $response = $runner->run(
            '<?php return ["via" => "in-process", "ping" => $data["ping"]];',
            ['ping' => '1'],
            [],
            30,
            $user,
            1,
            []
        );

        $this->assertSame('in-process', $response['output']['via']);
        $this->assertSame('1', $response['output']['ping']);
    }

    public function testSkipsInProcessWhenFlagDisabled(): void
    {
        config(['core-service-task.enabled' => false]);

        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return ["via" => "stored"];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => true,
        ]);

        $runner = new ScriptRunner($script);
        // MockRunner evals the passed $code
        $response = $runner->run(
            '<?php return ["via" => "mock"];',
            [],
            [],
            30,
            $user,
            1,
            []
        );

        $this->assertSame('mock', $response['output']['via']);
    }

    public function testSkipsInProcessWhenScriptNotAllowlisted(): void
    {
        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return ["via" => "stored"];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => false,
        ]);

        $runner = new ScriptRunner($script);
        $response = $runner->run(
            '<?php return ["via" => "mock"];',
            [],
            [],
            30,
            $user,
            1,
            []
        );

        $this->assertSame('mock', $response['output']['via']);
    }
}
