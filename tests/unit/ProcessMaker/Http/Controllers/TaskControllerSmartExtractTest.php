<?php

namespace Tests\Unit\ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Controllers\TaskController;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Services\SmartExtractConfiguration;
use ReflectionMethod;
use Tests\TestCase;

class TaskControllerSmartExtractTest extends TestCase
{
    public function test_hitl_configuration_uses_runtime_database_values(): void
    {
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::HITL_ENABLED,
            'value' => 'true',
        ]);
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::DASHBOARD_URL,
            'value' => 'https://dashboard.example.com/edit.html',
        ]);

        $processRequest = new ProcessRequest([
            'data' => [
                'documentToken' => 'document-token',
                'fileId' => 'file-123',
            ],
        ]);
        $task = new ProcessRequestToken();
        $task->setRelation('processRequest', $processRequest);

        $controller = new TaskController(app(SmartExtractConfiguration::class));
        $method = new ReflectionMethod(TaskController::class, 'smartExtractHitlConfiguration');
        $method->setAccessible(true);

        [$enabled, $iframeUrl] = $method->invoke($controller, $task, true);

        $this->assertTrue($enabled);
        $this->assertSame(
            'https://dashboard.example.com/edit.html?documentToken=document-token&fileId=file-123',
            $iframeUrl
        );
    }

    public function test_hitl_configuration_fails_closed_when_disabled(): void
    {
        EnvironmentVariable::factory()->create([
            'name' => SmartExtractConfiguration::HITL_ENABLED,
            'value' => 'false',
        ]);

        $task = new ProcessRequestToken();
        $task->setRelation('processRequest', new ProcessRequest(['data' => []]));

        $controller = new TaskController(app(SmartExtractConfiguration::class));
        $method = new ReflectionMethod(TaskController::class, 'smartExtractHitlConfiguration');
        $method->setAccessible(true);

        $this->assertSame([false, null], $method->invoke($controller, $task, true));
    }
}
