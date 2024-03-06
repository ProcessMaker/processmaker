<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Models\RequestDevice as RequestDeviceModel;

/**
 * @see \ProcessMaker\Models\RequestDevice
 *
 * @method static string getVariableName()
 * @method static string getId()
 */
class RequestDevice extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RequestDeviceModel::class;
    }
}
