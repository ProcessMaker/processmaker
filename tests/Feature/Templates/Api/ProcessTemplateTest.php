<?php

namespace Tests\Feature\Templates\Api;

use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Packages\Connectors\DataSources\Models\DataSourceCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

class ProcessTemplateTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;
    use WithFaker;

    public function testIndex()
    {
        $this->addGlobalSignalProcess();
        ProcessTemplates::factory()->count(10)->create();

        $params = [
            'per_page' => 10,
            'filter' => '',
            'order_by' => 'name',
            'order_direction' => 'asc',
            'include' => 'user,category,categories  ',
        ];
        $response = $this->apiCall('GET', route('api.template.index', ['type' => 'process']), $params);
        $response->assertStatus(200);

        $content = json_decode($response->getContent(), true);
        $data = $content['data'];
        $this->assertCount(10, $data);

        // Assert that the data does not contain these fields to optimize the load.
        $this->assertArrayNotHasKey('svg', $data[0]);
        $this->assertArrayNotHasKey('manifest', $data[0]);
    }

    public function testNotAllowingToSaveDuplicateTemplateWithTheSameName()
    {
        $this->addGlobalSignalProcess();

        // // Create Process Screens
        $screen = $this->createScreen('basic-form-screen', ['title' => 'Test Screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'screen category', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        $process = $this->createProcess('process-with-task-screen', ['name' => 'Test Process']);
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        $process->save();
        $templateA = ProcessTemplates::factory()->create(['name' => 'Test Duplicate Name Template']);

        $response = $this->apiCall(
            'POST',
            route('api.template.store', [
                'type' => 'process',
                'id' => $process->id,
            ]),
            [
                'asset_id' => $process->id,
                'user_id' => null,
                'name' => 'Test Duplicate Name Template',
                'description' => 'Test template description',
                'process_category_id' => 1,
                'version' => $templateA->version,
                'mode' => 'new',
                'saveAssetsMode' => 'saveAllAssets',
            ]
        );

        $response->assertStatus(409);
        $content = json_decode($response->getContent());
        $this->assertEquals('The template name must be unique.', $content->name[0]);
    }

    public function testSaveProcessAssetsAsTemplate()
    {
        $this->addGlobalSignalProcess();

        // Create User
        $user = User::factory()->create();

        // Create process screens
        $screen = $this->createScreen('basic-form-screen', ['title' => 'Test Screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'screen category', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        // Create process
        $process = $this->createProcess('process-with-task-screen', ['name' => 'Test Process']);
        $processCategory = ProcessCategory::factory()->create(['name' => 'process category', 'status' => 'ACTIVE']);
        $process->process_category_id = $processCategory->id;

        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        $process->save();

        $response = $this->apiCall(
            'POST',
            route('api.template.store', [
                'type' => 'process',
                'id' => $process->id,
            ]),
            [
                'asset_id' => $process->id,
                'user_id' => $user->id,
                'name' => 'Test Template',
                'version' => '1.0.0',
                'description' => 'description 1',
                'process_category_id' => $process->process_category_id,
                'mode' => 'copy',
                'saveAssetsMode' => 'saveAllAssets',
            ]
        );

        // Validate the header status code
        $response->assertStatus(200);
        // Assert that our database has the process we need
        $this->assertDatabaseHas('process_templates', ['name' => 'Test Template']);
    }

    public function testSaveProcessModelAsTemplate()
    {
        $this->addGlobalSignalProcess();

        // Create User
        $user = User::factory()->create();

        $screen = $this->createScreen('basic-form-screen', ['title' => 'Test Screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'screen category', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        $process = $this->createProcess('process-with-task-screen', ['name' => 'Test Process']);
        $processCategory = ProcessCategory::factory()->create(['name' => 'process category', 'status' => 'ACTIVE']);
        $process->process_category_id = $processCategory->id;
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        $process->save();

        $response = $this->apiCall(
            'POST',
            route('api.template.store', [
                'type' => 'process',
                'id' => $process->id,
            ]),
            [
                'asset_id' => $process->id,
                'user_id' => $user->id,
                'name' => 'Test Template',
                'version' => '1.0.0',
                'description' => 'Test template description',
                'process_category_id' => $process->process_category_id,
                'mode' => 'discard',
                'saveAssetsMode' => 'saveAllAssets',
            ]
        );

        // Validate the header status code
        $response->assertStatus(200);
        $template = json_decode($response->getContent(), true)['model'];

        // Assert that our database has the process we need
        $this->assertDatabaseHas('process_templates', ['name' => 'Test Template']);
        $this->assertEquals($process->id, $template['process_id']);

        $template = ProcessTemplates::where('name', 'Test Template')->firstOrFail();
        $dependents = data_get(json_decode($template->manifest, true), 'original.export.dependents');

        $this->assertEquals($process->id, $template->process_id);
    }

    public function testCreateProcessFromTemplate()
    {
        $this->addGlobalSignalProcess();

        // Create User
        $user = User::factory()->create();

        $screen = $this->createScreen('basic-form-screen', ['title' => 'Test Screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'screen category', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        $processCategory = ProcessCategory::factory()->create(['name' => 'Default Templates', 'status' => 'ACTIVE']);
        $process = $this->createProcess('process-with-task-screen', ['name' => 'Test Process', 'process_category_id' => $processCategory->id]);
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        $process->save();

        $template = ProcessTemplates::factory()->create(['name' => 'Test Duplicate Name Template', 'process_id' => $process->id, 'process_category_id' => $process->process_category_id]);

        $response = $this->apiCall(
            'POST',
            route('api.template.create', [
                'type' => 'process',
                'id' => $template->id,
            ]),
            [
                'user_id' => $user->id,
                'name' => 'Test Create Process from Template',
                'description' => 'Process from template description',
                'process_category_id' => $template['process_category_id'],
                'mode' => 'copy',
                'version' => $template->version,
                'saveAssetMode' => 'saveAllAssets',
            ]
        );

        $response->assertStatus(200);
        $id = json_decode($response->getContent(), true)['processId'];
        $newProcess = Process::where('id', $id)->firstOrFail();
        $newCategory = ProcessCategory::where('id', $template['process_category_id'])->firstOrFail();

        $this->assertEquals('Test Create Process from Template', $newProcess->name);
        $this->assertEquals('Process from template description', $newProcess->description);
        $this->assertEquals('Default Templates', $newCategory->name);
    }

    public function testTemplateToProcessSync()
    {
        $this->markTestSkipped(
            'This test needs to be refactor to mock the transactions.'
        );
        $this->addGlobalSignalProcess();
        $fixtures = $this->fixtures();

        if (!$fixtures['allTemplates']->count()) {
            $this->markTestSkipped('Condition not met, skipping test.');
        }

        $failedProcess = [];
        foreach ($fixtures['allTemplates'] as $template) {
            $response = $this->createProcessesFromTemplate(
                $template, $fixtures['user'], $fixtures['processCategoryId']
            );

            if ($response->getStatusCode() != 200) {
                array_push($failedProcess, $template->name . ': ' . $response->getContent());
                continue;
            }

            $response->assertStatus(200);
            $processId = json_decode($response->getContent(), true)['processId'];
            $newProcess = Process::where('id', $processId)->firstOrFail();

            $this->assertEquals($template->name, $newProcess->name);
            $this->assertEquals($template->description, $newProcess->description);
        }

        if (count($failedProcess) > 0) {
            throw new Exception(implode(', ', $failedProcess));
        }
    }

    public function testUpdateAssetsWhenCreatingProcess()
    {
        $this->addGlobalSignalProcess();
        $user = User::factory()->create();

        $screen = Screen::factory()->create(['title' => 'First Screen']);
        $secondScreen = Screen::factory()->create(['title' => 'Second Screen']);

        $script = Script::factory()->create(['title' => 'First Script']);
        $secondScript = Script::factory()->create(['title' => 'Second Script']);

        $process = $this->createProcess('process-with-multiple-assets', ['name' => 'Test Process']);
        $definition = '/bpmn:definitions/bpmn:process/bpmn:';

        Utils::setAttributeAtXPath($process, $definition . 'task[1]', 'pm:screenRef', $screen->id);
        Utils::setAttributeAtXPath($process, $definition . 'scriptTask[1]', 'pm:scriptRef', $script->id);
        Utils::setAttributeAtXPath($process, $definition . 'task[2]', 'pm:screenRef', $secondScreen->id);
        Utils::setAttributeAtXPath($process, $definition . 'scriptTask[2]', 'pm:scriptRef', $secondScript->id);

        $process->save();

        $manifest = $this->getManifest('process', $process->id);

        $template = ProcessTemplates::factory()->create(
            [
                'name' => 'Test Duplicate Name Template',
                'process_id' => $process->id,
                'process_category_id' => $process->process_category_id,
                'manifest' => json_encode($manifest),
            ]);

        $params = [
            'user_id' => $user->id,
            'name' => 'Test Updating Assets of a template',
            'description' => 'Description of the process',
            'process_category_id' => $template['process_category_id'],
            'mode' => 'copy',
            'version' => $template->version,
            'saveAssetMode' => 'saveAllAssets',
        ];

        // First Request
        $route = route('api.template.create', ['type' => 'process', 'id' => $template->id]);
        $response = $this->apiCall('POST', $route, $params);
        $response->assertStatus(200);

        // Response for the update assets page
        $updatePageResponse = $response->json();

        // Update some of the assets mode
        $updatePageResponse['existingAssets'][0]['mode'] = 'discard';
        $updatePageResponse['existingAssets'][1]['mode'] = 'discard'; // First Screen
        $updatePageResponse['existingAssets'][2]['mode'] = 'update';  // Second Screen
        $updatePageResponse['existingAssets'][3]['mode'] = 'copy'; // First Script
        $updatePageResponse['existingAssets'][4]['mode'] = 'discard'; // Second Script

        $updatePageResponse['request'] = json_encode($updatePageResponse['request']);
        $updatePageResponse['existingAssets'] = json_encode($updatePageResponse['existingAssets']);

        // New Request with updated assets mode
        $route = route('api.template.create', ['type' => 'update-assets', 'id' => $updatePageResponse['id']]);
        $response = $this->apiCall('POST', $route, $updatePageResponse);

        $response->assertStatus(200);
        $this->assertEquals('2', Screen::count());
        $this->assertDatabaseHas('scripts', ['title' => 'First Script 2']);
        $this->assertEquals('3', Script::count());

        $id = json_decode($response->getContent(), true)['processId'];
        $newProcess = Process::where('id', $id)->firstOrFail();

        $this->assertEquals('Test Updating Assets of a template', $newProcess->name);
        $this->assertEquals('Description of the process', $newProcess->description);
    }

    /**
     * Tests the fixtures of the private PHP function.
     *
     * @return array Associative array containing the generated user and process category ID
     */
    private function fixtures()
    {
        $user = User::factory()->create();
        $processCategoryId = ProcessCategory::factory()
                                ->create(['name' => 'Default Templates', 'status' => 'ACTIVE'])->getKey();
        ProcessCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);
        ScreenCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);
        ScriptCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);
        DataSourceCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);
        Setting::firstOrCreate(['key' => 'idp.token_url'], [
            'format' => 'text',
            'group' => 'IDP',
            'helper' => 'Enter your OAuth2 token URL',
            'config' => '',
            'name' => 'Token URL',
            'hidden' => false,
            'ui' => null,
        ]);

        $allTemplates = ProcessTemplates::where(['key' => 'default_templates', 'user_id' => null])
        ->select(['id', 'description', 'name', 'process_category_id', 'version'])
        ->get();

        return ['user' => $user, 'processCategoryId' => $processCategoryId, 'allTemplates' => $allTemplates];
    }

    /**
     * Create processes from a given template.
     *
     * @param mixed $template The template to import processes from.
     * @return mixed The response from the API call.
     */
    private function createProcessesFromTemplate($template, $user, $processCategoryId)
    {
        return $this->apiCall(
            'POST',
            route('api.template.create', [
                'type' => 'process',
                'id' => $template->id,
            ]),
            [
                'user_id' => $user->getKey(),
                'name' => $template->name,
                'version' => $template->version,
                'description' => $template->description,
                'process_category_id' => $processCategoryId,
                'mode' => 'copy',
                'saveAssetMode' => 'saveAllAssets',
            ]
        );
    }

    /**
     * Get asset manifest.
     *
     * @param string $type
     *
     * @param Request $request
     *
     * @return array
     */
    public function getManifest(string $type, int $id) : array
    {
        $response = (new ExportController)->manifest($type, $id);

        return json_decode($response->getContent(), true);
    }
}
