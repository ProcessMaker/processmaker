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
        'ProcessMaker\Events\ScriptExecutorCreated' => 'scriptExecutorCreated',
        'ProcessMaker\Events\ScriptExecutorUpdated' => 'scriptExecutorUpdated',
        'ProcessMaker\Events\ScriptExecutorDeleted' => 'scriptExecutorDeleted'
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

                if (isset($event->created_values)) {
                    $created_values = $event->created_values;
                } else {
                    $created_values = null;
                }

                if(isset($event->original_values) && isset($event->changed_values)) {
                    $original_values = $event->original_values;
                    $changed_values = $event->changed_values;
                } else {
                    $original_values = null;
                    $changed_values = null;
                }

                if (isset($event->deleted_values)) {
                    $deleted_values = $event->deleted_values;
                } else {
                    $deleted_values = null;
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
                        ],
                        'created_values' => $created_values,
                        'original_values' => $original_values,
                        'changed_values' => $changed_values,
                        'deleted_values' => $deleted_values
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
            ],
        ];

        if (isset($parsed->browser->name)) {
            $object->browser->name = $parsed->browser->name;
        }

        if (isset($parsed->browser->version)) {
            $object->browser->version = $parsed->browser->version->toString();
        }

        if (isset($parsed->os->name)) {
            $object->os->name = $parsed->os->name;
        }

        if (isset($parsed->os->version)) {
            $object->os->version = $parsed->os->version->toString();
        }

        return $object;
    }
}
