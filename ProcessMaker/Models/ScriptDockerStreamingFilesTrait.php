<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Facades\Docker;
use RuntimeException;

/**
 * Execute a docker container streaming files via stdin/stdout tar archives.
 */
trait ScriptDockerStreamingFilesTrait
{
    /**
     * Cache of images that support streaming mode.
     *
     * @var array<string, bool>
     */
    private static array $streamingImageCache = [];

    /**
     * Run a command in a docker container using tar streaming.
     *
     * @param array $options
     *
     * @return array
     * @throws RuntimeException
     */
    protected function executeStreaming(array $options)
    {
        if (!$this->imageSupportsStreaming($options['image'])) {
            Log::debug('Docker image does not support streaming, falling back to copying', [
                'image' => $options['image'],
            ]);

            return $this->executeCopying($options);
        }

        $inputTar = $this->buildUstarTar($options['inputs']);
        $outputPaths = array_values($options['outputs']);
        $outputArgs = implode(' ', array_map(
            static fn ($path) => escapeshellarg(ltrim($path, '/')),
            $outputPaths
        ));

        $cmd = Docker::command(0) . sprintf(
            ' run -i --rm --network=host %s %s /opt/executor/run-stream.sh %s',
            $options['parameters'],
            escapeshellarg($options['image']),
            $outputArgs
        );

        ['stdout' => $stdout, 'stderr' => $stderr, 'returnCode' => $returnCode] = $this->runStreamingContainer(
            $cmd,
            $inputTar,
            (int) $options['timeout']
        );
        $output = $this->filterStreamingStderr($stderr);
        $line = $output[0] ?? '';

        if ($returnCode) {
            if ($returnCode == 137 || $returnCode == 9) {
                Log::error('Script timed out');
                throw new ScriptTimeoutException(implode("\n", $output));
            }
            Log::error('Script threw return code ' . $returnCode);
            $message = implode("\n", $output);
            $message .= "\n\nProcessMaker Stack:\n";
            $message .= (new \Exception)->getTraceAsString();
            throw new ScriptException($message);
        }

        $outputFiles = $this->parseUstarTar($stdout);
        $outputs = [];
        foreach ($options['outputs'] as $name => $path) {
            $normalized = ltrim($path, '/');
            $outputs[$name] = $outputFiles[$normalized] ?? $outputFiles[$path] ?? '';
        }

        return compact('line', 'output', 'returnCode', 'outputs');
    }

    /**
     * Run a streaming docker process with stdin tar input and PHP-enforced timeout.
     *
     * Shell timeout (PROCESSMAKER_SCRIPTS_TIMEOUT) is not used here because it is
     * unavailable on some hosts (e.g. macOS) and breaks proc_open stdin piping.
     *
     * @param string $cmd
     * @param string $inputTar
     * @param int $timeout
     *
     * @return array{stdout: string, stderr: string, returnCode: int}
     */
    private function runStreamingContainer(string $cmd, string $inputTar, int $timeout): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start streaming docker container');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        fwrite($pipes[0], $inputTar);
        fclose($pipes[0]);

        $stdout = '';
        $stderr = '';
        $deadline = $timeout > 0 ? microtime(true) + $timeout : null;

        while (true) {
            if ($deadline !== null && microtime(true) >= $deadline) {
                $this->terminateStreamingProcess($process, [$pipes[1], $pipes[2]]);
                Log::error('Script timed out');
                throw new ScriptTimeoutException(
                    __('Script took too long to complete. Consider increasing the timeout.')
                    . "\n"
                    . __('Timeout: :timeout seconds', ['timeout' => $timeout])
                );
            }

            $status = proc_get_status($process);
            $read = [];

            if (!feof($pipes[1])) {
                $read[] = $pipes[1];
            }

            if (!feof($pipes[2])) {
                $read[] = $pipes[2];
            }

            if (!$status['running'] && $read === []) {
                break;
            }

            if ($read !== []) {
                $seconds = 1;
                $microseconds = 0;

                if ($deadline !== null) {
                    $remaining = $deadline - microtime(true);

                    if ($remaining <= 0) {
                        continue;
                    }

                    $seconds = (int) floor(min($remaining, 1));
                    $microseconds = (int) round(min($remaining - $seconds, 1) * 1000000);
                }

                $write = null;
                $except = null;
                $ready = @stream_select($read, $write, $except, $seconds, $microseconds);

                if ($ready === false) {
                    break;
                }

                if ($ready > 0) {
                    foreach ($read as $stream) {
                        $chunk = fread($stream, 8192);

                        if ($chunk === false || $chunk === '') {
                            continue;
                        }

                        if ($stream === $pipes[1]) {
                            $stdout .= $chunk;
                        } else {
                            $stderr .= $chunk;
                        }
                    }
                }
            } elseif ($status['running']) {
                usleep(100000);
            } else {
                break;
            }
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        return compact('stdout', 'stderr', 'returnCode');
    }

    /**
     * Forcefully stop a streaming docker process.
     *
     * @param resource $process
     * @param array<int, resource> $pipes
     *
     * @return void
     */
    private function terminateStreamingProcess($process, array $pipes = []): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        @proc_terminate($process, 9);
        usleep(100000);

        $status = proc_get_status($process);

        if ($status['running']) {
            @proc_terminate($process, 9);
        }

        proc_close($process);
    }

    /**
     * Remove benign tar stderr lines that are not script failures.
     *
     * @param string $stderr
     *
     * @return array<int, string>
     */
    private function filterStreamingStderr(string $stderr): array
    {
        if ($stderr === '') {
            return [];
        }

        $lines = explode("\n", rtrim($stderr, "\n"));

        return array_values(array_filter($lines, static function (string $line): bool {
            return !str_starts_with($line, 'tar: Removing leading ');
        }));
    }

    /**
     * Check if the docker image supports streaming via run-stream.sh.
     *
     * @param string $image
     *
     * @return bool
     */
    protected function imageSupportsStreaming(string $image): bool
    {
        if (array_key_exists($image, self::$streamingImageCache)) {
            return self::$streamingImageCache[$image];
        }

        $cmd = Docker::command() . sprintf(
            ' run --rm --entrypoint test %s -f /opt/executor/run-stream.sh',
            escapeshellarg($image)
        );
        exec($cmd, $output, $returnCode);

        return self::$streamingImageCache[$image] = ($returnCode === 0);
    }

    /**
     * Build a ustar tar archive from input files.
     *
     * @param array<string, string> $inputs
     *
     * @return string
     */
    protected function buildUstarTar(array $inputs): string
    {
        $tar = '';
        foreach ($inputs as $path => $content) {
            $tar .= $this->buildUstarTarEntry($path, $content);
        }

        $tar .= str_repeat("\0", 1024);

        $this->validateUstarTar($tar, $inputs);

        return $tar;
    }

    /**
     * Validate that a ustar tar archive contains the expected files.
     *
     * @param string $tar
     * @param array<string, string> $expected
     *
     * @return void
     */
    protected function validateUstarTar(string $tar, array $expected): void
    {
        $parsed = $this->parseUstarTar($tar);

        foreach ($expected as $path => $content) {
            $normalized = ltrim($path, '/');
            if (!array_key_exists($normalized, $parsed)) {
                throw new RuntimeException("Tar validation failed: missing path {$path}");
            }
            if ($parsed[$normalized] !== $content) {
                throw new RuntimeException("Tar validation failed: content mismatch for {$path}");
            }
        }
    }

    /**
     * Parse a ustar tar archive into path => content pairs.
     *
     * @param string $tar
     *
     * @return array<string, string>
     */
    protected function parseUstarTar(string $tar): array
    {
        $files = [];
        $offset = 0;
        $length = strlen($tar);

        while ($offset + 512 <= $length) {
            $header = substr($tar, $offset, 512);

            if ($header === str_repeat("\0", 512)) {
                break;
            }

            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            $path = $prefix !== '' ? $prefix . '/' . $name : $name;

            $size = octdec(trim(substr($header, 124, 12)));
            $offset += 512;

            if ($size > 0) {
                $files[$path] = substr($tar, $offset, $size);
                $offset += (int) (ceil($size / 512) * 512);
            } else {
                $files[$path] = '';
            }
        }

        return $files;
    }

    /**
     * Build a single ustar tar entry.
     *
     * @param string $path
     * @param string $content
     *
     * @return string
     */
    private function buildUstarTarEntry(string $path, string $content): string
    {
        $path = ltrim($path, '/');
        $size = strlen($content);

        $header = str_repeat("\0", 512);

        if (strlen($path) > 100) {
            $splitAt = strrpos(substr($path, 0, 155), '/');
            if ($splitAt === false) {
                throw new RuntimeException("Tar path too long: {$path}");
            }
            $prefix = substr($path, 0, $splitAt);
            $name = substr($path, $splitAt + 1);
            $this->writeTarField($header, 0, $name, 100);
            $this->writeTarField($header, 345, $prefix, 155);
        } else {
            $this->writeTarField($header, 0, $path, 100);
        }

        $this->writeTarField($header, 100, sprintf('%07o', 0644), 8); //Permissions
        $this->writeTarField($header, 108, sprintf('%07o', 0), 8); //User - UID
        $this->writeTarField($header, 116, sprintf('%07o', 0), 8); //Group - GID
        $this->writeTarField($header, 124, sprintf('%011o', $size), 12); //Size
        $this->writeTarField($header, 136, sprintf('%011o', time()), 12); //Modification Time
        $header[156] = '0';
        $this->writeTarField($header, 257, 'ustar', 6);
        $this->writeTarField($header, 263, '00', 2);

        $this->writeTarField($header, 148, sprintf('%07o', $this->calculateTarChecksum($header)), 8);

        $entry = $header . $content;
        $padding = (512 - ($size % 512)) % 512; //Padding to make the entry a multiple of 512 bytes

        return $entry . str_repeat("\0", $padding);
    }

    /**
     * Write a field into a tar header block.
     *
     * @param string $header
     * @param int $offset
     * @param string $value
     * @param int $length
     *
     * @return void
     */
    private function writeTarField(string &$header, int $offset, string $value, int $length): void
    {
        $header = substr_replace($header, substr(str_pad($value, $length, "\0"), 0, $length), $offset, $length);
    }

    /**
     * Calculate the ustar header checksum.
     *
     * @param string $header
     *
     * @return int
     */
    private function calculateTarChecksum(string $header): int
    {
        $header = substr_replace($header, str_repeat(' ', 8), 148, 8);
        $checksum = 0;

        for ($i = 0; $i < 512; $i++) {
            $checksum += ord($header[$i]);
        }

        return $checksum;
    }
}
