<?php

namespace ProcessMaker\Models;

class ScriptExecutorVersion extends ProcessMakerModel
{
    protected $fillable = [
        'title', 'description', 'language', 'config',
    ];
}
