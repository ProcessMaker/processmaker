<?php

namespace ProcessMaker\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class PermissionChanged implements SecurityLogEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels, FormatSecurityLogChanges;

    private Request $request;
    private $requestPermissions;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request,$requestPermissions)
    {
        $this->request = $request;
        $this->requestPermissions = $requestPermissions;
    }

    public function getData(): array
    {   
        //get Old User's permissions
        $oldPermission=$this->request->input('user_old_permission');

        //get User profile data
        $userData=User::find($this->request->input('user_id'));
            
        return [
            'username' => $userData->getAttribute('username'),
            '+ permission_names' => $this->requestPermissions,
            '- permission_names' => isset($oldPermission) ? $oldPermission : null
        ];

    }

    public function getChanges(): array
    {
        // return $this->changes;
        return $this->requestPermissions;
    }

    public function getEventName(): string
    {
        return 'PermissionChanged';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
