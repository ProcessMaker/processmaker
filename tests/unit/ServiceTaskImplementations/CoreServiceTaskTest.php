<?php

namespace Tests\Unit\ServiceTaskImplementations;

use ProcessMaker\Exception\ConfigurationException;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\ServiceTaskImplementations\CoreServiceTask;
use Tests\TestCase;

class CoreServiceTaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withPersonalAccessClient();
        config(['core-service-task.enabled' => true]);
    }

    public function testIsRegistered(): void
    {
        $this->assertTrue(
            WorkflowManager::existsServiceImplementation(CoreServiceTask::IMPLEMENTATION)
        );
    }

    public function testRunsAllowlistedPhpScript(): void
    {
        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return ["pong" => $data["ping"]];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => true,
            'timeout' => 30,
        ]);

        $task = new CoreServiceTask();
        $output = $task->run(
            ['ping' => '1'],
            ['script_id' => $script->id],
            'token-1',
            30
        );

        $this->assertSame('1', $output['pong']);
    }

    public function testFailsWhenDisabled(): void
    {
        config(['core-service-task.enabled' => false]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Core Service Task is disabled');

        (new CoreServiceTask())->run([], ['script_id' => 1]);
    }

    public function testFailsWithoutScriptId(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('script_id');

        (new CoreServiceTask())->run([], []);
    }

    public function testFailsWhenScriptNotAllowlisted(): void
    {
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return [];',
            'allow_in_process' => false,
        ]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('not allowed for in-process');

        (new CoreServiceTask())->run([], ['script_id' => $script->id]);
    }

    public function testFailsForNonPhpScript(): void
    {
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return [];',
            'allow_in_process' => true,
        ]);

        // Bypass model events to simulate a non-PHP allowlisted row
        Script::whereKey($script->id)->update([
            'language' => 'javascript',
            'allow_in_process' => true,
        ]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('only supports PHP');

        (new CoreServiceTask())->run([], ['script_id' => $script->id]);
    }

    public function testRejectsAllowInProcessForNonPhpOnSave(): void
    {
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return [];',
            'allow_in_process' => false,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $script->language = 'javascript';
        $script->allow_in_process = true;
        $script->save();
    }

    public function testCapsTimeoutToMax(): void
    {
        config([
            'core-service-task.max_timeout' => 2,
            'core-service-task.default_timeout' => 60,
            // Timeout hard-kill is enforced by the bare PHP subprocess runner
            'core-service-task.execution' => 'bare',
        ]);

        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php sleep(5); return ["ok" => true];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => true,
            'timeout' => 60,
        ]);

        $this->expectException(\ProcessMaker\Exception\ScriptTimeoutException::class);

        (new CoreServiceTask())->run(
            [],
            ['script_id' => $script->id],
            '',
            999
        );
    }
}
