<?php

namespace ProcessMaker\Traits;

trait DataConnectionTrait
{

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        // Set the required date format for SQL Server
        return config('database.connections.data.driver') === 'sqlsrv' ? 'Y-m-d H:i:s' : parent::getDateFormat();
    }
}
