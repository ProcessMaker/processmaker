<?php
namespace ProcessMaker\Listeners;

use Carbon\Carbon;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use WhichBrowser\Parser;

class SecurityLogger
{
    private $eventTypes = [
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
        
        if (array_key_exists($class, $this->eventTypes)) {
            $eventType = $this->eventTypes[$class];
            
            if (isset($event->user)) {
                $userId = $event->user->id;
            } else {
                $userId = null;
            }
            
            $parsed = new Parser(request()->headers->get('User-Agent'));
            
            SecurityLog::create([
               'event' => $eventType,
               'ip' => request()->ip(),
               'meta' => [
                   'user_agent' => request()->headers->get('User-Agent'),
                   'browser' => [
                       'name' => $parsed->browser->name,
                       'version' => $parsed->browser->version->toString(),
                   ],
               ],
               'user_id' => $userId,
            ]);
        }
    }
}
