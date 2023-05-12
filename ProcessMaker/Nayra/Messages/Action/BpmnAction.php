<?php

namespace ProcessMaker\Nayra\Messages\Action;

class BpmnAction
{
    private $action;

    private $bpmnId;

    private $params;

    private $state;

    public function __construct(string $action, string $bpmnId, array $params, string $state)
    {
        $this->action = $action;
        $this->bpmnId = $bpmnId;
        $this->params = $params;
        $this->state = $state;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getBpmnId(): string
    {
        return $this->bpmnId;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
