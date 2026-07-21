<?php

namespace Tests\Unit\ServiceTaskImplementations;

use ProcessMaker\Contracts\ScriptModuleInterface;
use ProcessMaker\Facades\ScriptRuntime;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;
use ProcessMaker\ScriptRuntime\ScriptModuleRegistry;
use ProcessMaker\ServiceTaskImplementations\CoreServiceTask;
use Tests\TestCase;

class CoreServiceTaskModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'core-service-task.enabled' => true,
            'core-service-task.execution' => 'modules',
        ]);

        ScriptRuntime::clearResolvedInstances();
        $registry = new ScriptModuleRegistry();
        $runtime = new \ProcessMaker\ScriptRuntime\ScriptRuntime($registry);
        $this->app->instance(ScriptModuleRegistry::class, $registry);
        $this->app->instance(\ProcessMaker\ScriptRuntime\ScriptRuntime::class, $runtime);
        $this->app->instance('script.runtime', $runtime);

        ScriptRuntime::registerModule(CoreTaskFakeModule::class);
    }

    public function testRunsWithRegisteredModule(): void
    {
        $user = User::factory()->create();
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return ["out" => $demo->value()];',
            'run_as_user_id' => $user->id,
            'allow_in_process' => true,
            'timeout' => 30,
        ]);

        $output = (new CoreServiceTask())->run(
            [],
            ['script_id' => $script->id],
            'tok',
            30
        );

        $this->assertSame('ok', $output['out']);
    }
}

class CoreTaskFakeModule implements ScriptModuleInterface
{
    public static function key(): string
    {
        return 'demo';
    }

    public static function label(): string
    {
        return 'Demo';
    }

    public static function catalog(): array
    {
        return ['value' => []];
    }

    public function boot(ScriptExecutionContext $context): void
    {
    }

    public function value(): string
    {
        return 'ok';
    }
}
