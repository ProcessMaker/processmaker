<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\Bus;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\ImportExport\Exporters\ScriptExecutorExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Manifest;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Jobs\BuildScriptExecutor;
use ProcessMaker\Jobs\MoveScriptExecutorToMicroservice;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ScriptExecutorExporterTest extends TestCase
{
    use HelperTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminUser();
        config(['script-runner-microservice.enabled' => true]);
    }

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

    public function testImportUpdateDoesNotPromoteBaseExecutorViaTransition(): void
    {
        Bus::fake([
            BuildScriptExecutor::class,
            MoveScriptExecutorToMicroservice::class,
        ]);

        $executor = ScriptExecutor::factory()->create([
            'title' => 'PHP Executor',
            'description' => 'Default PHP Executor',
            'config' => '',
            'language' => 'php',
            'type' => null,
        ]);

        $payload = $this->scriptExecutorImportPayload($executor, [
            'description' => 'QA FOUR-34379 import regression marker',
            'config' => 'RUN composer require aws/aws-sdk-php:3.226.0',
            'type' => ScriptExecutorType::Custom->value,
        ]);

        (new Importer($payload, new Options([])))->doImport();

        $executor->refresh();
        $this->assertNull($executor->type);
        $this->assertEquals('QA FOUR-34379 import regression marker', $executor->description);
        $this->assertEquals(
            'RUN composer require aws/aws-sdk-php:3.226.0',
            $executor->config
        );
        Bus::assertNotDispatched(MoveScriptExecutorToMicroservice::class);
    }

    public function testImportUpdateStillTransitionsCustomExecutor(): void
    {
        Bus::fake([
            BuildScriptExecutor::class,
            MoveScriptExecutorToMicroservice::class,
        ]);

        $executor = ScriptExecutor::factory()->create([
            'title' => 'My Custom Executor',
            'description' => 'custom before',
            'config' => 'RUN echo before',
            'language' => 'php',
            'type' => ScriptExecutorType::Custom,
        ]);

        $payload = $this->scriptExecutorImportPayload($executor, [
            'description' => 'custom after',
            'config' => 'RUN echo after',
            'type' => ScriptExecutorType::Custom->value,
        ]);

        (new Importer($payload, new Options([])))->doImport();

        $executor->refresh();
        $this->assertEquals(ScriptExecutorType::Custom, $executor->type);
        $this->assertEquals('custom after', $executor->description);
        Bus::assertDispatched(MoveScriptExecutorToMicroservice::class, function ($job) use ($executor) {
            return $this->jobUuid($job) === $executor->uuid;
        });
    }

    private function scriptExecutorImportPayload(ScriptExecutor $executor, array $attributeOverrides): array
    {
        $attributes = array_merge([
            'uuid' => $executor->uuid,
            'title' => $executor->title,
            'description' => $executor->description,
            'language' => $executor->language,
            'config' => $executor->config,
            'is_system' => 0,
            'type' => $executor->type?->value,
        ], $attributeOverrides);

        return [
            'type' => 'process_package',
            'root' => $executor->uuid,
            'export' => [
                $executor->uuid => [
                    'exporter' => ScriptExecutorExporter::class,
                    'type' => 'ScriptExecutor',
                    'type_human' => 'Script Executor',
                    'type_plural' => 'ScriptExecutors',
                    'type_human_plural' => 'Script Executors',
                    'last_modified_by' => '',
                    'last_modified_by_id' => null,
                    'model' => ScriptExecutor::class,
                    'force_password_protect' => false,
                    'hidden' => false,
                    'mode' => 'update',
                    'saveAssetsMode' => 'saveAllAssets',
                    'explicit_discard' => false,
                    'dependents' => [],
                    'name' => $attributes['title'],
                    'description' => $attributes['description'],
                    'process_manager' => [],
                    'process_manager_id' => [],
                    'attributes' => $attributes,
                    'extraAttributes' => [
                        'translatedLanguages' => [],
                    ],
                    'references' => [],
                ],
            ],
        ];
    }

    private function jobUuid(MoveScriptExecutorToMicroservice $job): string
    {
        $property = (new \ReflectionClass($job))->getProperty('uuid');
        $property->setAccessible(true);

        return $property->getValue($job);
    }
}
