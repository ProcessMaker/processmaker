<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Jobs\ErrorHandling;
use Psr\Log\AbstractLogger;

class ScriptMicroserviceErrorContractTest extends TestCase
{
    private mixed $previousLogger;

    private AbstractLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousLogger = Log::getFacadeRoot();
        $this->logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = compact('level', 'message', 'context');
            }
        };
        Log::swap($this->logger);
    }

    protected function tearDown(): void
    {
        if ($this->previousLogger !== null) {
            Log::swap($this->previousLogger);
        } else {
            Log::clearResolvedInstance('log');
        }
        parent::tearDown();
    }

    public static function errorContracts(): array
    {
        return [
            'API 1.0.0 standard PHP' => ['1.0.0', 1],
            'API 1.2.1 standard PHP' => ['1.2.1', 1],
            'API 1.2.1 custom PHP exit status' => ['1.2.1', 255],
        ];
    }

    #[DataProvider('errorContracts')]
    public function testVersionedContractsExposeTheCauseAndKeepDiagnostics(string $version, int $exitCode): void
    {
        $response = $this->fixture($version);
        $field = $version === '1.2.1' ? 'error_message' : 'message';
        $response[$field] = 'Script execution failed with exit code: ' . $exitCode;

        $this->assertSame('variableName is required', $this->exceptionFor($response)->getMessage());
        $this->assertCount(1, $this->logger->records);
        $log = $this->logger->records[0];
        $diagnostics = $version === '1.2.1'
            ? $log['context']['diagnostics']['error']
            : $log['context']['output_diagnostics'];
        $this->assertSame('/opt/executor/script.php', $diagnostics['file']);
        $this->assertSame(88, $diagnostics['line']);
        $this->assertStringContainsString('validateConfig()', $diagnostics['trace']);
        if ($version === '1.2.1') {
            $this->assertSame(42, $log['context']['correlation']['script_id']);
            $this->assertSame('v2026.12.2', $log['context']['correlation']['executor_version']);
        }
        $this->assertSame('error', $log['level']);
        $this->assertSame('Script microservice execution failed', $log['message']);
    }

    public function testActionableTopLevelMessageKeepsPrecedence(): void
    {
        $response = $this->fixture('1.2.1');
        $response['error_message'] = 'Document conversion failed';
        $this->assertSame('Document conversion failed', $this->exceptionFor($response)->getMessage());
    }

    public function testGenericMessageIsKeptWhenOnlyDiagnosticsAreAvailable(): void
    {
        $response = $this->fixture('1.2.1');
        unset($response['error']['error']);
        $this->assertSame($response['error_message'], $this->exceptionFor($response)->getMessage());
    }

    public function testDiagnosticsAndCorrelationAreSanitizedWithoutLoggingRequestData(): void
    {
        $response = $this->fixture('1.2.1');
        $response['error']['error'] = "PHP Fatal error: Uncaught Exception: variableName is required in /opt/executor/script.php:88\nStack trace:";
        $response['error']['trace'] = [
            ['file' => '/opt/executor/bootstrap.php', 'line' => 20, 'function' => 'validateConfig',
                'args' => [['password' => 'private-argument']], 'object' => ['private' => 'payload']],
            ['file' => '/opt/executor/script.php', 'line' => 88,
                'function' => "Authorization: Basic private-auth\nBearer private-bearer\npassword=private-password"],
        ];
        $response['metadata']['user_email'] = 'private@example.invalid';
        $response['metadata']['secrets'] = ['api_token' => 'private-token'];
        $response['metadata']['script_task'] = ['instance_id' => 123, 'token_id' => 456, 'data' => 'private-data'];
        $response['output']['document'] = 'private-document';

        $message = $this->exceptionFor($response)->getMessage();
        $this->assertSame('variableName is required', $message);
        $context = $this->logger->records[0]['context'];
        $this->assertSame(123, $context['correlation']['script_task']['instance_id']);
        $this->assertSame(456, $context['correlation']['script_task']['token_id']);
        $this->assertSame('/opt/executor/bootstrap.php', $context['diagnostics']['error']['trace'][0]['file']);
        $this->assertSame('validateConfig', $context['diagnostics']['error']['trace'][0]['function']);
        $encoded = json_encode($context, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('[REDACTED]', $encoded);
        $this->assertStringNotContainsString('private-', $encoded);
        $this->assertStringNotContainsString('private@', $encoded);
        $this->assertStringNotContainsString('args', $encoded);
        $this->assertStringNotContainsString('object', $encoded);
    }

    public function testSuccessfulResponseDoesNotProduceAnErrorLog(): void
    {
        ErrorHandling::convertResponseToException(['status' => 'success', 'output' => ['value' => 1]]);
        $this->assertSame([], $this->logger->records);
    }

    public function testLegacyTimeoutRemainsATimeout(): void
    {
        $this->expectException(ScriptTimeoutException::class);
        ErrorHandling::convertResponseToException(['status' => 'error', 'message' => 'Command exceeded timeout of 30 seconds']);
    }

    public function testDiagnosticStringsAreBounded(): void
    {
        $response = $this->fixture('1.2.1');
        $response['error']['trace'] = str_repeat('x', 20000);
        $this->exceptionFor($response);
        $this->assertSame(8000, strlen($this->logger->records[0]['context']['diagnostics']['error']['trace']));
    }

    public function testJsonCredentialsInStringTracesAreRedacted(): void
    {
        $response = $this->fixture('1.2.1');
        $response['error']['trace'] = '{"client_secret":"private-secret", "callback_token":"private-callback"}';
        $this->exceptionFor($response);
        $trace = $this->logger->records[0]['context']['diagnostics']['error']['trace'];
        $this->assertSame('{"client_secret":"[REDACTED]", "callback_token":"[REDACTED]"}', $trace);
    }

    public function testCredentialsAreRedactedBeforeTruncatingDiagnostics(): void
    {
        $response = $this->fixture('1.2.1');
        $response['error']['trace'] = json_encode(['Authorization' => 'Basic private-auth', 'password' => str_repeat('private-password', 1000)]);
        $this->exceptionFor($response);
        $trace = $this->logger->records[0]['context']['diagnostics']['error']['trace'];
        $this->assertSame('{"Authorization":"[REDACTED]","password":"[REDACTED]"}', $trace);
    }

    public static function fallbackResponses(): array
    {
        return [
            'plain legacy message' => [['status' => 'error', 'message' => 'Plain failure'], 'Plain failure'],
            'missing diagnostics' => [['status' => 'error'], 'Script execution failed with unknown error'],
            'generic message only' => [['status' => 'error', 'error_message' => 'Script execution failed with exit code: 255'], 'Script execution failed with exit code: 255'],
            'structured detail' => [['status' => 'error', 'error' => ['detail' => 'Invalid document']], 'Invalid document'],
            'legacy stderr' => [['status' => 'error', 'output' => ['stderr' => "Invalid document\nStack trace:"]], 'Invalid document'],
        ];
    }

    #[DataProvider('fallbackResponses')]
    public function testFallbackCompatibility(array $response, string $message): void
    {
        $this->assertSame($message, $this->exceptionFor($response)->getMessage());
    }

    private function fixture(string $version): array
    {
        return json_decode(file_get_contents(__DIR__ . '/../Fixtures/script-microservice/' . $version . '/php-validation-error.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function exceptionFor(array $response): ScriptException
    {
        try {
            ErrorHandling::convertResponseToException($response);
        } catch (ScriptException $exception) {
            return $exception;
        }
        $this->fail('An error response must throw ScriptException');
    }
}
