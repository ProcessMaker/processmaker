<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;

class TokenDeleted implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Token $deleted_values)
    {
        $this->user = Auth::user();
        $this->data = [
            "Token" => $deleted_values->id
        ];
    }
    
    public function getData(): array
    {
        return $this->data;
    }

    public function getEventName(): string
    {
        return 'TokenDeleted';
    }
}
