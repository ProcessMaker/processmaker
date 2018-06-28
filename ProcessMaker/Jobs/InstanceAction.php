<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Model\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;

abstract class InstanceAction extends BpmnAction
{

    public $instanceId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ProcessInterface $process, ExecutionInstanceInterface $instance)
    {
        parent::__construct($definitions, $process);
        $this->instanceId = $instance->uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            //Load the process definition
            $definitions = Definitions::find($this->definitionsId);
            $workflow = $definitions->getDefinitions();

            //Get the reference to the process
            $process = $workflow->getProcess($this->processId);

            //Load process instance
            $instance = $workflow->getEngine()->loadExecutionInstance($this->instanceId);

            //Do the action
            App::call([$this, 'action'], compact('workflow', 'process', 'instance'));

            //Run engine to the next state
            $workflow->getEngine()->runToNextState();
        } catch (\Throwable $t) {
            dd($t);
        }
    }
}
