<?php

namespace Tests\Feature\Console;

use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\ScriptExecutorVersion;
use Tests\TestCase;

class InitializeScriptMicroserviceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ScriptExecutorVersion::where('id', '>', 0)->delete();
        ScriptExecutor::where('id', '>', 0)->delete();
    }

    public function testInitializeScriptMicroserviceEnabled()
    {
        config([
            'script-runner-microservice.enabled' => true,
            'app.custom_executors' => false,
        ]);

        $this->createExistingExecutors();

        $this->artisan('processmaker:initialize-script-microservice')->assertSuccessful();

        $this->assertEquals(2, ScriptExecutor::where('is_system', true)->count());
        $this->assertEquals(2, ScriptExecutor::where('type', ScriptExecutorType::Duplicate)->count());

        $this->assertEquals([
            'php',
            'javascript',
            'python',
            'csharp',
            'java',
            'javascript-ssr',
        ], ScriptExecutor::where('is_system', false)->pluck('language')->toArray());
    }

    public function testInitializeScriptMicroserviceEnabledWithCustomExecutors()
    {
        config([
            'script-runner-microservice.enabled' => true,
            'app.custom_executors' => true,
        ]);

        $this->createExistingExecutors();

        $this->artisan('processmaker:initialize-script-microservice')->assertSuccessful();

        // Custom executors are allowed on the microservice, so nothing is hidden
        $this->assertEquals(0, ScriptExecutor::where('is_system', true)->count());
        $this->assertEquals(0, ScriptExecutor::where('type', ScriptExecutorType::Duplicate)->count());

        // Missing microservice languages are still created
        $this->assertEquals([
            'php-nayra',
            'php',
            'php',
            'javascript',
            'python',
            'csharp',
            'java',
            'javascript-ssr',
        ], ScriptExecutor::where('is_system', false)->pluck('language')->toArray());
    }

    public function testInitializeScriptMicroserviceDisabled()
    {
        config(['script-runner-microservice.enabled' => false]);

        $first = ScriptExecutor::factory()->create([
            'language' => 'php',
            'description' => 'A PHP Executor',
            'is_system' => true,
            'type' => ScriptExecutorType::Duplicate,
        ]);
        $second = ScriptExecutor::factory()->create([
            'language' => 'php',
            'description' => 'Existing system Executor',
            'is_system' => true,
            'type' => ScriptExecutorType::System,
        ]);

        $this->artisan('processmaker:initialize-script-microservice')->assertSuccessful();

        $this->assertEquals(2, ScriptExecutor::count());

        $first->refresh();
        $second->refresh();

        $this->assertEquals(false, $first->is_system);
        $this->assertEquals(true, $second->is_system);
    }

    /**
     * Create one executor with an unsupported language and two sharing the same language.
     */
    private function createExistingExecutors(): void
    {
        ScriptExecutor::factory()->create([
            'language' => 'php-nayra',
            'description' => 'Language is not in the microservice supported list',
        ]);
        ScriptExecutor::factory()->create([
            'language' => 'php',
            'description' => 'First PHP Executor',
        ]);
        ScriptExecutor::factory()->create([
            'language' => 'php',
            'description' => 'Second PHP Executor',
        ]);
    }
}
