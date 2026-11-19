<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\ImportExport\Exporters\ScriptExecutorExporter;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\Models\ScriptExecutor;
use Tests\TestCase;

class ScriptExecutorExporterTest extends TestCase
{
    public function testUpdateModeDoesNotOverwriteType(): void
    {
        $executor = ScriptExecutor::factory()->create([
            'title' => 'PHP Executor',
            'description' => 'local description',
            'config' => '',
            'language' => 'php',
            'type' => null,
        ]);

        $assetInfo = [
            'model' => ScriptExecutor::class,
            'attributes' => [
                'id' => $executor->id,
                'uuid' => $executor->uuid,
                'title' => 'PHP Executor',
                'description' => 'from package',
                'language' => 'php',
                'config' => 'RUN composer require aws/aws-sdk-php:3.226.0',
                'is_system' => 0,
                'type' => ScriptExecutorType::Custom->value,
            ],
        ];

        [$mode, $model] = Manifest::getModel(
            $executor->uuid,
            $assetInfo,
            'update',
            ScriptExecutorExporter::class,
            false
        );

        $this->assertEquals('update', $mode);
        $this->assertNull($model->type);
        $this->assertEquals('from package', $model->description);
        $this->assertEquals('RUN composer require aws/aws-sdk-php:3.226.0', $model->config);
    }

    public function testNewModeStillAppliesType(): void
    {
        $uuid = '9acb37b3-a8ed-49dc-b4de-f686ad4e286f';
        $assetInfo = [
            'model' => ScriptExecutor::class,
            'attributes' => [
                'uuid' => $uuid,
                'title' => 'Custom Package Executor',
                'description' => 'custom executor',
                'language' => 'php',
                'config' => 'RUN echo custom',
                'is_system' => 0,
                'type' => ScriptExecutorType::Custom->value,
            ],
        ];

        // Mode "new" is assigned by the system when no matching model exists.
        [$mode, $model] = Manifest::getModel(
            $uuid,
            $assetInfo,
            'update',
            ScriptExecutorExporter::class,
            false
        );

        $this->assertEquals('new', $mode);
        $this->assertEquals(ScriptExecutorType::Custom, $model->type);
        $this->assertEquals('Custom Package Executor', $model->title);
    }
}
