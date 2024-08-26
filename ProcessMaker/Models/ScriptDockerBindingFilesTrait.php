<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Facades\Docker;

/**
 * Execute a docker container binding files to interchange information.
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
        $response = $this->runContainer(
            $options['image'],
            $options['command'],
            $options['parameters'],
            $bindings,
            $options['timeout']
        );

        return $response;
    }

    /**
     * Execute a docker container.
     *
     * @param string $image
     * @param string $command
     * @param string $parameters
     * @param string $bindings
     * @param int $timeout
     *
     * @return array
     * @throws ScriptTimeoutException
     */
    private function runContainer($image, $command, $parameters, $bindings, $timeout)
    {
        $cmd = Docker::command($timeout) . sprintf(
            ' run --rm %s %s %s %s 2>&1',
            $parameters,
            $bindings,
            $image,
            $command
        );

        Log::debug('Running Docker container', [
            'timeout' => $timeout,
            'cmd' => $cmd,
        ]);

        $line = exec($cmd, $output, $returnCode);
        if ($returnCode) {
            if ($returnCode == 137 || $returnCode == 9) {
                Log::error('Script timed out');
                $this->removeTemporalFiles();
                throw new ScriptTimeoutException(
                    __('Script took too long to complete. Consider increasing the timeout.')
                  . "\n"
                  . __('Timeout: :timeout seconds', ['timeout' => $timeout])
                  . "\n"
                  . implode("\n", $output)
                );
            }
            Log::error('Script threw return code ' . $returnCode . ' Message: ' . implode("\n", $output));

            $message = implode("\n", $output);
            $message .= "\n\nProcessMaker Stack:\n";
            $message .= (new \Exception)->getTraceAsString();
            $this->removeTemporalFiles();
            throw new ScriptException($message);
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
        return sprintf(' -v %s:%s', $hostFile, $guestFile);
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
        $hostFile = tempnam(config('app.processmaker_scripts_home'), 'put');
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
        $hostFile = tempnam(config('app.processmaker_scripts_home'), 'get');
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
     */
    private function removeTemporalFiles()
    {
        foreach ($this->temporalFiles as $filename) {
            unlink($filename);
        }
    }
}
