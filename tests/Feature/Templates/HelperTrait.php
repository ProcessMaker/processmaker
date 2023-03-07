<?php

namespace Tests\Feature\Templates;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;

trait HelperTrait
{
    public $globalSignal = null;

    public function createProcess($bpmnPath, $attrs = [])
    {
        $bpmn = file_get_contents(__DIR__ . '/fixtures/' . $bpmnPath . '.bpmn.xml');

        return Process::factory()->create(
            array_merge(
                $attrs,
                [
                    'bpmn' => $bpmn,
                ]
            )
        );
    }

    public function createScreen($screenPath, $attrs = [], $watchersPath = null)
    {
        $config = json_decode(file_get_contents(__DIR__ . '/fixtures/' . $screenPath . '.json'), true);
        $watchers = $watchersPath ? json_decode(file_get_contents(__DIR__ . '/fixtures/' . $watchersPath . '.json'), true) : [];

        return Screen::factory()->create(
            [
                ...$attrs,
                ...[
                    'config' => $config,
                    'watchers' => $watchers,
                ],
            ]
        );
    }
}
