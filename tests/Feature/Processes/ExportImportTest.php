<?php

namespace Tests\Feature;

use Faker\Factory as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ProcessMaker\Providers\WorkflowServiceProvider;

class ExportImportTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;
    
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
        $this->assertDatabaseHas('screens', ['id' => 1, 'title' => 'Existing Screen']);
        $this->assertDatabaseHas('scripts', ['id' => 1, 'title' => 'Existing Script']);
        
        // Set path and name of file to import
        $filePath = 'tests/storage/process/';
        $fileName = 'test_process_import_refs.spark';
        
        // Load file to import
        $file = new UploadedFile(base_path($filePath) . $fileName, $fileName, null, null, null, true);

        // Import process
        $response = $this->apiCall('POST', '/processes/import', [
            'file' => $file,
        ]);
        
        // Assert newly imported process and elements exist
        $this->assertDatabaseHas('processes', ['id' => 1, 'name' => 'Test Process 01']);
        $this->assertDatabaseHas('screens', ['id' => 2, 'title' => 'Screen 01']);
        $this->assertDatabaseHas('screens', ['id' => 3, 'title' => 'Screen 02']);
        $this->assertDatabaseHas('scripts', ['id' => 2, 'title' => 'Script 01']);
        $this->assertDatabaseHas('scripts', ['id' => 3, 'title' => 'Script 02']);
        
        // Get BPMN
        $definitions = Process::find(1)->getDefinitions();
        
        // Check screen refs & script refs
        $this->checkScreenRefs($definitions);
        $this->checkScriptRefs($definitions);
    }

    /**
     * Part of TestProcessImportRefs: check screen references in BPMN.
     *
     * @return void
     */
    private function checkScreenRefs($definitions)
    {
        // Map of names to their expected reference ID
        $map = [
            'Human Task 1' => 2,
            'Human Task 2' => 3,
        ];
        
        // For each of the human tasks...
        $tasks = $definitions->getElementsByTagName('task');
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
    private function checkScriptRefs($definitions)
    {
        // Map of names to their expected reference ID
        $map = [
            'Script Task 1' => 2,
            'Script Task 2' => 3,
        ];

        // For each of the script tasks...        
        $tasks = $definitions->getElementsByTagName('scriptTask');
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
        $response->assertHeader('content-disposition', 'attachment; filename=leave_absence_request.spark');

        // Get our file contents (we have to do it this way because of
        // Symfony's weird response API)
        ob_start();
        $content = $response->sendContent();
        $content = ob_get_clean();

        // Save the file contents and convert them to an UploadedFile
        $fileName = tempnam(sys_get_temp_dir(), 'exported');
        file_put_contents($fileName, $content);
        $file = new UploadedFile($fileName, 'leave_absence_request.spark', null, null, null, true);

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
        $response->assertJsonStructure(['status' => [], 'assignable' => []]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('processes', ['name' => 'Leave Absence Request 2']);
        $this->assertDatabaseHas('screens', ['title' => 'Request Time Off 2']);
        $this->assertDatabaseHas('scripts', ['title' => 'Get available days Script 2']);
    }

    /**
     * Test assignments after import process.
     */
    public function test_assignmets_after_import()
    {
        // Load file to import
        $file = new UploadedFile(base_path('tests/storage/process/') . 'test_process_import.spark', 'test_process_import.spark', null, null, null, true);

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
}
