<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Laravel\Passport\Token;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TokenDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    public $user;
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Token $deleted_values)
    {
        $this->data = [
            "Token" => $deleted_values->id
        ];
    }
    
    /**
     * Return event data 
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'TokenDeleted';
    }
}
