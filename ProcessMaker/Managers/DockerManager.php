<?php
namespace ProcessMaker\Managers;

class DockerManager
{
    public function hasRemoteDocker()
    {
        return config('app.processmaker_scripts_docker_host') !== '';
    }

    public function getDockerHost()
    {
        return self::hasRemoteDocker() ? 'DOCKER_HOST='.config('app.processmaker_scripts_docker_host') : '';
    }

    public function getDockerExecutable($timeout = 0)
    {
        return $timeout > 0 ? "timeout -s 9 $timeout ".config('app.processmaker_scripts_docker') : config('app.processmaker_scripts_docker');
    }

    public function command($timeout = 0){
        if (self::hasRemoteDocker()){
            return self::getDockerHost(). ' '. self::getDockerExecutable($timeout);
        }
        return self::getDockerExecutable($timeout);
    }
}