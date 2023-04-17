<?php

namespace Tests\Feature\Templates\Api;

use Database\Seeders\ProcessTemplatesSeeder;
use Database\Seeders\UserSeeder;
use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessTemplates;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Templates;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

class ProcessTemplateTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;
    use WithFaker;

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

    public function testNotAllowingToSaveDuplicateTemplateWithTheSameName()
    {
        $this->addGlobalSignalProcess();

        // Create User
        $user = User::factory()->create();

        // Create Process Screens
        $screen = $this->createScreen('basic-form-screen', ['title' => 'Test Screen']);
        $screenCategory = ScreenCategory::factory()->create(['name' => 'screen category', 'status' => 'ACTIVE']);
        $screen->screen_category_id = $screenCategory->id;
        $screen->save();

        $process = $this->createProcess('process-with-task-screen', ['name' => 'Test Process']);
        $processCategory = ProcessCategory::factory()->create(['name' => 'process category', 'status' => 'ACTIVE']);
        $process->process_category_id = $processCategory->id;
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:screenRef', $screen->id);
        $process->save();
        ProcessTemplates::factory()->create(['name' => 'Test Duplicate Name Template', 'process_id' => $process->id]);

        $response = $this->apiCall(
            'POST',
            route('api.template.store', [
                'type' => 'process',
                'id' => $process->id,
            ]),
            [
                'asset_id' => $process->id,
                'user_id' => $user->id,
                'name' => 'Test Duplicate Name Template',
                'description' => 'Test template description',
                'process_category_id' => 1,
                'mode' => 'new',
                'saveAssetsMode' => 'saveAllAssets',
            ]
        );

        $response->assertStatus(409);
        $content = json_decode($response->getContent());
        $this->assertEquals('The template name must be unique.', $content->name[0]);
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
        $response = $this->apiCall(
            'POST',
            route('api.template.create', [
                'type' => 'process',
                'id' => $process->id,
            ]),
            [
                'asset_id' => $process->id,
                'user_id' => $user->id,
                'name' => 'Test Template',
                'description' => 'Test template description',
                'process_category_id' => $process->process_category_id,
                'mode' => 'copy',
                'saveAssetMode' => 'saveAllAssets',
            ]
        );
        dd($response);
        // Validate the header status code
        $response->assertStatus(200);
        // // Assert that our database has the process we need
        $this->assertDatabaseHas('process_templates', ['name' => 'Test Template']);

        // Create Process Templates
        $template = ProcessTemplates::where('name', 'Test Template')->firstOrFail();

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

    public function testSeededTemplate()
    {
        $this->seed(UserSeeder::class);
        $this->seed(ProcessTemplatesSeeder::class);
        $template = ProcessTemplates::where('name', 'Employee Onboarding 2023')->firstOrFail();
        $this->assertEquals('Employee Onboarding 2023', $template->name);
        $this->assertEquals('Default Templates', ProcessCategory::where('id', $template['process_category_id'])->firstOrFail()->name);
    }
}
