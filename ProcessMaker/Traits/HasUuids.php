<?php

namespace ProcessMaker\Traits;

use Illuminate\Support\Str;

trait HasUuids
{
    /**
     * Generate an ordered UUID
     */
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }
}
