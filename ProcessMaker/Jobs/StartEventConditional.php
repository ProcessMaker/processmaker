<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;

class StartEventConditional extends BpmnAction
{
    public $definitionsId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions)
    {
        $this->definitionsId = $definitions->getKey();
    }

    /**
     *
     */
    public function action()
    {
    }
}
