<?php

namespace ProcessMaker\Models;

use RuntimeException;

/**
 * Execute a docker container binding files to interchange information.
 *
 */
trait ScriptDockerBindingFilesTrait
{

    private $temporalFiles = [];
    private $outputFiles = [];

    /**
     * Run a command in a docker container.
     *
     * @param array $options
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function executeBinding(array $options)
    {
        $bindings = '';
        foreach ($options['inputs'] as $guestFile => $content) {
            $bindings .= $this->bindInput($guestFile, $content);
        }
        foreach ($options['outputs'] as $name => $guestFile) {
            $bindings .= $this->bindOutput($guestFile, $name);
        }
        $response = $this->runContainer($options['image'], $options['command'],
            $options['parameters'], $bindings);
        return $response;
    }

    /**
     * Execute a docker container.
     *
     * @param string $image
     * @param string $command
     * @param string $parameters
     * @param string $bindings
     *
     * @return array
     * @throws RuntimeException
     */
    private function runContainer($image, $command, $parameters, $bindings)
    {
        $cmd = config('app.bpm_scripts_docker') . sprintf(' run %s %s %s %s 2>&1',
                $parameters, $bindings, $image, $command);
        $line = exec($cmd, $output, $returnCode);
        if ($returnCode) {
            throw new RuntimeException('Unable to run a docker container: '
            . implode("\n", $output));
        }
        $outputs = $this->getOutputFilesContent();
        $this->removeTemporalFiles();
        return compact('line', 'output', 'returnCode', 'outputs');
    }

    /**
     * Get the parameter to bind two files.
     *
     * @param string $hostFile
     * @param string $guestFile
     *
     * @return string
     */
    private function bindFile($hostFile, $guestFile)
    {
        return sprintf(" -v %s:%s", $hostFile, $guestFile);
    }

    /**
     * Put a content into a gust file.
     *
     * @param string $guestFile
     * @param string $content
     *
     * @return string
     */
    private function bindInput($guestFile, $content)
    {
        $hostFile = tempnam(config('app.bpm_scripts_home'), 'put');
        $this->temporalFiles[] = $hostFile;
        file_put_contents($hostFile, $content);
        return $this->bindFile($hostFile, $guestFile);
    }

    /**
     * Put a content into a gust file.
     *
     * @param string $guestFile
     *
     * @return string
     */
    private function bindOutput($guestFile, $name)
    {
        $hostFile = tempnam(config('app.bpm_scripts_home'), 'get');
        $this->temporalFiles[] = $hostFile;
        $this->outputFiles[$name] = $hostFile;
        return $this->bindFile($hostFile, $guestFile);
    }

    /**
     * Get the contents of the binded output files.
     *
     * @return array
     */
    private function getOutputFilesContent()
    {
        $outputs = [];
        foreach ($this->outputFiles as $name => $filename) {
            $outputs[$name] = file_get_contents($filename);
        }
        return $outputs;
    }

    /**
     * Remove the temporal files.
     *
     */
    private function removeTemporalFiles()
    {
        foreach ($this->temporalFiles as $filename) {
            unlink($filename);
        }
    }
}
