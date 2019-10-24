<?php
namespace Tests\Model;

use Tests\TestCase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class SystemResourceEncryptionTest extends TestCase
{
    private function assertEncryptedValues($instance, $field)
    {
        $table = $instance->getTable();
        $connection = $instance->getConnection()->getName();

        $result = \DB::connection($connection)
            ->select('select * from ' . $table);
        $raw = $result[0]->$field;

        $instance->refresh(); // reload from db
        $this->assertEquals($instance->$field, decrypt($raw));
    }

    public function testEncryptedValues() {
        $processCategory = factory(ProcessCategory::class)->create(['is_system' => true]);
        
        $bpmn = file_get_contents(base_path('database/processes/templates/SingleTask.bpmn'));
        $process = factory(Process::class)->create([
            "process_category_id" => $processCategory->id,
            "bpmn" => $bpmn,
        ]);

        $this->assertEncryptedValues($process, 'bpmn');

        $processRequest = factory(ProcessRequest::class)->create([
            "process_id" => $process->id,
            "data" => ['foo' => 'bar']
        ]);

        $this->assertEncryptedValues($processRequest, 'data');
        
        $task = factory(ProcessRequestToken::class)->create([
            "process_id" => $process->id,
            "process_request_id" => $processRequest->id,
            "data" => ['foo' => 'bar']
        ]);
        $this->assertEncryptedValues($task, 'data');


        $scriptCategory = factory(ScriptCategory::class)->create([
            "is_system" => true,
        ]);
        $script = factory(Script::class)->create([
            "script_category_id" => $scriptCategory->id,
            "code" => 'some code here;'
        ]);
        $this->assertEncryptedValues($script, 'code');

        $screenCategory = factory(ScreenCategory::class)->create([
            'is_system' => true
        ]);
        $screen = factory(Screen::class)->create([
            "screen_category_id" => $screenCategory->id,
            "config" => '[{}]'
        ]);
        $this->assertEncryptedValues($screen, 'config');
    }

    public function testHandleExistingUnencrypted() {
        $scriptCategory = factory(ScriptCategory::class)->create([
            "is_system" => true,
        ]);
        $scriptId = \DB::connection('processmaker')
            ->table('scripts')
            ->insertGetId([
                'code' => 'code',
                'title' => 'title',
                'script_category_id' => $scriptCategory->id
            ]);
        $script = Script::find($scriptId);
        $this->assertEquals('code', $script->code);
        
    }
}