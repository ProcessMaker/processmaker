<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\Events\ImportLog;

class Logger
{
    public function __construct(public int $userId)
    {
    }

    public function log($message, $additionalParams = [])
    {
        $this->dispatch('log', $message, $additionalParams);
    }

    public function warn($message)
    {
        $this->dispatch('warn', $message);
    }

    public function error($message)
    {
        $this->dispatch('error', $message);
    }

    public function exception($e)
    {
        $this->dispatch('error', get_class($e) . ': ' . $e->getMessage());
    }

    private function dispatch($type, $message, $additionalParams = [])
    {
        ImportLog::dispatch($this->userId, $type, $message, $additionalParams);
    }
}
