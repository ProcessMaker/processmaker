<?php

namespace Tests\Feature\Docker;

use ProcessMaker\Facades\Docker;
use Tests\TestCase;

class DockerFacadeTest extends TestCase
{
    /**
     * Default timeout command
     */
    const DEFAULT_TIMEOUT_COMMAND = 'timeout';

    /**
     * Default docker command
     */
    const DEFAULT_DOCKER_COMMAND = '/usr/bin/docker';

    protected function setUp(): void
    {
        // Set empty env variables
        config(['app.processmaker_scripts_docker_host' => '']);
        config(['app.processmaker_scripts_docker' => self::DEFAULT_DOCKER_COMMAND]);
        config(['app.processmaker_scripts_timeout' => self::DEFAULT_TIMEOUT_COMMAND]);
    }

    public function testValidateDockerDefaultEnvironmentVariables()
    {
        $dockerHost = Docker::getDockerHost();
        $this->assertEquals($dockerHost, '');

        $this->assertFalse(Docker::hasRemoteDocker());

        $command = Docker::command();
        $this->assertEquals($command, self::DEFAULT_DOCKER_COMMAND);

        $commandWithTimeout = Docker::command($timeout = 30);
        $this->assertEquals($commandWithTimeout, implode(' ', [
            self::DEFAULT_TIMEOUT_COMMAND,
            '-s 9',
            $timeout,
            self::DEFAULT_DOCKER_COMMAND,
        ]));
    }

    public function testEvaluateWhenRemoteDockerHostEnabled()
    {
        $dockerHost = 'tcp://127.0.0.1:2375';
        $timeout = 12;

        // Enable remote docker host
        config(['app.processmaker_scripts_docker_host' => $dockerHost]);

        $this->assertTrue(Docker::hasRemoteDocker());

        $envInjection = Docker::getDockerHost();
        $this->assertEquals($envInjection, 'DOCKER_HOST='.$dockerHost);

        $command = Docker::command();
        $this->assertEquals($command, 'DOCKER_HOST='.$dockerHost.' '.self::DEFAULT_DOCKER_COMMAND);

        $commandWithTimeout = Docker::command($timeout);
        $this->assertEquals($commandWithTimeout,
            implode(' ', [
                'DOCKER_HOST='.$dockerHost,
                self::DEFAULT_TIMEOUT_COMMAND,
                '-s 9',
                $timeout,
                self::DEFAULT_DOCKER_COMMAND,
            ])
        );
    }

    public function testValidateCustomTimeoutEnvironmentVariable()
    {
        // Set custom timeout command
        $customTimeoutCmd = '/usr/local/bin/gtimeout';
        config(['app.processmaker_scripts_timeout' => $customTimeoutCmd]);

        // Valid Timeout
        $timeout = 30;

        $command = Docker::command($timeout);
        $this->assertEquals($command, implode(' ', [
            $customTimeoutCmd,
            '-s 9',
            $timeout,
            self::DEFAULT_DOCKER_COMMAND,
        ]));
    }

    public function testValidateCustomDockerEnvironmentVariable()
    {
        // Set custom docker command
        $customDockerCmd = '/usr/local/bin/docker';
        config(['app.processmaker_scripts_docker' => $customDockerCmd]);

        $command = Docker::command();
        $this->assertEquals($command, $customDockerCmd);
    }

    public function testTimeoutZeroDoesNotBeIncluded()
    {
        // Define No Timeout
        $timeout = 0;

        $command = Docker::command($timeout);
        $this->assertEquals($command, self::DEFAULT_DOCKER_COMMAND);
    }
}
