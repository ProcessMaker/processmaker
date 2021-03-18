<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Model\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;

class CallProcess extends BpmnAction
{

    public $definitionsId;
    public $processId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ProcessInterface $process, array $data)
    {
        $this->definitionsId = $definitions->id;
        $this->processId = $process->getId();
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(ProcessInterface $process)
    {
        //Create an initial data store for the process instance
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($this->data);

        //Call the process
        $instance = $process->call($dataStorage);
        return $instance;
    }
}
