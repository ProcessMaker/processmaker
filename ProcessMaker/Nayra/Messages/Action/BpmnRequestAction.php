<?php

namespace ProcessMaker\Nayra\Messages\Action;

class BpmnRequestAction extends BpmnAction
{
    private $collaborationId;

    public function __construct(string $action, string $bpmnId, string $collaborationId, array $params, string $state)
    {
        parent::__construct($action, $bpmnId, $params, $state);
        $this->collaborationId = $collaborationId;
    }

    public function getCollaborationId(): string
    {
        return $this->collaborationId;
    }
}
