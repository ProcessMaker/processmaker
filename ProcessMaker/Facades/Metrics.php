<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Services\MetricsService;

class Metrics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MetricsService::class;
    }
}
