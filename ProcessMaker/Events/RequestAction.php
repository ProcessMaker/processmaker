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
     * 
     * @return array
     */
    public function getData(): array
    {
        $actionAt = '';
        
        switch ($this->action) {
            case 'CREATED' :
                $actionAt = 'created_at';
                break;
            case 'CANCELLED' :
                $actionAt = 'cancelled_at';
                break;
            case 'COMPLETED' :
                $actionAt = 'completed_at';
                break;
        }

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
            $actionAt => Carbon::now()
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
