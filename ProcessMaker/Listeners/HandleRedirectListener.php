<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Events\RedirectToEvent;
use ProcessMaker\Models\ProcessRequest;

class HandleRedirectListener
{
    private static $processRequest;
    private static $redirectionMethod = '';
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
        $event = new RedirectToEvent($processRequest, $method, $params);
        event($event);
    }
}
