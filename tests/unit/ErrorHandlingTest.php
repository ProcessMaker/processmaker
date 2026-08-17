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

    public function testUsesTopLevelErrorMessageBeforeStructuredMicroserviceError(): void
    {
        $result = [
            'status' => 'error',
            'error_message' => 'Failed to apply Smart Extract model: Unsupported image type for PDF conversion: image/gif',
            'error' => [
                'code' => 'RuntimeException',
                'file' => '/opt/executor/script.php',
                'line' => 42,
                'trace' => 'sensitive stack trace',
            ],
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage(
            'Failed to apply Smart Extract model: Unsupported image type for PDF conversion: image/gif'
        );

        ErrorHandling::convertResponseToException($result);
    }

    public function testExtractsMessageFromStructuredMicroserviceError(): void
    {
        $result = [
            'status' => 'error',
            'error' => [
                'detail' => 'Unsupported image type for PDF conversion: image/gif',
                'trace' => 'stack trace must not be used',
            ],
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Unsupported image type for PDF conversion: image/gif');

        ErrorHandling::convertResponseToException($result);
    }

    public function testSanitizesMicroserviceErrorMessage(): void
    {
        $result = [
            'status' => 'error',
            'error_message' => "PHP Fatal error: Uncaught Exception: Failed to apply Smart Extract model: Bearer secret-token in /opt/executor/script.php:42\nStack trace:\n#0 {main}",
        ];

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage(
            'Failed to apply Smart Extract model: Bearer [REDACTED]'
        );

        ErrorHandling::convertResponseToException($result);
    }
}
