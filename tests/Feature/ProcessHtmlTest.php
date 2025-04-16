<?php

namespace Tests\Feature;

use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessHtmlTest extends TestCase
{
    use RequestHelper;


    /**
     * A process with html entities in the documentation field should be able to be loaded.
     * By default, the bpmn processes are loaded with the html entities support.
     */
    public function test_process_with_html_can_be_loaded()
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
        ]);
        $process = $this->createProcessFromBPMN('tests/Fixtures/process_with_html.bpmn');

        $definitions = $process->getDefinitions(true);

        $this->assertNotEmpty($definitions, 'The process could not be loaded correctly');
    }
}
