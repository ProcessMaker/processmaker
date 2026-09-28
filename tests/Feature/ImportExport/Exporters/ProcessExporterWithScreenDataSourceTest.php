<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\Jobs\ExportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use Tests\TestCase;

class ProcessExporterWithScreenDataSourceTest extends TestCase
{
    /**
     * Test that Process export with a Screen using a Data Source
     * correctly identifies the Data Source and not as a Script.
     */
    public function testProcessExportWithScreenDataSource()
    {
        $dataSourceClass = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';
        $dataSourceCategoryClass = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory';
        if (!class_exists($dataSourceClass) || !class_exists($dataSourceCategoryClass)) {
            $this->markTestSkipped('DataSource package not installed');
        }

        // Create a script first to consume some IDs
        $script = Script::factory()->create(['title' => 'A Script']);
        
        $category = new $dataSourceCategoryClass();
        $category->name = 'Test Category ' . uniqid();
        $category->save();

        $dataSource = new $dataSourceClass();
        $dataSource->name = 'Test Data Source ' . uniqid();
        $dataSource->description = 'Description';
        $dataSource->data_source_category_id = $category->id;
        $dataSource->save();

        // Let's try to make a script with the same ID as the data source
        // This might be tricky due to auto-increment, but we can try to force it or just use a high ID
        $duplicateScript = null;
        try {
            $duplicateScript = Script::factory()->create(['id' => $dataSource->id, 'title' => 'Duplicate ID Script']);
        } catch (\Exception $e) {
            // If ID is already taken, just create one normally
            $duplicateScript = Script::factory()->create(['title' => 'Other Script']);
        }

        $screen = Screen::factory()->create([
            'title' => 'Screen with data source watcher',
            'watchers' => [
                [
                    'name' => 'Data Source Watcher',
                    'script_id' => 'data_source-' . $dataSource->id,
                    'script' => [
                        'id' => 'data_source-' . $dataSource->id,
                        'title' => 'My Data Source',
                    ],
                ],
            ],
        ]);

        $bpmn = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" isExecutable="false">
    <bpmn:startEvent id="StartEvent_1" pm:screenRef="' . $screen->id . '" />
  </bpmn:process>
</bpmn:definitions>';

        $process = Process::factory()->create([
            'name' => 'Process with screen using data source',
            'bpmn' => $bpmn
        ]);

        $exportJob = new ExportProcess($process);
        $exportJob->handle();
        
        // Access private package property via reflection
        $reflection = new \ReflectionClass($exportJob);
        $property = $reflection->getProperty('package');
        $property->setAccessible(true);
        $package = $property->getValue($exportJob);

        // Check if data source is in package
        $this->assertArrayHasKey('data_sources', $package);
        $this->assertCount(1, $package['data_sources']);
        $this->assertEquals($dataSource->id, $package['data_sources'][0]['id']);

        // Check that it is NOT in scripts
        foreach ($package['scripts'] as $script) {
            $this->assertNotEquals('data_source-' . $dataSource->id, $script['id']);
            $this->assertNotEquals($dataSource->id, $script['id']);
            // Also check that it's not present as a numeric ID if it's the same
            if (is_numeric($script['id']) && (int)$script['id'] === $dataSource->id) {
                $this->fail('Data source ID ' . $dataSource->id . ' found in scripts section');
            }
        }
    }
}
