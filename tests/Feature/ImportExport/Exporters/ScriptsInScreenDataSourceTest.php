<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\Assets\DataSourcesInScreen;
use ProcessMaker\Assets\ScriptsInScreen;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Jobs\ExportScreen;
use Tests\TestCase;

class ScriptsInScreenDataSourceTest extends TestCase
{
    /**
     * Test that ScriptsInScreen does not identify data_source- prefixed IDs as scripts.
     */
    public function testReferencesToExportWithDataSource()
    {
        $screen = Screen::factory()->create([
            'title' => 'Screen with data source watcher',
            'watchers' => [
                [
                    'name' => 'Data Source Watcher',
                    'script_id' => 'data_source-3',
                    'script' => [
                        'id' => 'data_source-3',
                        'title' => 'My Data Source',
                    ],
                ],
            ],
        ]);

        $scriptsInScreen = new ScriptsInScreen();
        $scripts = $scriptsInScreen->referencesToExport($screen);

        // After the fix, this should be 0 because the only watcher is a data source
        $this->assertCount(0, $scripts, 'Should not identify data_source as a script');
    }

    /**
     * Test that DataSourcesInScreen identifies data_source- prefixed IDs as data sources.
     */
    public function testReferencesToExportWithDataSourceAsset()
    {
        $screen = Screen::factory()->create([
            'title' => 'Screen with data source watcher',
            'watchers' => [
                [
                    'name' => 'Data Source Watcher',
                    'script_id' => 'data_source-3',
                    'script' => [
                        'id' => 'data_source-3',
                        'title' => 'My Data Source',
                    ],
                ],
            ],
        ]);

        $dataSourcesInScreen = new DataSourcesInScreen();
        $dataSources = $dataSourcesInScreen->referencesToExport($screen);

        $this->assertCount(1, $dataSources);
        $this->assertEquals('ProcessMaker\Packages\Connectors\DataSources\Models\DataSource', $dataSources[0][0]);
        $this->assertEquals(3, $dataSources[0][1]);
    }

    /**
     * Test that ExportScreen includes data sources in the package.
     */
    public function testExportScreenIncludesDataSources()
    {
        $dataSourceClass = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSource';
        $dataSourceCategoryClass = 'ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory';
        if (!class_exists($dataSourceClass) || !class_exists($dataSourceCategoryClass)) {
            $this->markTestSkipped('DataSource package not installed');
        }

        $category = new $dataSourceCategoryClass();
        $category->name = 'Test Category';
        $category->save();

        $dataSource = new $dataSourceClass();
        $dataSource->name = 'Test Data Source';
        $dataSource->description = 'Description';
        $dataSource->data_source_category_id = $category->id;
        $dataSource->save();

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

        $exportJob = new ExportScreen($screen);
        $exportJob->handle();
        
        // Access private package property via reflection
        $reflection = new \ReflectionClass($exportJob);
        // package is protected in ExportProcess
        $property = $reflection->getParentClass()->getProperty('package');
        $property->setAccessible(true);
        $package = $property->getValue($exportJob);

        $this->assertArrayHasKey('data_sources', $package);
        $this->assertCount(1, $package['data_sources']);
        $this->assertEquals($dataSource->id, $package['data_sources'][0]['id']);
    }
}
