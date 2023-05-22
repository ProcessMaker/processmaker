<?php

namespace ProcessMaker\Managers;

/**
 * Manager class created to handle ProcessMaker's docker execution globally
 */
class DockerManager
{
    /**
     * Returns if the application has set up a remote docker server
     * @return bool
     */
    public function hasRemoteDocker()
    {
        return config('app.processmaker_scripts_docker_host') !== '';
    }

    /**
     * Returns the DOCKER_HOST env injection to be used before command if remote docker it's enabled
     * @return string
     */
    public function getDockerHost()
    {
        return self::hasRemoteDocker() ? 'DOCKER_HOST=' . config('app.processmaker_scripts_docker_host') : '';
    }

    /**
     * Returns the docker command executable to be used by the app, includes a timeout command if required
     *
     * @param int $timeout (optional) Default to 0 sec, set the timeout in seconds for the docker
     * @return string
     */
    public function getDockerExecutable($timeout = 0)
    {
        return $timeout > 0 ?
            config('app.processmaker_scripts_timeout') . " -s 9 $timeout " . config('app.processmaker_scripts_docker') :
            config('app.processmaker_scripts_docker');
    }

    /**
     * Returns the CLI command to execute docker in ProcessMaker, it includes all logic from configuration (timeout, remote docker, etc).
     *
     * @param int $timeout (optional) Default to 0 sec, set the timeout in seconds for the docker
     * @return string
     */
    public function command($timeout = 0)
    {
        if (self::hasRemoteDocker()) {
            return self::getDockerHost() . ' ' . self::getDockerExecutable($timeout);
        }

        return self::getDockerExecutable($timeout);
    }
}
