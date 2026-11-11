<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class BpmnLayoutService
{
    /**
     * @return array{bpmn: string, warnings: list<string>}
     */
    public function layout(string $bpmn): array
    {
        if (!config('mcp.bpmn_layout.enabled')) {
            throw new BpmnLayoutException('BPMN auto-layout is disabled (MCP_BPMN_LAYOUT_ENABLED).');
        }

        $maxBytes = (int) config('mcp.bpmn_layout.max_input_bytes', 2_097_152);
        if (strlen($bpmn) > $maxBytes) {
            throw new BpmnLayoutException('BPMN XML exceeds the maximum size for auto-layout.');
        }

        $node = (string) config('mcp.bpmn_layout.node_binary', 'node');
        $script = (string) config('mcp.bpmn_layout.script_path');
        if ($script === '' || !is_file($script)) {
            throw new BpmnLayoutException('BPMN layout script is not installed at ' . $script);
        }

        $timeout = max(1, (int) config('mcp.bpmn_layout.timeout', 30));
        $process = new Process([$node, $script]);
        $process->setInput($bpmn);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();
        } catch (ProcessTimedOutException) {
            throw new BpmnLayoutException('BPMN auto-layout timed out after ' . $timeout . ' seconds.');
        } catch (\Throwable $exception) {
            $parsed = $this->parseStderr($process->getErrorOutput());
            if ($parsed['error'] !== null) {
                throw new BpmnLayoutException($parsed['error']);
            }

            throw new BpmnLayoutException(
                'BPMN auto-layout failed: ' . ($exception->getMessage() !== '' ? $exception->getMessage() : 'unknown error'),
            );
        }

        $output = trim($process->getOutput());
        if ($output === '') {
            $parsed = $this->parseStderr($process->getErrorOutput());
            throw new BpmnLayoutException($parsed['error'] ?? 'BPMN auto-layout produced empty output.');
        }

        $parsed = $this->parseStderr($process->getErrorOutput());

        return [
            'bpmn' => $output,
            'warnings' => $parsed['warnings'],
        ];
    }

    /**
     * @return array{warnings: list<string>, error: ?string}
     */
    private function parseStderr(string $stderr): array
    {
        $warnings = [];
        $error = null;

        foreach (preg_split('/\r\n|\r|\n/', $stderr) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                if ($error === null && Str::contains($line, 'error')) {
                    $error = $line;
                }
                continue;
            }

            $level = $decoded['level'] ?? null;
            $message = $decoded['message'] ?? null;
            if (!is_string($message) || $message === '') {
                continue;
            }

            if ($level === 'error') {
                $error = $message;
            } elseif ($level === 'warning') {
                $warnings[] = $message;
            }
        }

        return ['warnings' => $warnings, 'error' => $error];
    }
}
