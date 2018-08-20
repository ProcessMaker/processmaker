<?php
namespace ProcessMaker\Jobs;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Model\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use Throwable;

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
    public function handle()
    {
        try {
            //Load the process definition
            $definitions = Definitions::find($this->definitionsId);
            $workflow = $definitions->getDefinitions();

            //Get the reference to the object
            $process = $workflow->getProcess($this->processId);

            //Do the action
            $response = App::call([$this, 'action'], compact('workflow', 'process'));

            //Run engine to the next state
            $workflow->getEngine()->runToNextState();
            
            return $response;
        } catch (Throwable $t) {
            Log::error($t->getMessage());
        }
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
