<?php

namespace ProcessMaker;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class PmqlHelper {
    private $type;
    
    private $statusMap = [
        'In Progress' => 'ACTIVE',
        'Completed' => 'COMPLETED',
        'Error' => 'ERROR',
        'Canceled' => 'CANCELED',
    ];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function aliases()
    {
        return function($expression) {
            $field = $expression->field->field();
            $method_name = $this->type . ucfirst($field);

            if (method_exists($this, $method_name)) {
                $value = $expression->value->value();
                return $this->$method_name($value);
            }
        };
    }

    private function requestRequest($value)
    {
        return function($query) use ($value) {
            $processes = Process::where('name', $value)->get();
            $query->whereIn('process_id', $processes->pluck('process_id'));
        };
    }

    private function requestStatus($value)
    {
        return function($query) use ($value) {
            if (array_key_exists($value, $this->statusMap)) {
                $query->where('status', $this->statusMap[$value]);
            } else {
                $query->where('status', $value);
            }
        };
    }

    private function requestRequester($value)
    {
        return function($query) use ($value) {
            $requests = ProcessRequest::whereHas('user', function($query) use ($value) {
                $query->where('username', $value);
            })->get();
            $query->whereIn('id', $requests->pluck('id'));
        };
    }
    
    private function requestParticipant($value)
    {
        return function($query) use ($value) {
            $requests = ProcessRequest::whereHas('participants', function($query) use ($value) {
                $query->where('username', $value);
            })->get();
            $query->whereIn('id', $requests->pluck('id'));
        };
    }
}