<?php

namespace ProcessMaker\Traits;

trait DataConnectionTrait
{
    /**
     * If database.enable_external_connection is enabled then
     * DATA connection is used, else SPARK connection is used
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('database.enable_external_connection') ? 'data' : 'spark';
    }
}
