<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class RequestError implements SecurityLogEventInterface
{
    use Dispatchable;
    
    private ProcessRequest $data;
    private string $error;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ProcessRequest $data, string $error)
    {
        $this->data = $data;
        $this->error = $error;
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return [
            'request' => [
                'label' => $this->data->getAttribute('id'),
                'link' => route('requests.show', $this->data)
            ],
            'error' => $this->error,
            'occurred_at' => Carbon::now()
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
        return 'RequestError';
    }
}
