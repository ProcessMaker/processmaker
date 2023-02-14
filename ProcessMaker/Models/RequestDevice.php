<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

/**
 * TODO: Create a better and more efficient way to identify devices, such as a random variable stored in localstorage and sent in the request header.
 */
class RequestDevice
{
    const VARIABLE_NAME = 'device_id';

    /**
     * @return string
     */
    public function getVariableName()
    {
        return static::VARIABLE_NAME;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return Hash::driver('pm')->make(Session::getId());
    }
}