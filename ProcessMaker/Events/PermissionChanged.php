<?php

namespace ProcessMaker\Events;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\FormatSecurityLogChanges;

class PermissionChanged implements SecurityLogEventInterface
{
    use Dispatchable, FormatSecurityLogChanges;

    private Request $request;
    private $requestPermissions;
    private $permissionType;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request,$requestPermissions,$permissionType)
    {
        $this->request = $request;
        $this->requestPermissions = $requestPermissions;
        $this->permissionType = $permissionType;
    }

    public function getData(): array
    {
        //get Old User's permissions
        $oldPermission = $this->request->input('user_old_permission');

        //get User profile data
        $userData = User::find($this->request->input('user_id'));
        if ($this->permissionType == "SuperAdmin") {
            return [
                'username' => $userData->getAttribute('username'),
                '+ permission_names' => 'Super Admin - All Permissions',
                '- permission_names' => isset($oldPermission) ? $oldPermission : null
            ];
        } else {
            return [
                'username' => $userData->getAttribute('username'),
                '+ permission_names' => $this->requestPermissions,
                '- permission_names' => isset($oldPermission) ? $oldPermission : null
            ];
        }
        

    }

    public function getChanges(): array
    {
        // return $this->changes;
        return isset($this->requestPermissions) ? $this->requestPermissions : [];
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
