<?php

namespace Tests\Feature\Templates\Api;

use DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Templates\HelperTrait;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RequestHelper;
    use HelperTrait;
    use WithFaker;

    public function testSaveProcessTemplate()
    {
        $this->addGlobalSignalProcess();

        // TODO: Create Process Screens
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
                'name' => 'Test Template',
                'description' => 'Test template description',
                'process_template_category_id' => 1,
                'mode' => 'copy',
            ]
        );

        // Validate the header status code
        $response->assertStatus(200);
        // Assert that our database has the process we need
        $this->assertDatabaseHas('process_templates', ['name' => 'Test Template']);
    }
}
