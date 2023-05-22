<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class AuthClientDeleted implements SecurityLogEventInterface
{
    use Dispatchable;

    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($deleted_values)
    {
        $this->data = $deleted_values;
    }
    
    public function getData(): array
    {
        return $this->data;
    }

    public function getEventName(): string
    {
        return 'AuthClientDeleted';
    }
}
