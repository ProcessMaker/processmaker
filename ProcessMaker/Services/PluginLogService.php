<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use ProcessMaker\Events\PluginLog;

class PluginLogService
{
    public function running(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'running', $pluginName, $userId);
    }

    public function error(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'error', $pluginName, $userId);
    }

    public function done(string $message, string $pluginName, ?int $userId = null): void
    {
        $this->log($message, 'done', $pluginName, $userId);
    }

    public function log(string $message, string $type, string $pluginName, ?int $userId = null): void
    {
        $message = self::redactHttpUserInfoFromString($message);

        $type === 'error' ? \Log::error($message) : \Log::info($message);

        if ($userId) {
            event(new PluginLog($message, $type, $pluginName, $userId));
        }
    }

    public static function redactHttpUserInfoFromString(string $message): string
    {
        $message = preg_replace('#://[^/\s:@]+:[^/\s@]+@#', '://', $message) ?? $message;

        return preg_replace('#://[^/\s@]+@#', '://', $message) ?? $message;
    }
}
