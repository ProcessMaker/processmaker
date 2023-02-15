<?php

namespace Tests\Feature\ImportExport\Api;

use DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageVocabularies\Models\Vocabulary;
use ProcessMaker\Package\WebEntry\ImportExport\WebEntryUtils;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSource;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;
use ProcessMaker\Packages\Connectors\DataSources\Models\Webhook;
use ProcessMaker\Plugins\Collections\Models\Collection;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ExportImportTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;
    use WithFaker;

    public function testDownloadExportFile()
    {
        $screen = Screen::factory()->create(['title' => 'Screen']);

        $response = $this->apiCall(
            'POST',
            route('api.export.download', [
                'type' => 'screen',
                'id' => $screen->id,
            ]),
            [
                'password' => 'foobar',
                'options' => [],
            ]
        );

        // Ensure we can download the exported file.
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', "attachment; filename={$screen->title}.json");

        // Ensure it's encrypted.
        $payload = json_decode($response->streamedContent(), true);
        $this->assertEquals(true, $payload['encrypted']);

        $headers = $response->headers;
        $exportInfo = json_decode($headers->get('export-info'), true)['exported'];
        $this->assertCount(1, $exportInfo['Screen']['ids']);
        $this->assertEquals($screen->id, $exportInfo['Screen']['ids'][0]);
        $this->assertCount(1, $exportInfo['ScreenCategory']['ids']);
        $this->assertEquals($screen->categories[0]->id, $exportInfo['ScreenCategory']['ids'][0]);
    }

    public function testImportPreview()
    {
        [$file] = $this->importFixtures();

        $response = $this->apiCall('POST', route('api.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('manifest', $json);
    }

    public function testImport()
    {
        [$file] = $this->importFixtures();

        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            'password' => null,
            'options' => $this->makeOptions(),
        ]);
        $response->assertStatus(200);
    }

    public function testHandleDuplicateAttributes()
    {
        [$file, $screen, $nestedScreen] = $this->importFixtures();

        // Test that there is an unsaved screen in the manifest with a duplicate name
        // so delete it from the target instance here
        $nestedScreenUuid = $nestedScreen->uuid;
        $nestedScreen->delete();

        $initialScreenCount = Screen::count();

        // Update the existing screen, no change to title
        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            'password' => null,
            'options' => $this->makeOptions([
                $screen->uuid => ['mode' => 'copy'],
                $nestedScreenUuid => ['mode' => 'update'],
            ]),
        ]);
        $response->assertStatus(200);

        // Assert we added the child screen and the copied parent screen
        $this->assertEquals($initialScreenCount + 2, Screen::count());

        // Original parent screen on target instance
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen']);
        // Imported "copy" parent screen - gets the first auto-increment to 1
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen 1']);
        // Imported nested screen, originally called "Exported Screen 1", gets incremented to 2
        $this->assertDatabaseHas('screens', ['title' => 'Exported Screen 2']);
    }

    private function importFixtures()
    {
        $nestedScreen = Screen::factory()->create([
            'title' => 'Exported Screen 1',
            'description' => 'child',
            'screen_category_id' => null,
        ]);
        $config = [
            [
                'items' => [
                    [
                        'component' => 'FormNestedScreen',
                        'config' => [
                            'screen' => (int) $nestedScreen->id,
                        ],
                    ],
                ],
            ],
        ];
        $screen = Screen::factory()->create([
            'title' => 'Exported Screen',
            'config' => $config,
            'description' => 'parent',
            'screen_category_id' => null,
        ]);
        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // Create fake file upload.
        $payload = $exporter->payload();
        $content = json_encode($payload);
        $file = UploadedFile::fake()->createWithContent($payload['name'] . '.json', $content);

        return [
            $file, $screen, $nestedScreen,
        ];
    }

    public function testImportOldProcess()
    {
        $content = file_get_contents(base_path('tests/Feature/ImportExport/fixtures/old-process-payload-41.json'));
        // Run old process importer job
        $response = ImportProcess::dispatchNow($content);
        $process = Process::where('id', $response->process->id)->firstOrFail();

        $this->assertEquals('old_process_test_41', $process->name);
    }

    /**
     * @group agustin
     * @dataProvider importType
     * There are some assets that are not tested because we are not exporting for now:
     * - Users
     * - Groups
     * - Subprocesses
     *
     * Not yet implemented
     * - Collections
     */
    public function testExportImportFull($importType)
    {
        DB::beginTransaction();
        $this->addGlobalSignalProcess();

        // Prepare scenario
        $scenario = $this->prepareScenariosForExportImportFull();

        // Assign assets to process bpmn elements
        $process = $scenario['process'];
        $this->assignAttributesInBPMN($scenario, $process);

        // Do export
        [$exportData, $exportResponse] = $this->runFullProcessExport($process);

        // Assert assets in export file
        $this->assertAssetsInExportFile($scenario, $exportData);

        if ($importType == 'create') {
            // Clear database
            DB::rollBack();

            // Assert assets does not exists anymore in database
            $this->assertAssetsRemovedFromDB($scenario);
        } else {
            // Assert assets still in database
            $this->assertAssetsStillInDB($scenario);
        }

        // Do import
        $importResponse = $this->runFullProcessImport($exportResponse);
        $importResponse->assertStatus(200);

        // Assert elements was correctly imported
        $this->assertAssetsWasImported($scenario);
    }

    public function importType()
    {
        return [
            ['update'],
            ['create'],
        ];
    }

    private function prepareScenariosForExportImportFull()
    {
        // Create a user
        $user = User::factory()->create(['username' => 'user']);

        // Create a process from process-full fixture
        $process = Process::factory()->create([
            'name' => 'Full process',
            'bpmn' => file_get_contents(base_path('tests/Feature/ImportExport/fixtures/process-full.bpmn.xml')),
        ]);

        // Create a form screen for form task 1
        $formTaskScreen = $this->createScreen('screen_with_nested_screen', ['title' => 'screen'], 'watchers');
        $formTaskNestedScreen = Screen::factory()->create(['title' => 'nested screen', 'config' => []]);
        $watcherScript = Script::factory()->create(['title' => 'script']);
        $this->associateScriptWatcher($formTaskScreen, $watcherScript);
        $this->associateNestedScreen($formTaskScreen, $formTaskNestedScreen);

        $config = [['items' => []]];

        // Create screens for webentry
        $weAssociatedScreen = Screen::factory()->create(['title' => 'Web Entry associated screen', 'config' => $config]);
        $weCompletedScreen = Screen::factory()->create(['title' => 'Web Entry completed screen', 'config' => $config]);

        // Create a screen category
        $screenCategory = ScreenCategory::factory()->create(['name' => 'Screen category', 'status' => 'ACTIVE']);

        // Create a signal
        $signal = new SignalData(
            $this->faker->unique()->word(),
            $this->faker->sentence(),
            $this->faker->sentence()
        );

        // Create an external data source for data connector 2
        $dataSourceCategory = DataSourceCategory::factory()->create(['status' => 'ACTIVE', 'is_system' => false]);
        $dataSource = DataSource::factory()->create(['data_source_category_id' => $dataSourceCategory->id]);

        // Create a data source webhook
        $webhook = Webhook::factory()->create(['name' => $signal->getId() . ' webhook', 'config' => ['event' => $signal->getId()]]);

        // Create a display screen for connector PDF
        $pdfScreen = Screen::factory()->create(['title' => 'Connector PDF screen', 'type' => 'DISPLAY', 'config' => $config]);

        // Create 2 vocabularies for start event and form task 1 and 1 for process
        $vocabulary1 = Vocabulary::factory()->create();
        $vocabulary2 = Vocabulary::factory()->create();
        $vocabulary3 = Vocabulary::factory()->create();

        // Create an env variable that is used in script task 1
        $environmentVariable = EnvironmentVariable::factory()->create(['name' => 'MY_VAR_1']);

        // Create a script for script task 1 that uses an env variable
        $scriptCategory = ScriptCategory::factory()->create(['name' => 'test category']);
        $script = Script::factory()->create([
            'title' => 'test',
            'code' => '<?php $var1 = getenv(\'MY_VAR_1\'); return [];',
            'run_as_user_id' => $user->id,
        ]);

        // Assign
        $script->categories()->sync($scriptCategory);
        $formTaskScreen->screen_category_id = $screenCategory->id;
        $formTaskScreen->save();
        Vocabulary::attachProcessVocabularies($process, [$vocabulary3->id]);

        $process->save();

        return [
            'user' => $user,
            'process' => $process,
            'formTaskScreen' => $formTaskScreen,
            'formTaskNestedScreen' => $formTaskNestedScreen,
            'screenCategory' => $screenCategory,
            'weAssociatedScreen' => $weAssociatedScreen,
            'weCompletedScreen' => $weCompletedScreen,
            'dataSourceCategory' => $dataSourceCategory,
            'dataSource' => $dataSource,
            'webhook' => $webhook,
            'pdfScreen' => $pdfScreen,
            'vocabulary1' => $vocabulary1,
            'vocabulary2' => $vocabulary2,
            'vocabulary3' => $vocabulary3,
            'environmentVariable' => $environmentVariable,
            'script' => $script,
            'watcherScript' => $watcherScript,
            'scriptCategory' => $scriptCategory,
            'signal' => $signal,
        ];
    }

    private function assignAttributesInBPMN($scenario, $process)
    {
        // Intermediate Throw Event: Signal
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:intermediateThrowEvent/bpmn:signalEventDefinition', 'signalRef', $scenario['signal']->getId());
        // Intermediate Catch Event: Signal
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:intermediateCatchEvent/bpmn:signalEventDefinition', 'signalRef', $scenario['signal']->getId());
        // Start Event: Signal
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:startEvent[3]/bpmn:signalEventDefinition', 'signalRef', $scenario['signal']->getId());
        // Signal
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:signal', 'id', $scenario['signal']->getId());
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:signal', 'name', $scenario['signal']->getName());
        // Start event: Vocabulary
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:startEvent[1]', 'pm:validations', implode(',', [$scenario['vocabulary1']->id]));
        // Form task: Vocabulary
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:validations', implode(',', [$scenario['vocabulary2']->id]));
        // Form task: Screen
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $scenario['formTaskScreen']->id);
        // PDF Connector: Screen
        Utils::setPmConfigValueAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:serviceTask[1]', 'screenRef', $scenario['pdfScreen']->id);
        // Data connector: Data source
        Utils::setPmConfigValueAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:serviceTask[2]', 'dataSource', $scenario['dataSource']->id);
        // Script task: script
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:scriptTask', 'pm:scriptRef', $scenario['script']->id);
        // Web entry start event: screen
        WebEntryUtils::setPmWebEntryConfigValueAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:startEvent[2]', 'screen_id', $scenario['weAssociatedScreen']->id);
        // Web entry start event: completed screen
        WebEntryUtils::setPmWebEntryConfigValueAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:startEvent[2]', 'completed_screen_id', $scenario['weCompletedScreen']->id);

        $process->saveOrFail();
    }

    private function associateScriptWatcher($screen, $script)
    {
        $watchers = $screen->watchers;
        Arr::set($watchers, '0.script.id', 'script-' . $script->id);
        Arr::set($watchers, '0.script.uuid', $script->uuid);
        Arr::set($watchers, '0.script_id', $script->id);
        $screen->watchers = $watchers;
        $screen->saveOrFail();
    }

    private function associateNestedScreen($parent, $child)
    {
        $config = $parent->config;
        Arr::set($config, '0.items.2.config.screen', $child->id);
        $parent->config = $config;
        $parent->saveOrFail();
    }

    private function runFullProcessExport($process)
    {
        $route = route('api.export.download', ['type' => 'process', 'id' => $process->id]);
        $params = ['password' => 'foobar', 'options' => []];
        $response = $this->apiCall('POST', $route, $params);

        $response->assertStatus(200);

        $headers = $response->headers;

        return [
            json_decode($headers->get('export-info'), true)['exported'],
            $response,
        ];
    }

    public function runFullProcessImport($exportResponse)
    {
        $payload = json_decode($exportResponse->streamedContent(), true);
        $file = UploadedFile::fake()->createWithContent($payload['name'] . '.json', json_encode($payload));

        $response = $this->apiCall('POST', route('api.import.do_import'), [
            'file' => $file,
            'password' => 'foobar',
            'options' => $this->makeOptions(),
        ]);

        return $response;
    }

    private function assertAssetsInExportFile($scenario, $exportData)
    {
        $this->assertCount(1, $exportData['processes']);
        $this->assertCount(5, $exportData['screens']);
        $this->assertCount(5, $exportData['screen_categories']);
        $this->assertCount(1, $exportData['data_sources']);
        $this->assertCount(1, $exportData['data_source_categories']);
        $this->assertCount(3, $exportData['vocabularies']);
        $this->assertCount(2, $exportData['scripts']);
        $this->assertCount(2, $exportData['script_categories']);
        $this->assertCount(1, $exportData['environment_variables']);
        $this->assertCount(1, $exportData['process_categories']);
        $this->assertCount(1, $exportData['signals']);
        $this->assertCount(1, $exportData['webhooks']);

        $this->assertContains($scenario['process']->id, $exportData['processes']);
        $this->assertContains($scenario['formTaskScreen']->id, $exportData['screens']);
        $this->assertContains($scenario['formTaskNestedScreen']->id, $exportData['screens']);
        $this->assertContains($scenario['weAssociatedScreen']->id, $exportData['screens']);
        $this->assertContains($scenario['weCompletedScreen']->id, $exportData['screens']);
        $this->assertContains($scenario['pdfScreen']->id, $exportData['screens']);
        $this->assertContains($scenario['formTaskScreen']->categories[0]->id, $exportData['screen_categories']);
        $this->assertContains($scenario['weAssociatedScreen']->categories[0]->id, $exportData['screen_categories']);
        $this->assertContains($scenario['weCompletedScreen']->categories[0]->id, $exportData['screen_categories']);
        $this->assertContains($scenario['pdfScreen']->categories[0]->id, $exportData['screen_categories']);
        $this->assertContains($scenario['dataSource']->id, $exportData['data_sources']);
        $this->assertContains($scenario['dataSourceCategory']->id, $exportData['data_source_categories']);
        $this->assertContains($scenario['vocabulary1']->id, $exportData['vocabularies']);
        $this->assertContains($scenario['vocabulary2']->id, $exportData['vocabularies']);
        $this->assertContains($scenario['vocabulary3']->id, $exportData['vocabularies']);
        $this->assertContains($scenario['script']->id, $exportData['scripts']);
        $this->assertContains($scenario['watcherScript']->id, $exportData['scripts']);
        $this->assertContains($scenario['scriptCategory']->id, $exportData['script_categories']);
        $this->assertContains($scenario['watcherScript']->categories[0]->id, $exportData['script_categories']);
        $this->assertContains($scenario['environmentVariable']->id, $exportData['environment_variables']);
        $this->assertContains($scenario['process']->id, $exportData['process_categories']);
        $this->assertContains($scenario['signal']->getId(), $exportData['signals']);
        $this->assertContains($scenario['webhook']->id, $exportData['webhooks']);
    }

    private function assertAssetsRemovedFromDB($scenario)
    {
        $this->assertEquals(0, Process::where('name', $scenario['process']->name)->count());
        $this->assertEquals(0, Screen::where('title', $scenario['formTaskScreen']->title)->count());
        $this->assertEquals(0, ScreenCategory::where('name', $scenario['screenCategory']->name)->count());
        $this->assertEquals(0, Screen::where('title', $scenario['weAssociatedScreen']->title)->count());
        $this->assertEquals(0, Screen::where('title', $scenario['weCompletedScreen']->title)->count());
        $this->assertEquals(0, DataSourceCategory::where('name', $scenario['dataSourceCategory']->name)->count());
        $this->assertEquals(0, DataSource::where('name', $scenario['dataSource']->name)->count());
        $this->assertEquals(0, Screen::where('title', $scenario['pdfScreen']->title)->count());
        $this->assertEquals(0, Vocabulary::where('title', $scenario['vocabulary1']->title)->count());
        $this->assertEquals(0, Vocabulary::where('title', $scenario['vocabulary2']->title)->count());
        $this->assertEquals(0, Vocabulary::where('title', $scenario['vocabulary3']->title)->count());
        $this->assertEquals(0, EnvironmentVariable::where('name', $scenario['environmentVariable']->name)->count());
        $this->assertEquals(0, Script::where('title', $scenario['script']->title)->count());
        $this->assertEquals(0, ScriptCategory::where('name', $scenario['scriptCategory']->name)->count());
        $this->assertEquals(0, Webhook::where('name', $scenario['signal']->getId() . ' webhook')->count());
    }

    private function assertAssetsStillInDB($scenario)
    {
        $this->assertEquals(1, Process::where('name', $scenario['process']->name)->count());
        $this->assertEquals(1, Screen::where('title', $scenario['formTaskScreen']->title)->count());
        $this->assertEquals(1, Screen::where('title', $scenario['formTaskNestedScreen']->title)->count());
        $this->assertEquals(1, ScreenCategory::where('name', $scenario['screenCategory']->name)->count());
        $this->assertEquals(1, Screen::where('title', $scenario['weAssociatedScreen']->title)->count());
        $this->assertEquals(1, Screen::where('title', $scenario['weCompletedScreen']->title)->count());
        $this->assertEquals(1, DataSourceCategory::where('name', $scenario['dataSourceCategory']->name)->count());
        $this->assertEquals(1, DataSource::where('name', $scenario['dataSource']->name)->count());
        $this->assertEquals(1, Screen::where('title', $scenario['pdfScreen']->title)->count());
        $this->assertEquals(1, Vocabulary::where('title', $scenario['vocabulary1']->title)->count());
        $this->assertEquals(1, Vocabulary::where('title', $scenario['vocabulary2']->title)->count());
        $this->assertEquals(1, Vocabulary::where('title', $scenario['vocabulary3']->title)->count());
        $this->assertEquals(1, EnvironmentVariable::where('name', $scenario['environmentVariable']->name)->count());
        $this->assertEquals(1, Script::where('title', $scenario['script']->title)->count());
        $this->assertEquals(1, ScriptCategory::where('name', $scenario['scriptCategory']->name)->count());
        $this->assertEquals(1, Webhook::where('name', $scenario['signal']->getId() . ' webhook')->count());
    }

    private function assertAssetsWasImported($scenario)
    {
        $this->assertDatabaseHas('processes', ['name' => $scenario['process']->name]);
        $this->assertDatabaseHas('screens', ['title' => $scenario['formTaskScreen']->title]);
        $this->assertDatabaseHas('screens', ['title' => $scenario['formTaskNestedScreen']->title]);
        $this->assertDatabaseHas('screen_categories', ['name' => $scenario['screenCategory']->name]);
        $this->assertDatabaseHas('screens', ['title' => $scenario['weAssociatedScreen']->title]);
        $this->assertDatabaseHas('screens', ['title' => $scenario['weCompletedScreen']->title]);
        $this->assertDatabaseHas('data_source_categories', ['name' => $scenario['dataSourceCategory']->name]);
        $this->assertDatabaseHas('data_sources', ['name' => $scenario['dataSource']->name]);
        $this->assertDatabaseHas('screens', ['title' => $scenario['pdfScreen']->title]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary1']->title]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary2']->title]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary3']->title]);
        $this->assertDatabaseHas('scripts', ['title' => $scenario['script']->title]);
        $this->assertDatabaseHas('scripts', ['title' => $scenario['watcherScript']->title]);
        $this->assertDatabaseHas('script_categories', ['name' => $scenario['scriptCategory']->name]);
        $this->assertDatabaseHas('script_categories', ['name' => $scenario['watcherScript']->categories[0]->name]);
        $this->assertDatabaseHas('environment_variables', ['name' => $scenario['environmentVariable']->name]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary1']->title]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary2']->title]);
        $this->assertDatabaseHas('vocabularies', ['title' => $scenario['vocabulary3']->title]);
        $this->assertDatabaseHas('data_source_webhooks', ['name' => $scenario['signal']->getId() . ' webhook']);

        // Assert signal
        $importedProcess = Process::where('name', $scenario['process']->name)->firstOrFail();
        $definitions = $importedProcess->getDefinitions(true);
        $signalDefinitions = $definitions->getElementsByTagName('signal');
        $signalEventDefinitions = $definitions->getElementsByTagName('signalEventDefinition');
        $this->assertEquals(1, $signalDefinitions->count());
        $this->assertEquals(3, $signalEventDefinitions->count());
        $this->assertEquals($scenario['signal']->getId(), $signalEventDefinitions[0]->attributes[0]->value);
        $this->assertEquals($scenario['signal']->getId(), $signalEventDefinitions[1]->attributes[0]->value);
        $this->assertEquals($scenario['signal']->getId(), $signalEventDefinitions[2]->attributes[0]->value);
        $this->assertEquals($scenario['signal']->getId(), $signalDefinitions[0]->attributes[0]->value);
    }
}
