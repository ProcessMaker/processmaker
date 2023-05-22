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

class UserUpdated implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

 
    private User $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        //dd($user->getAttributes());
        $this->user = $user;

    }

    public function getData(): array
    {
        $old_data = array_diff_assoc($this->user->getOriginal(), $this->user->getAttributes());
        $prefixOld = "- ";
        $newOldKeys = array_map(function($key) use ($prefixOld) {
            return $prefixOld . $key;
        }, array_keys($old_data));
        $prefix_old_data  = array_combine($newOldKeys, array_values($old_data));

        $new_data = array_diff_assoc($this->user->getAttributes(),$this->user->getOriginal());
        $prefixNew = "+ ";
        $newNewKeys = array_map(function($key) use ($prefixNew) {
            return $prefixNew . $key;
        }, array_keys($new_data));
        $prefix_new_data  = array_combine($newNewKeys, array_values($new_data));

        
        
      
        return [
            'username' => $this->user->getAttribute('username'),
            array_merge($prefix_old_data,$prefix_new_data)
        ];

    }

    public function getEventName(): string
    {
        return 'UserUpdated';
    }
}
