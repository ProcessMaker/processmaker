<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Model\Process as Definitions;
use Illuminate\Support\Facades\App;

class CallProcess extends BpmnAction
{

    public $definitionsId;
    public $processId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ProcessInterface $process, array $data)
    {
        $this->definitionsId = $definitions->id;
        $this->processId = $process->getId();
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
        } catch (\Throwable $t) {
            dd($t);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function action(ProcessInterface $process)
    {
        return $process->call();
    }
}
