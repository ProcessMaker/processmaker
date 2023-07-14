<?php

namespace ProcessMaker\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;

class DBHelper
{
    /**
     * Check DB connection health check.
     */
    public static function db_health_check()
    {
        // check if connection is still alive
        try {
            DB::connection()->getDatabaseName();
        } catch (PDOException $e) {
            Log::error('db_health_check: ' . $e->getMessage());
            DB::reconnect();
        }
    }
}
