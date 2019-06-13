<?php

namespace ProcessMaker\Traits;

trait SqlsrvSupportTrait
{
    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        // Set the required date format for SQL Server
        return config('database.connections.data.date_format')
            ? config('database.connections.data.date_format') : parent::getDateFormat();
    }
}
