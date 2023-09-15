<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\ProcessRequestToken;

class ActivityReassignment implements SecurityLogEventInterface
{
    use Dispatchable;

    public const ACTION_REASSIGNMENT = 'REASSIGNMENT';

    private ProcessRequestToken $processRequest;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequestToken $processRequest)
    {
        $this->processRequest = $processRequest;
    }

    /**
     * Return event data
     */
    public function getData(): array
    {
        return [
            'name_action' => $this::ACTION_REASSIGNMENT,
            'request' => [
                'label' => $this->processRequest->getAttribute('process_request_id'),
                'link' => route('requests.show', ['request' => $this->processRequest->getAttribute('process_request_id')]),
            ],
            'task' => $this->processRequest->getAttribute('element_name'),
            'process' => $this->processRequest->process->name ?? '',
            'actionated_at' => $this->processRequest->getAttribute('updated_at'),
        ];
    }

    /**
     * Return event changes
     */
    public function getChanges(): array
    {
        return [
            'request_id' => $this->processRequest->getAttribute('process_request_id'),
            'process_id' => $this->processRequest->process->id,
        ];
    }

    /**
     * Return event name
     */
    public function getEventName(): string
    {
        return 'ActivityReassignment';
    }
}
