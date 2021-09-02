<?php

namespace Tests\Feature\Processes;

use Faker\Factory as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use phpDocumentor\Reflection\PseudoType;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Providers\WorkflowServiceProvider;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Jobs\ImportProcess;

class ExportImportTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private $definitions, $process, $screen01, $screen02, $script01, $script02;

    protected function setUpExecutors() {
        ScriptExecutor::setTestConfig('php');
        ScriptExecutor::setTestConfig('lua');
    }

    /**
     * Test to ensure screens and scripts are referenced
     * by the proper nodes upon process import.
     *
     * @return void
     */
    public function testProcessImportRefs()
    {
        // Create a pre-existing screen and script
        factory(Screen::class, 1)->create(['title' => 'Existing Screen']);
        factory(Script::class, 1)->create(['title' => 'Existing Script']);

        // Assert that they now exist
        $this->assertDatabaseHas('screens', ['title' => 'Existing Screen']);
        $this->assertDatabaseHas('scripts', ['title' => 'Existing Script']);

        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_refs.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        // Retrieve the newly imported elements
        $this->process = Process::where('name', 'Test Process 01')->first();
        $this->screen01 = Screen::where('title', 'Screen 01')->first();
        $this->screen02 = Screen::where('title', 'Screen 02')->first();
        $this->script01 = Script::where('title', 'Script 01')->first();
        $this->script02 = Script::where('title', 'Script 02')->first();

        // Get BPMN
        $this->definitions = $this->process->getDefinitions();

        // Check screen refs & script refs
        $this->checkScreenRefs();
        $this->checkScriptRefs();
    }

    /**
     * Part of TestProcessImportRefs: check screen references in BPMN.
     *
     * @return void
     */
    private function checkScreenRefs()
    {
        // Map of names to their expected reference ID
        $map = [
            'Human Task 1' => $this->screen01->id,
            'Human Task 2' => $this->screen02->id,
        ];

        // For each of the human tasks...
        $tasks = $this->definitions->getElementsByTagName('task');
        foreach ($tasks as $task) {
            // Obtain the name and screen ref
            $name = $task->getAttribute('name');
            $screenRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef');

            // Assert that the screen ref matches the expected screen ref
            if (array_key_exists($name, $map)) {
                $this->assertEquals($map[$name], $screenRef);
            }
        }
    }

    /**
     * Part of TestProcessImportRefs: check script references in BPMN.
     *
     * @return void
     */
    private function checkScriptRefs()
    {
        // Map of names to their expected reference ID
        $map = [
            'Script Task 1' => $this->script01->id,
            'Script Task 2' => $this->script02->id,
        ];

        // For each of the script tasks...        
        $tasks = $this->definitions->getElementsByTagName('scriptTask');
        foreach ($tasks as $task) {
            // Obtain the name and script ref
            $name = $task->getAttribute('name');
            $scriptRef = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef');

            // Assert that the script ref matches the expected script ref            
            if (array_key_exists($name, $map)) {
                $this->assertEquals($map[$name], $scriptRef);
            }
        }
    }

    /**
     * Test to ensure we can export and import
     *
     * @return void
     */
    public function testExportImportProcess()
    {
        // Create an admin user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        $standardUser = factory(User::class)->create([
            'username' => 'standard',
            'is_administrator' => false,
        ]);

        // Seed the processes table.
        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        // Assert that our database has what it should and not what it shouldn't
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off']);
        $this->assertDatabaseHas('scripts', ['title' => 'Get available days Script']);
        $this->assertDatabaseMissing('processes', ['name' => 'Leave Absence Request 2']);
        $this->assertDatabaseMissing('screens', ['title' => 'Request Time Off 2']);
        $this->assertDatabaseMissing('scripts', ['title' => 'Get available days Script 2']);

        // Get the process we'll be testing on
        $process = Process::where('name', 'Leave Absence Request')->first();


        // Add additional categories
        $secondProcessCategory = factory(ProcessCategory::class)->create(['name' => 'Second Category']);
        $process->categories()->save($secondProcessCategory);

        $script = Script::where('title', 'Get available days Script')->firstOrFail();
        $secondScriptCategory = ScriptCategory::create(['name' => 'Other Script Category']);
        $script->categories()->save($secondScriptCategory);
        
        $screen = Screen::where('title', 'Request Time Off')->firstOrFail();
        $secondScreenCategory = ScreenCategory::create(['name' => 'Other Screen Category']);
        $screen->categories()->save($secondScreenCategory);

        // Test to ensure our standard user cannot export a process
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(403);

        // Test to ensure our admin user can export a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response->assertStatus(200);
        $response->assertJsonStructure(['url']);

        // Test to ensure we can download the exported file
        $response = $this->webCall('GET', $response->json('url'));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename="Leave Absence Request.json"');

        // Get our file contents (we have to do it this way because of
        // Symfony's weird response API)
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();

        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'leave_absence_request.json', null, null, null, true);

        // Test to ensure our standard user cannot import a process
        $this->user = $standardUser;
        $response = $this->apiCall('POST', "/processes/import", [
            'file' => $file,
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('processes', ['name' => 'Leave Absence Request 2']);

        // Test to ensure our admin user can import a process
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/import", [
            'file' => $file,
        ]);
        // dd($response->json());
        $response->assertJsonStructure(['status' => [], 'assignable' => []]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request 2']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off 2']);
        $this->assertDatabaseHas('scripts', ['title' => 'Get available days Script 2']);

        // Assert items were added to both categories
        $this->assertCount(2, $process->category->refresh()->processes);
        $this->assertCount(2, $secondProcessCategory->refresh()->processes);
        
        $this->assertCount(2, $script->category->refresh()->scripts);
        $this->assertCount(2, $secondScriptCategory->refresh()->scripts);

        $this->assertCount(2, $screen->category->refresh()->screens);
        $this->assertCount(2, $secondScreenCategory->refresh()->screens);

        // Assert that assignments are preserved, except for user and group assignments
        $process = Process::where('name', 'Leave Absence Request 2')->first();
        $definitions = $process->getDefinitions();
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        $this->assertEquals("", $definitions->findElementById('node_5')->getAttributeNS($ns, 'assignment'));
        $this->assertEquals("", $definitions->findElementById('node_5')->getAttributeNS($ns, 'assignedUsers'));
        
        $this->assertEquals("self_service", $definitions->findElementById('node_6')->getAttributeNS($ns, 'assignment'));
        $this->assertEquals("", $definitions->findElementById('node_6')->getAttributeNS($ns, 'assignedGroups'));
    }

    /**
     * Test anonymous user assignments are not removed. Instead,
     * they are are updated to the current instance's anon user ID
     */
    public function testExportWithAnonymousUser()
    {
        $originalAnonUser = app(AnonymousUser::class);
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        Artisan::call('db:seed', ['--class' => 'ProcessSeeder']);

        $process = Process::where('name', 'Leave Absence Request')->first();
        $definitions = $process->getDefinitions();
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        $definitions->findElementById('node_5')->setAttributeNS($ns, 'assignedUsers', $originalAnonUser->id);
        $process->update(['bpmn' => $definitions->saveXML()]);

        $response = $this->apiCall('POST', "/processes/{$process->id}/export");
        $response = $this->webCall('GET', $response->json('url'));
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();
        
        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'leave_absence_request.json', null, null, null, true);

        $newAnonUser = factory(User::class)->create(['status' => 'active']);
        $this->app->extend(AnonymousUser::class, function($app) use ($newAnonUser) {
            return $newAnonUser;
        });
        
        $this->user = $adminUser;
        $response = $this->apiCall('POST', "/processes/import", [
            'file' => $file,
        ]);

        $process = Process::where('name', 'Leave Absence Request 2')->first();
        $definitions = $process->getDefinitions();
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;
        $this->assertEquals("user", $definitions->findElementById('node_5')->getAttributeNS($ns, 'assignment'));
        $this->assertEquals(
            $newAnonUser->id, 
            $definitions->findElementById('node_5')->getAttributeNS($ns, 'assignedUsers')
        );
        
        // Reset the anon user for other tests
        $this->app->extend(AnonymousUser::class, function($app) use ($originalAnonUser) {
            return $originalAnonUser;
        });
    }

    /**
     * Test assignments after import process.
     */
    public function test_assignmets_after_import()
    {
        // Load file to import
        $file = new UploadedFile(base_path('tests/storage/process/') . 'test_process_import.json', 'test_process_import.json', null, null, null, true);

        //Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);
        $response->assertJsonStructure(['status' => [], 'assignable' => [], 'process' => []]);

        $assignable = [];
        $faker = Faker::create();

        //Create assignments in startEvent, task, userTask, callActivity
        foreach ($response->json('assignable') as $item) {
            if ($item['type'] === 'callActivity') {
                $item['value'] = factory(Process::class)->create(['name' => 'process test', 'status' => 'ACTIVE'])->toArray();
            } else {
                if ($item['type'] === 'script') {
                    $new = factory(User::class)->create(['status' => 'ACTIVE'])->toArray();
                } else {
                    $new = $faker->randomElement([factory(User::class)->create(['status' => 'ACTIVE'])->toArray(), factory(Group::class)->create(['status' => 'ACTIVE'])->toArray()]);
                    if (!isset($new['firstname'])) {
                        $new['id'] = 'group-' . $new['id'];
                    }
                }
                $item['value'] = $new;
            }

            $assignable[] = $item;
        }

        //Create assignments in Cancel Request and Edit Data
        $cancelGroup1 = factory(Group::class)->create(['name' => 'groupCancelRequest', 'status' => 'ACTIVE']);
        $cancelUser1 = factory(User::class)->create(['firstname' => 'userCancelRequest', 'status' => 'ACTIVE']);
        $ediGroup1 = factory(Group::class)->create(['name' => 'groupEditData', 'status' => 'ACTIVE']);
        $ediUser1 = factory(User::class)->create(['firstname' => 'userEditData', 'status' => 'ACTIVE']);
        $cancelRequest = [
            'users' => [$cancelUser1->id],
            'groups' => [$cancelGroup1->id]
        ];
        $editData = [
            'users' => [$ediUser1->id],
            'groups' => [$ediGroup1->id]
        ];

        $processId = $response->json('process')['id'];

        //Assignments after import process
        $response = $this->apiCall('POST', '/processes/' . $processId . '/import/assignments', [
            'assignable' => $assignable,
            'cancel_request' => $cancelRequest,
            'edit_data' => $editData
        ]);

        //Validate the header status code
        $response->assertStatus(204);

        $process = Process::find($processId);

        //Verify users and groups that can cancel request and edit data
        $this->assertEquals($cancelUser1->id, $process->usersCanCancel()->get()[0]->id);
        $this->assertEquals($cancelGroup1->id, $process->groupsCanCancel()->get()[0]->id);
        $this->assertEquals($ediGroup1->id, $process->groupsCanEditData()->get()[0]->id);
        $this->assertEquals($ediUser1->id, $process->usersCanEditData()->get()[0]->id);

        $definitions = $process->getDefinitions();

        //verify assignments in Start event, task and userTask
        $tags = ['startEvent', 'task', 'userTask'];
        foreach ($tags as $tag) {
            $elements = $definitions->getElementsByTagName($tag);
            foreach ($elements as $element) {
                $id = $element->getAttributeNode('id')->value;
                foreach ($assignable as $assign) {
                    if ($assign['id'] == $id) {
                        $value = $assign['value']['id'];
                        if (is_int($value)) {
                            $this->assertEquals('user', $element->getAttributeNode('pm:assignment')->value);
                            $this->assertEquals($value, $element->getAttributeNode('pm:assignedUsers')->value);
                        } else {
                            $value = explode('-', $value);
                            $value = $value[1];
                            $this->assertEquals('group', $element->getAttributeNode('pm:assignment')->value);
                            $this->assertEquals('group', $element->getAttributeNode('pm:assignment')->value);
                            $this->assertEquals($value, $element->getAttributeNode('pm:assignedGroups')->value);
                        }
                    }
                }
            }
        }

        //Verify assignments in callActivity
        $elements = $definitions->getElementsByTagName('callActivity');
        foreach ($elements as $element) {
            $id = $element->getAttributeNode('id')->value;
            foreach ($assignable as $assign) {
                if ($assign['id'] == $id) {
                    $this->assertEquals($assign['value']['id'], $element->getAttributeNode('calledElement')->value);
                }
            }
        }

        //Verify assignments in scripts run as
        foreach ($assignable as $assign) {
            if ($assign['type'] === 'script') {
                $script = Script::find($assign['id']);
                $this->assertEquals($assign['value']['id'], $script->run_as_user_id);
            }
        }
    }

    /**
     * Test import of invalid text file.
     *
     * @return void
     */
    public function testProcessImportInvalidTextFile()
    {
        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_invalid_text_file.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test import of invalid json file.
     *
     * @return void
     */
    public function testProcessImportInvalidJsonFile()
    {
        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_invalid_json_file.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test import of invalid base64 file.
     *
     * @return void
     */
    public function testProcessImportInvalidBase64File()
    {
        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_invalid_base64_file.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test import of invalid json file.
     *
     * @return void
     */
    public function testProcessImportInvalidBinaryFile()
    {
        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_invalid_bin_file.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Test import process with multiple multilevel assets.
     *
     * @return void
     */
    public function testImportMultipleAssets()
    {
        // Create a pre-existing screen and script
        factory(Screen::class, 2)->create(['title' => 'Existing Screen']);
        factory(Script::class, 2)->create(['title' => 'Existing Script']);

        // Assert that they now exist
        $this->assertDatabaseHas('screens', ['title' => 'Existing Screen']);
        $this->assertDatabaseHas('scripts', ['title' => 'Existing Script']);

        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_multiple_assets.json';

        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);

        $processes = Process::pluck('id', 'name');
        $this->assertArrayHasKey('Exporting Assets', $processes);

        // Assertion: all screens of the file were imported
        $screens = Screen::pluck('id', 'title');
        $this->assertArrayHasKey('Nested Screen', $screens);
        $this->assertArrayHasKey('Task Screen', $screens);
        $this->assertArrayHasKey('Task Intestitial Screen', $screens);
        $this->assertArrayHasKey('Start Intestitial Screen', $screens);
        $this->assertArrayHasKey('Summary Screen', $screens);
        $this->assertArrayHasKey('Details Screen', $screens);
        $this->assertArrayHasKey('Cancel Screen', $screens);
        $this->assertArrayHasKey('Manual Screen', $screens);

        // Assertion: all scritps of the file were imported
        $scripts = Script::pluck('id', 'title');
        $this->assertArrayHasKey('Script for Task', $scripts);
        $this->assertArrayHasKey('Script for Watcher', $scripts);

        $process = Process::find($processes['Exporting Assets']);
        $definitions = $process->getDefinitions();
        $ns = WorkflowServiceProvider::PROCESS_MAKER_NS;

        // Assertion: Verify references for screens used in tasks
        $ref = $definitions->findElementById('node_3')->getAttributeNS($ns, 'screenRef');
        $this->assertEquals($screens['Task Screen'], $ref);
        $ref = $definitions->findElementById('node_6')->getAttributeNS($ns, 'screenRef');
        $this->assertEquals($screens['Manual Screen'], $ref);

        // Assertion: Verify references for screens used as interstitial
        $ref = $definitions->findElementById('node_1')->getAttributeNS($ns, 'interstitialScreenRef');
        $this->assertEquals($screens['Start Intestitial Screen'], $ref);
        $ref = $definitions->findElementById('node_3')->getAttributeNS($ns, 'interstitialScreenRef');
        $this->assertEquals($screens['Task Intestitial Screen'], $ref);
        $ref = $definitions->findElementById('node_6')->getAttributeNS($ns, 'interstitialScreenRef');
        $this->assertEquals($screens['Task Intestitial Screen'], $ref);

        // Assertion: Verify references for screens used in process cancel and details
        $this->assertEquals($screens['Cancel Screen'], $process->cancel_screen_id);
        $this->assertEquals($screens['Details Screen'], $process->request_detail_screen_id);

        // Assertion: Verify references for scripts used in process
        $ref = $definitions->findElementById('node_4')->getAttributeNS($ns, 'scriptRef');
        $this->assertEquals($scripts['Script for Task'], $ref);

        // Verify reference of nested screen
        $screen = Screen::find($screens['Task Screen']);
        $this->assertEquals($screens['Nested Screen'], $screen->config[0]['items'][0]['config']['screen']);

        // Verify reference of watcher in nested screen
        $nested = Screen::find($screens['Nested Screen']);
        //$this->assertEquals($screens['Nested Screen'], $screen->config[0]['items'][0]['config']['screen']);
        $this->assertEquals('script-' . $scripts['Script for Watcher'], $nested->watchers[0]['script']['id']);
        $this->assertEquals($scripts['Script for Watcher'], $nested->watchers[0]['script_id']);
    }

    public function testNestedScreensRecursion()
    {
        $this->spy(Screen::class, function ($mock) {
            $mock->shouldNotReceive('findOrFail');
        });

        $content = file_get_contents(
            __DIR__ . '/../../Fixtures/nested_screen_process.json'
        );
        $result = ImportProcess::dispatchNow($content);
        $processId = $result->process->id;
        $this->apiCall('POST', "/processes/{$processId}/export");
    }
}
