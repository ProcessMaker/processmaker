<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Laravel\Passport\Token;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TokenCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;
    public $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Token $created_values)
    {
        $this->data = [
            "Token Id" => $created_values->id
        ];
        $this->changes = [$created_values];
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Return event changes 
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'TokenCreated';
    }
}
