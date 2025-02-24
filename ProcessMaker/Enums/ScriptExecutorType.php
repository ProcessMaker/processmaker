<?php

namespace ProcessMaker\Enums;

enum ScriptExecutorType:string
{
    case System = 'system';
    case Custom = 'custom';
    case Duplicate = 'duplicate';
}
