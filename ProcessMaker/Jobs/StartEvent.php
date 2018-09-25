<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;

class StartEvent extends BpmnAction
{

    public $definitionsId;
    public $processId;
    public $elementId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, StartEventInterface $event, array $data)
    {
        $this->definitionsId = $definitions->uuid_text;
        $this->processId = $event->getOwnerProcess()->getId();
        $this->elementId = $event->getId();
        $this->data = $data;
    }

    /**
     * Start a $process from start event $element.
     *
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function action(ProcessInterface $process, StartEventInterface $element)
    {
        //Create a new data store
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($this->data);
        $instance = $process->getEngine()->createExecutionInstance($process, $dataStorage);
        $element->start();
        
        //Return the instance created
        return $instance;
    }
}
