<?php

namespace Tests\Feature\Templates\Api;

use Database\Seeders\UserSeeder;
use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function testTemplateSync()
    {
        $user = User::factory()->create();
        $processCategoryId = ProcessCategory::factory()->create(['name' => 'Default Templates', 'status' => 'ACTIVE'])->getKey();
        ProcessCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);
        ScreenCategory::factory()->create(['name' => 'Uncategorized', 'status' => 'ACTIVE']);

        $githubConfig = config('services.github');
        $templateBranch = $githubConfig['template_branch'];
        $templateRepoBaseUrl = $githubConfig['base_url'] . $githubConfig['template_repo'];

        if ($templateBranch != 'develop' && $templateBranch != 'master') {
            $count = $this->countTemplatesFromRepo($githubConfig);
            $this->assertEquals($count, ProcessTemplates::where(['key' => 'default_templates'])->count());

            $developReadmeFile = $templateRepoBaseUrl . '/develop/README.md';
            $newReadmeFile = $templateRepoBaseUrl . '/' . $templateBranch . '/README.md';

            $developIndex = Http::get($developReadmeFile);
            $envIndex = Http::get($newReadmeFile);
            $diffOutput = $this->getAddedLines($developIndex, $envIndex);

            foreach ($diffOutput as $templateName) {
                $template = ProcessTemplates::where(['name' => $templateName, 'key' => 'default_templates'])->firstOrFail();
                $response = $this->createProcessesFromTemplate($template, $user, $processCategoryId);
                $response->assertStatus(200);

                $processId = json_decode($response->getContent(), true)['processId'];
                $newProcess = Process::where('id', $processId)->firstOrFail();
                $newCategory = ProcessCategory::where('id', $template['process_category_id'])->firstOrFail();

                $this->assertEquals($template->name, $newProcess->name);
                $this->assertEquals($template->description, $newProcess->description);
                $this->assertEquals('Default Templates', $newCategory->name);
            }
        }
    }

     /**
      * Compares the count of imported templates with the templates in the database.
      *
      * @param array $config The configuration array containing the base URL, template repository, and template branch.
      * @throws Exception If the default template list could not be fetched.
      * @return int The count of templates.
      */
     private function countTemplatesFromRepo($config)
     {
         $url = $config['base_url'] . $config['template_repo'] . '/' . $config['template_branch'] . '/index.json';
         $response = Http::get($url);

         if (!$response->successful()) {
             throw new Exception('Unable to fetch default template list.');
         }

         $templates = $response->json();
         $count = 0;

         foreach ($templates as $template) {
             if (is_array($template)) {
                 $count += count($template);
             }
         }

         return $count;
     }

    /**
     * Create processes from a given template.
     *
     * @param mixed $template The template to import processes from.
     * @return mixed The response from the API call.
     */
    private function createProcessesFromTemplate($template, $user, $processCategoryId)
    {
        $response = $this->apiCall(
            'POST',
            route('api.template.create', [
                'type' => 'process',
                'id' => $template->id,
            ]),
            [
                'user_id' => $user->getKey(),
                'name' => $template->name,
                'description' => $template->description,
                'process_category_id' => $processCategoryId,
                'mode' => 'copy',
                'saveAssetMode' => 'saveAllAssets',
            ]
        );

        return $response;
    }

    /**
     * Returns an array of titles of the added lines between two index files.
     *
     * @param string $developIndex The index file from the develop branch.
     * @param string $envIndex The index file from the environment branch.
     * @return array An array of titles of the added lines in an array.
     */
    private function getAddedLines($developIndex, $envIndex)
    {
        $developLines = explode(PHP_EOL, $developIndex);
        $envLines = explode(PHP_EOL, $envIndex);
        $addedLines = [];

        foreach ($envLines as $line) {
            if (!in_array($line, $developLines)) {
                $addedLines[] = $line;
            }
        }

        $pattern = '/\[([^]]+)\]/';
        $extractedContent = [];

        foreach ($addedLines as $item) {
            preg_match($pattern, $item, $match);
            $content = $match[1] ?? '';
            $extractedContent[] = $content;
        }

        return $extractedContent;
    }
}
