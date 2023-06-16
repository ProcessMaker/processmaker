<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class RequestAction implements SecurityLogEventInterface
{
    use Dispatchable;

    const ACTION_CREATED = 'CREATED';
    const ACTION_CANCELED = 'CANCELED';
    const ACTION_COMPLETED = 'COMPLETED';
    const ACTIONS = [
        'COMPLETED' => 'completed_at',
        'CANCELED' => 'canceled_at',
        'CREATED' => 'created_at',
    ];
    
    private ProcessRequest $data;
    private string $action;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $data, string $action)
    {
        $this->data = $data;
        $this->action = $action;
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        $parentProcess = Process::find($this->data->getAttribute('process_id'));
        return [
            'process' => [
                'label' => $parentProcess->getAttribute('name'),
                'link' => route('modeler.show', $parentProcess)
            ],
            'request' => [
                'label' => $this->data->getAttribute('id'),
                'link' => route('requests.show', $this->data)
            ],
            'action' => $this->action,
            $this::ACTIONS[$this->action] => Carbon::now()
        ];
    }
    
    /**
     * Return event changes 
     */
    public function getChanges(): array
    {
        return [];
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'RequestAction';
    }
}
