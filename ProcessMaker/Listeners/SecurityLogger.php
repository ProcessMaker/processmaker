<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Contracts\SecurityLogEventInterface;
use ProcessMaker\Helpers\SensitiveDataHelper;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\MediaLog;
use ProcessMaker\Models\SecurityLog;
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
     * @param \Illuminate\Auth\Events\Failed|\Illuminate\Auth\Events\Login|\Illuminate\Auth\Events\Logout|SecurityLogEventInterface  $event
     * @return void
     */
    public function handle($event)
    {
        $specificEvents = ['FilesDownloaded', 'FilesAccessed', 'FilesCreated', 'FilesDeleted', 'FilesUpdated'];
        $class = get_class($event);

        if ($event instanceof SecurityLogEventInterface) {
            $data = $event->getData();
            $changes = $event->getChanges();
            SecurityLog::create([
                'event' => $event->getEventName(),
                'ip' => request()->ip(),
                'meta' => $this->getMeta(),
                'user_id' => isset($event->user) ? $event->user->id : Auth::id(),
                'data' => $data ? SensitiveDataHelper::parseArray($data) : null,
                'changes' => $changes ? SensitiveDataHelper::parseArray($changes) : null,
            ]);
            if (in_array($event->getEventName(), $specificEvents)) {
                MediaLog::create([
                    'event_type' => $event->getEventName(),
                    'media_id' => $data['name']['id'],
                    'user_id' => isset($event->user) ? $event->user->id : Auth::id(),
                ]);
            }
        } elseif (array_key_exists($class, $this->eventTypes)) {
            $eventType = $this->eventTypes[$class];
            SecurityLog::create([
                'event' => $eventType,
                'ip' => request()->ip(),
                'meta' => $this->getMeta(),
                'user_id' => isset($event->user) ? $event->user->id : null,
            ]);
        }
    }

    private function getMeta()
    {
        $userAgent = $this->userAgent();

        return [
            'user_agent' => $userAgent->string,
            'browser' => [
                'name' => $userAgent->browser->name,
                'version' => $userAgent->browser->version,
            ],
            'os' => [
                'name' => $userAgent->os->name,
                'version' => $userAgent->os->version,
            ],
        ];
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
