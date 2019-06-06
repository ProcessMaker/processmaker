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

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        // Set the required date format for SQL Server
        return config('database.enable_external_connection') &&
        config('database.connections.data.driver') === 'sqlsrv' ? 'Y-m-d H:i:s' : parent::getDateFormat();
    }
}
