<?php
namespace ProcessMaker\Listeners;

use Carbon\Carbon;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;

class SecurityLogger
{
    private $types = [
        'Illuminate\Auth\Events\Failed' => 'attempt',
        'Illuminate\Auth\Events\Login' => 'login',
        'Illuminate\Auth\Events\Logout' => 'logout',
    ];
    
    /**
     * Handle the event.
     *
     * @param  Illuminate\Auth\Events\Logout  $event
     * @return void
     */
    public function handle($event)
    {
        $class = get_class($event);
        
        if (array_key_exists($class, $this->types)) {
            $type = $this->types[$class];
            
            if (isset($event->user)) {
                $userId = $event->user->id;
            } else {
                $userId = null;
            }
            
            SecurityLog::create([
               'type' => $type,
               'ip' => request()->ip(),
               'user_agent' => request()->headers->get('User-Agent'),
               'user_id' => $userId,
            ]);
        }
    }
}
