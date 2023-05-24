<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class UserUpdated implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels, FormatSecurityLogChanges;

 
    private User $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {

        $this->user = $user;

    }

    public function getData(): array
    {   
         $old_data = array_diff_assoc($this->user->getOriginal(), $this->user->getAttributes());
         $new_data = array_diff_assoc($this->user->getAttributes(),$this->user->getOriginal());
           
        return [
            'username' => $this->user->getAttribute('username'),
            $this->formatChanges($new_data,$old_data)
        ];

    }

    public function getChanges(): array
    {
        // return $this->changes;
        return $this->user->getAttributes();
    }

    public function getEventName(): string
    {
        return 'UserUpdated';
    }
}
