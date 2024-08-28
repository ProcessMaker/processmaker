<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Models\ProcessRequest;

class HandleRedirectListener
{
    private static $processRequest = null;

    protected static $redirectionMethod = '';

    private static $redirectionParams = [];

    protected function setRedirectTo(ProcessRequest $processRequest, string $method, ...$params): void
    {
        self::$processRequest = $processRequest;
        self::$redirectionMethod = $method;
        self::$redirectionParams = $params;
    }

    public static function sendRedirectToEvent()
    {
        $method = self::$redirectionMethod;
        $params = self::$redirectionParams;
        $processRequest = self::$processRequest;

        if ($processRequest !== null) {
            $event = new RedirectToEvent($processRequest, $method, $params);
            event($event);
            // clean params to prevent send the same redirect multiple times
            self::$redirectionParams = [];
            self::$redirectionMethod = '';
            self::$processRequest = null;
        }
    }
}
