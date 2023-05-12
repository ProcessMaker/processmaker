<?php

namespace ProcessMaker\Nayra\Messages\Action;

class BpmnResult
{
    private $transactions;

    private $state;

    public function __construct(array $transactions, string $state)
    {
        $this->transactions = $transactions;
        $this->state = $state;
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
