<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Managers\DockerManager;

class Docker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return DockerManager::class;
    }
}