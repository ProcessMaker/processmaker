<?php

namespace ProcessMaker;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

class PmqlHelper {
    private $type;

    private $statusMap = [
        'In Progress' => 'ACTIVE',
        'Completed' => 'COMPLETED',
        'Error' => 'ERROR',
        'Canceled' => 'CANCELED',
    ];

    private $taskStatusMap = [
        'In Progress' => 'ACTIVE',
        'Completed' => 'CLOSED',
    ];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function aliases()
    {
        return function($expression) {
            $field = $expression->field->field();
            if (is_string($field)) {
                $method_name = $this->type . ucfirst($field);

                if (method_exists($this, $method_name)) {
                    $value = $expression->value->value();
                    return $this->$method_name($value);
                }
            }
        };
    }

    private function requestRequest($value)
    {
        return function($query) use ($value) {
            $processes = Process::where('name', $value)->get();
            $query->whereIn('process_id', $processes->pluck('id'));
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
        $user = User::where('username', $value)->get()->first();
        $requests = ProcessRequest::where('user_id', $user->id)->get();

        return function($query) use ($requests) {
            $query->whereIn('id', $requests->pluck('id'));
        };
    }

    private function requestParticipant($value)
    {
        $user = User::where('username', $value)->get()->first();
        $tokens = ProcessRequestToken::where('user_id', $user->id)->get();

        return function($query) use ($tokens) {
            $query->whereIn('id', $tokens->pluck('process_request_id'));
        };
    }

    private function taskStatus($value)
    {
        return function($query) use ($value) {
            if (array_key_exists($value, $this->taskStatusMap)) {
                $query->where('status', $this->taskStatusMap[$value]);
            } else {
                $query->where('status', $value);
            }
        };
    }

    private function taskTask($value)
    {
        return function($query) use($value) {
            $query->where('element_name', $value);
        };
    }

    private function taskRequest($value)
    {
        return function($query) use ($value) {
            $processes = Process::where('name', $value);
            $query->whereIn('process_id', $processes->pluck('id'));
        };
    }
}
