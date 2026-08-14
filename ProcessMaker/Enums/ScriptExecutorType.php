<?php

namespace ProcessMaker\Enums;

enum ScriptExecutorType:string
{
    case System = 'system';
    case Custom = 'custom';
    case Duplicate = 'duplicate';
    case Realtime = 'realtime';

    public function isCustomOrRealtime(): bool
    {
        return $this === self::Custom || $this === self::Realtime;
    }
}
