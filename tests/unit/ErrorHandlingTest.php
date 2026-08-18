<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunServiceTask;
use ProcessMaker\Services\SmartExtractConfiguration;
use ReflectionClass;
use Tests\TestCase;

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

    public function testRedactsCompleteAuthorizationValues(): void
    {
        $this->assertSame(
            'Authorization=[REDACTED]',
            ErrorHandling::sanitizeScriptErrorMessage('Authorization: Basic dXNlcjpwYXNz')
        );
        $this->assertSame(
            'Authorization=[REDACTED]',
            ErrorHandling::sanitizeScriptErrorMessage('authorization=Token abc123')
        );
    }

    public function testTruncatesMessagesWithoutBreakingUtf8(): void
    {
        $message = ErrorHandling::sanitizeScriptErrorMessage(str_repeat('a', 399) . '😀');

        $this->assertTrue(mb_check_encoding($message, 'UTF-8'));
        $this->assertSame(str_repeat('a', 399) . '…', $message);
    }

    public function testRedactsMultilineDiagnosticsWithoutRemovingTheStack(): void
    {
        $diagnostic = "Authorization: Basic dXNlcjpwYXNz\nStack trace:\n#0 Bearer secret-token";
        $redacted = ErrorHandling::redactScriptErrorDetails($diagnostic);

        $this->assertSame(
            "Authorization=[REDACTED]\nStack trace:\n#0 Bearer [REDACTED]",
            $redacted
        );
    }

    public function testOnlySmartExtractDocumentSendIsShortenedBeforeRetryHandling(): void
    {
        Log::spy();
        $job = (new ReflectionClass(RunServiceTask::class))->newInstanceWithoutConstructor();
        $prepare = (new ReflectionClass(RunServiceTask::class))->getMethod('prepareExceptionForHandling');
        $exception = new ScriptException(
            "PHP Fatal error: Uncaught Exception: Failed to apply Smart Extract model: image/gif "
                . "in /opt/executor/script.php:42\nStack trace:\n#0 Authorization: Basic dXNlcjpwYXNz"
        );

        $handled = $prepare->invoke($job, SmartExtractConfiguration::SEND_DOCUMENT_SCRIPT_KEY, $exception);
        $unchanged = $prepare->invoke($job, 'another-package/script', $exception);
        $element = new class {
            public function getProperty(string $property): ?string
            {
                return null;
            }
        };
        $errorHandling = new class($element, null) extends ErrorHandling {
            public ?string $notificationMessage = null;

            public function sendExecutionErrorNotification(string $message)
            {
                $this->notificationMessage = $message;
            }
        };
        [$retryMessage] = $errorHandling->handleRetries((object) ['attemptNum' => 1], $handled);

        $this->assertInstanceOf(ScriptException::class, $handled);
        $this->assertNotSame($exception, $handled);
        $this->assertSame('Failed to apply Smart Extract model: image/gif', $handled->getMessage());
        $this->assertSame('Failed to apply Smart Extract model: image/gif', $retryMessage);
        $this->assertSame('Failed to apply Smart Extract model: image/gif', $errorHandling->notificationMessage);
        $this->assertSame($exception, $unchanged);

        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'Smart Extract document-send executor failed'
                && str_contains($context['message'], 'Stack trace:')
                && str_contains($context['message'], 'Authorization=[REDACTED]')
                && !str_contains($context['message'], 'dXNlcjpwYXNz');
        });
    }
}
