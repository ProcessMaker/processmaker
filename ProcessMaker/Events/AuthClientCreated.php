<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($created_values)
    {
        $this->data = $created_values;
    }
    
    public function getData(): array
    {
        return $this->data;
    }

    public function getEventName(): string
    {
        return 'AuthClientCreated';
    }
}
