<?php
namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

class CatchEvent extends BpmnAction
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
    public function __construct(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, array $data)
    {
        $this->definitionsId = $definitions->getKey();
        $this->instanceId = $instance->getKey();
        $this->tokenId = $token->getKey();
        $this->data = $data;
    }

    /**
     * Start a $process from start event $element.
     *
     * @param TokenInterface $token
     * @param CatchEventInterface $element
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function action(TokenInterface $token, CatchEventInterface $element)
    {
        $dataStore = $token->getInstance()->getDataStore();

//        foreach ($data as $key => $value) {
//            $dataStore->putData($key, $value);
//        }

        $element->execute($element->getEventDefinitions()->item(0), $token->getInstance());
    }
}
