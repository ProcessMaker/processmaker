<?php

namespace ProcessMaker\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Laravel\Passport\Token;
use ProcessMaker\Contracts\SecurityLogEventInterface;

class TokenCreated implements SecurityLogEventInterface
{
    use Dispatchable;

    private array $data;

    private array $changes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Token $token)
    {
        $this->data = [
            'token_id' => $token->getKey(),
            'created_at' => Carbon::now(),
        ];
    }

    /**
     * Return event data
     * Return event data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Return event changes
     * Return event changes
     */
    public function getChanges(): array
    {
        return $this->data;
    }

    /**
     * return event name
     */
    public function getEventName(): string
    {
        return 'TokenCreated';
    }
}
