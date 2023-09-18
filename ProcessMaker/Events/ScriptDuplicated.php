<?php

namespace ProcessMaker\Events;

class ScriptDuplicated extends ScriptCreated
{
    public function getEventName(): string
    {
        return 'ScriptDuplicated';
    }
}
