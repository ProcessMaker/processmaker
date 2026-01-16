<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Jobs\ErrorHandling;

class ErrorHandlingTest extends TestCase
{
    public function testUsesOutputErrorOverGenericMessage(): void
    {
        $result = [
            'status' => 'error',
            'message' => 'Generic failure',
            'output' => [
                'error' => 'SMART_EXTRACT_API_HOST is required but could not be resolved',
            ],
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('SMART_EXTRACT_API_HOST is required but could not be resolved');

        ErrorHandling::convertResponseToException($result);
    }

    public function testPrefersStderrAndKeepsOnlyFirstLine(): void
    {
        $result = [
            'status' => 'error',
            'message' => 'fallback message',
            'output' => [
                'stderr' => "First line of error\nstack trace line 2\nstack trace line 3",
            ],
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('First line of error');

        ErrorHandling::convertResponseToException($result);
    }

    public function testTimeoutErrorThrowsScriptTimeoutException(): void
    {
        $result = [
            'status' => 'error',
            'message' => 'Command exceeded timeout of 120 seconds',
        ];

        $this->expectException(ScriptTimeoutException::class);
        $this->expectExceptionMessage('Command exceeded timeout of 120 seconds');

        ErrorHandling::convertResponseToException($result);
    }

    public function testFallsBackToRawMessageWhenNoOutputPresent(): void
    {
        $result = [
            'status' => 'error',
            'message' => 'Plain failure',
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Plain failure');

        ErrorHandling::convertResponseToException($result);
    }
}
