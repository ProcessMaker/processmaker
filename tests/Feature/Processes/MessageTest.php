<?php

namespace Tests\Feature\Processes;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RequestHelper;

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    public function test()
    {
        $script = Script::factory()->create([
            'language' => 'php',
            'code' => '<?php return $config; ?>',
            'run_as_user_id' => $this->user->id,
        ]);

        $process = Process::factory()->create([
            'bpmn' => str_replace(
                '[script_id]',
                $script->id,
                file_get_contents(__DIR__ . '/../../Fixtures/message_test.bpmn')
            ),
        ]);

        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_1');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $request->refresh();
        $this->assertEquals('COMPLETED', $request->status);

        // Data should contain foo=>bar from the first pool but not
        // bar=>baz from the other pool
        $this->assertEquals(['foo' => 'bar'], $request->data);
    }
}
