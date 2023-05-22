<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Managers\DockerManager;

/**
 * @see \ProcessMaker\Managers\DockerManager
 *
 * @method bool hasRemoteDocker()
 * @method string getDockerHost()
 * @method string getDockerExecutable(int)
 * @method mixed command(int)
 */
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
