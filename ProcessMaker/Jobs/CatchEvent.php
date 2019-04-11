<?php

namespace ProcessMaker\Jobs;

use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\MessageEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\DataStoreInterface;

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
     * Start a $process from catch event $element.
     *
     * @param TokenInterface $token
     * @param CatchEventInterface $element
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface
     */
    public function action(TokenInterface $token, CatchEventInterface $element)
    {
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
     * If data name is set, then the event payload will be set to that data name.
     * If the data name exists, then the data is merged.
     *
     * @param DataStoreInterface $dataStore
     * @return void
     */
    private function messageEventUpdateData(MessageEventDefinitionInterface $eventDefinition, DataStoreInterface $dataStore)
    {
        $dataName = $eventDefinition->getProperty('dataName');
        $dataName = $dataName === 'undefined' ? '' : $dataName;
        if ($dataName) {
            $dataStore->putData($dataName, $this->data);
        } else {
            foreach ($this->data as $key => $value) {
                $dataStore->putData($key, $value);
            }
        }
    }
}
