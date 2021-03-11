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
        if (config('auth.log_auth_events')) {
            $class = get_class($event);
            
            if (array_key_exists($class, $this->eventTypes)) {
                $eventType = $this->eventTypes[$class];
                
                if (isset($event->user)) {
                    $userId = $event->user->id;
                } else {
                    $userId = null;
                }
                
                $userAgent = $this->userAgent();
                
                SecurityLog::create([
                   'event' => $eventType,
                   'ip' => request()->ip(),
                   'meta' => [
                       'user_agent' => $userAgent->string,
                       'browser' => [
                           'name' => $userAgent->browser->name,
                           'version' => $userAgent->browser->version,
                       ],
                       'os' => [
                           'name' => $userAgent->os->name,
                           'version' => $userAgent->os->version,
                       ]
                   ],
                   'user_id' => $userId,
                ]);
            }
        }
    }
    
    private function userAgent()
    {
        $string = request()->headers->get('User-Agent');
        $parsed = new Parser($string);
        
        $object = (object) [
            'string' => $string,
            'browser' => (object) [
               'name' => null,
               'version' => null,
           ],
           'os' => (object) [
               'name' => null,
               'version' => null,
           ]
        ];
        
        if ($parsed->browser->name) {
            $object->browser->name = $parsed->browser->name;
        }
        
        if ($parsed->browser->version) {
            $object->browser->version = $parsed->browser->version->toString();
        }
        
        if ($parsed->os->name) {
            $object->os->name = $parsed->os->name;
        }
        
        if ($parsed->os->version) {
            $object->os->version = $parsed->os->version->toString();
        }
        
        return $object;
    }
}
