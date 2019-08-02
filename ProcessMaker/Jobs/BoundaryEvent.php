<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\BoundaryEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\DataStoreInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\MessageEventDefinitionInterface;

class BoundaryEvent extends BpmnAction
{
    public $definitionsId;
    public $processId;
    public $elementId;
    public $boundaryEventId;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Definitions $definitions, ExecutionInstanceInterface $instance, TokenInterface $token, BoundaryEventInterface $boundary, array $data)
    {
        $this->definitionsId = $definitions->getKey();
        $this->instanceId = $instance->getKey();
        $this->tokenId = $token->getKey();
        $this->boundaryEventId = $boundary->getId();
        $this->data = $data;
    }

    /**
     * Start a $process from catch event $element.
     *
     * @param TokenInterface $token
     * @param CatchEventInterface $element
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function action(BpmnDocumentInterface $definitions, TokenInterface $token)
    {
        $element = $definitions->getBoundaryEvent($this->boundaryEventId);
        $dataStore = $token->getInstance()->getDataStore();

        $element->execute($element->getEventDefinitions()->item(0), $token->getInstance());

        foreach ($element->getEventDefinitions() as $eventDefinition) {
            if ($eventDefinition instanceof MessageEventDefinitionInterface) {
                $this->messageEventUpdateData($eventDefinition, $dataStore);
            }
        }
    }

    /**
     * Updata data for a message event
     *
     * If variableName is set, then the event payload will be set to that variable name.
     * If the data name exists, then the data is merged.
     *
     * @param DataStoreInterface $dataStore
     * @return void
     */
    private function messageEventUpdateData(MessageEventDefinitionInterface $eventDefinition, DataStoreInterface $dataStore)
    {
        $variableName = $eventDefinition->getProperty('variableName');
        $variableName = $variableName === 'undefined' ? '' : $variableName;
        if ($variableName) {
            $dataStore->putData($variableName, $this->data);
        } else {
            foreach ($this->data as $key => $value) {
                $dataStore->putData($key, $value);
            }
        }
    }
}
