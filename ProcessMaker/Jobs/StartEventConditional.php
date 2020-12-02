<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;

class StartEventConditional extends BpmnAction
{
    public $definitionsId;

    /**
     * Create a new job instance.
     *
     * @param Definitions $definitions
     *
     * @return void
     */
    public function __construct(Definitions $definitions)
    {
        $this->definitionsId = $definitions->getKey();
    }

    /**
     * Conditional event
     *
     * This Job only uses the base behavior of BpmnAction to load and evaluate the conditional event
     * it not requires any additional behavior.
     */
    public function action()
    {
        return true;
    }
}
