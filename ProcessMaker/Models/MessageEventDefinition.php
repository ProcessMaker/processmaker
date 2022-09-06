<?php

namespace ProcessMaker\Models;

use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Nayra\Bpmn\Models\MessageEventDefinition as Base;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;

/**
 * Implementation of the message element.
 */
class MessageEventDefinition extends Base
{
    /**
     * Implement the event definition behavior when an event is triggered.
     *
     * @param EventDefinitionInterface $event
     * @param FlowNodeInterface $target
     * @param ExecutionInstanceInterface|null $targetRequest
     * @param TokenInterface|null $token
     *
     * @return $this
     */
    public function execute(EventDefinitionInterface $event, FlowNodeInterface $target, ExecutionInstanceInterface $targetRequest = null, TokenInterface $token = null)
    {
        // Set collaboration
        $parent = $token ? $token->getInstance() : null;
        $child = $targetRequest;

        if ($parent && $child) {
            $collaboration_id = $parent->process_collaboration_id ?: $child->process_collaboration_id;
            if (!$collaboration_id) {
                $collaboration = new ProcessCollaboration();
                $collaboration->process_id = $parent->process->getKey();
                $collaboration->saveOrFail();
                $collaboration_id = $collaboration->getKey();
            }
            if (!$parent->process_collaboration_id) {
                $parent->process_collaboration_id = $collaboration_id;
                $parent->saveOrFail();
            }
            if (!$child->process_collaboration_id) {
                $child->process_collaboration_id = $collaboration_id;
                $child->saveOrFail();
            }
        }

        return $this;
    }
}
