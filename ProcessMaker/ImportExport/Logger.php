<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\Jobs\ImportV2;

class Logger
{
    public $pid = null;

    public $userId = null;

    private $warnings = [];

    private int $totalSteps = 1;

    private int $currentStep = 1;

    public function __construct($userId = null)
    {
        $this->pid = getmypid();
        $this->userId = $userId;

        if ($userId) {
            Event::listen(MessageLogged::class, function (MessageLogged $e) {
                if ($e->level === 'warning') {
                    return;
                }
                $this->logToFile('Log::' . $e->level, $e->message, $e->context);
            });
        }
    }

    public function clear()
    {
        Storage::delete(ImportV2::LOG_PATH);
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

    public function status($message)
    {
        $this->dispatch('status', $message);
    }

    public function exception($e)
    {
        $this->dispatch('error', get_class($e) . ': ' . $e->getMessage() . ' ' . $e->getTraceAsString());
    }

    private function dispatch($type, $message, $additionalParams = [])
    {
        if (!$this->userId) {
            return;
        }

        ImportLog::dispatch($this->userId, $type, substr($message, 0, 1000), $additionalParams);
        $this->logToFile($type, $message, $additionalParams);
    }

    public function addWarning($message)
    {
        $this->warnings[] = $message;
    }

    private function logToFile($type, $message, $additionalParams = [])
    {
        $params = '';
        if (!empty($additionalParams)) {
            $params = ' additionalParams: ' . json_encode($additionalParams);
        }
        $datetime = date('Y-m-d H:i:s');
        Storage::append(ImportV2::LOG_PATH, "[$this->pid] [$this->userId] [$datetime] [$type] $message" . $params);
    }

    public function setSteps($payloads)
    {
        $this->currentStep = 0;
        $this->totalSteps = 0;

        foreach ($payloads as $payload) {
            // Multiply by 2 since each has a "saving" and an "associating" stage.
            $this->totalSteps += count($payload['export']) * 2;
        }
    }

    public function setStatus($type, $item = '')
    {
        if ($type === 'done') {
            $this->dispatch('status', 'done', $this->warnings);

            return;
        }

        $this->status(ucfirst($type) . ' ' . $item);
        $this->currentStep++;

        $this->dispatch('progress', round(($this->currentStep / $this->totalSteps) * 100));
    }
}
