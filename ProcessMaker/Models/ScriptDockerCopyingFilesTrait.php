<?php

namespace ProcessMaker\Models;

use Log;
use RuntimeException;

/**
 * Execute a docker container copying files to interchange information.
 *
 */
trait ScriptDockerCopyingFilesTrait
{

    /**
     * Run a command in a docker container.
     *
     * @param array $options
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function executeCopying(array $options)
    {
        $container = $this->createContainer($options['image'], $options['command'], $options['parameters']);
        foreach ($options['inputs'] as $path => $data) {
            $this->putInContainer($container, $path, $data);
        }
        $response = $this->startContainer($container, $options['timeout']);
        $outputs = [];
        foreach ($options['outputs'] as $name => $path) {
            $outputs[$name] = $this->getFromContainer($container, $path);
        }

        exec(config('app.bpm_scripts_docker') . ' rm ' . $container);
        $response['outputs'] = $outputs;
        return $response;
    }

    /**
     * Create a docker container.
     *
     * @param string $image
     * @param string $command
     * @param string $parameters
     *
     * @return string
     * @throws \RuntimeException
     */
    private function createContainer($image, $command, $parameters = '')
    {
        $cidfile = tempnam(config('app.bpm_scripts_home'), 'cid');
        unlink($cidfile);
        $cmd = config('app.bpm_scripts_docker') . sprintf(' create %s --cidfile %s %s %s 2>&1', $parameters, $cidfile, $image, $command);
        $line = exec($cmd, $output, $returnCode);
        if ($returnCode) {
            throw new RuntimeException('Unable to create a docker container: ' . implode("\n", $output));
        }
        if (!file_exists($cidfile)) {
            throw new RuntimeException('Unable to create a docker container: ' . implode("\n", $output));
        }
        $cid = file_get_contents($cidfile);
        unlink($cidfile);
        return $cid;
    }

    /**
     * Put a string content into a file in the container.
     *
     * @param string $container
     * @param string $path
     * @param string $content
     *
     * @throws \RuntimeException
     */
    private function putInContainer($container, $path, $content)
    {
        $source = tempnam(config('app.bpm_scripts_home'), 'put');
        file_put_contents($source, $content);
        list($returnCode, $output) = $this->execCopy($source, $container, $path);
        unlink($source);
        if ($returnCode) {
            throw new RuntimeException('Unable to send data to container: ' . implode("\n", $output));
        }
    }
    
    /**
     * Runs the docker copy command
     *
     * @param string $source
     * @param string $container
     * @param string $dest
     *
     * @throws \RuntimeException
     */
    private function execCopy($source, $container, $dest)
    {
        $cmd = config('app.bpm_scripts_docker')
            . sprintf(' cp %s %s:%s 2>&1', $source, $container, $dest);
        exec($cmd, $output, $returnCode);
        return [$returnCode, $output];
    }

    /**
     * Get the content from a file in the container.
     *
     * @param string $container
     * @param string $path
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getFromContainer($container, $path)
    {
        $target = tempnam(config('app.bpm_scripts_home'), 'get');
        $cmd = config('app.bpm_scripts_docker') . sprintf(' cp %s:%s %s 2>&1', $container, $path, $target);
        exec($cmd, $output, $returnCode);
        $content = file_get_contents($target);
        unlink($target);
        return $content;
    }

    /**
     * Start the container.
     *
     * @param string $container
     * @param integer $timeout
     *
     * @return array
     */
    private function startContainer($container, $timeout)
    {
        $cmd = '';
        
        if ($timeout > 0) {
            $cmd .= "timeout -s 9 $timeout ";
        }
        
        $cmd .= config('app.bpm_scripts_docker') . sprintf(' start %s -a 2>&1', $container);

        Log::debug('Running Docker container', [
            'timeout' => $timeout,
            'cmd' => $cmd,
        ]);

        $line = exec($cmd, $output, $returnCode);
        if ($returnCode) {
            
            if ($returnCode == 137) {
                Log::error('Script timed out');
            } else {
                Log::error('Script threw return code ' . $returnCode);
            }
            
            throw new RuntimeException(implode("\n", $output));
        }
        return compact('line', 'output', 'returnCode');
    }
}
