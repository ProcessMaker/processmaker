<?php

namespace ProcessMaker\Models;

use ProcessMaker\Exception\ScriptException;
use Tests\TestCase;

class ScriptDockerNayraTraitFunctionState
{
    public const SUCCESS_RESPONSE = '{"status":"ok"}';

    public const LOCAL_NAYRA_HOST = 'http://127.0.0.1:8081';

    public const OK_HEADER = 'HTTP/1.1 200 OK';

    public const SCRIPT_CODE = '<?php return [];';

    public static array $headers = [];

    public static array $requestedHeaders = [];

    public static ?string $curlUrl = null;

    public static array $curlOptions = [];

    public static array $curlHandles = [];

    public static string $curlResult;

    public static int $curlHttpStatus = 200;

    public static bool $curlClosed = false;

    public static function reset(): void
    {
        self::$headers = [];
        self::$requestedHeaders = [];
        self::$curlUrl = null;
        self::$curlOptions = [];
        self::$curlHandles = [];
        self::$curlResult = self::SUCCESS_RESPONSE;
        self::$curlHttpStatus = 200;
        self::$curlClosed = false;
    }
}

class ScriptExecutorResolutionNotExercisedException extends \Exception
{
}

class ScriptDockerNayraTraitTestHarness
{
    use ScriptDockerNayraTrait {
        getNayraInstanceUrl as public exposedGetNayraInstanceUrl;
        resolveNayraBaseUrl as public exposedResolveNayraBaseUrl;
        getNayraPort as public exposedGetNayraPort;
        getNayraContainerName as public exposedGetNayraContainerName;
    }

    public int $bringUpNayraCalls = 0;

    public array $bringUpNayraRestartValues = [];

    public function bringUpNayra($restart = false)
    {
        $this->bringUpNayraCalls++;
        $this->bringUpNayraRestartValues[] = $restart;

        self::setNayraEndpoint(ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST);
    }

    protected function getScriptExecutor(): ScriptExecutor
    {
        throw new ScriptExecutorResolutionNotExercisedException(
            'The test harness does not exercise script executor resolution.'
        );
    }

    protected function getHeaders(string $url): array|false
    {
        ScriptDockerNayraTraitFunctionState::$requestedHeaders[] = $url;

        return ScriptDockerNayraTraitFunctionState::$headers[$url] ?? false;
    }

    protected function curlInit(string $url): object
    {
        ScriptDockerNayraTraitFunctionState::$curlUrl = $url;

        return (object) ['url' => $url];
    }

    protected function curlSetOpt(mixed $handle, int $option, mixed $value): bool
    {
        ScriptDockerNayraTraitFunctionState::$curlHandles[__FUNCTION__][] = $handle;
        ScriptDockerNayraTraitFunctionState::$curlOptions[$option] = $value;

        return true;
    }

    protected function curlExec(mixed $handle): string|bool
    {
        ScriptDockerNayraTraitFunctionState::$curlHandles[__FUNCTION__][] = $handle;

        return ScriptDockerNayraTraitFunctionState::$curlResult;
    }

    protected function curlGetInfo(mixed $handle, int $option): mixed
    {
        ScriptDockerNayraTraitFunctionState::$curlHandles[__FUNCTION__][] = $handle;

        if ($option === CURLINFO_HTTP_CODE) {
            return ScriptDockerNayraTraitFunctionState::$curlHttpStatus;
        }

        return ['http_code' => ScriptDockerNayraTraitFunctionState::$curlHttpStatus];
    }

    protected function curlClose(mixed $handle): void
    {
        ScriptDockerNayraTraitFunctionState::$curlHandles[__FUNCTION__][] = $handle;
        ScriptDockerNayraTraitFunctionState::$curlClosed = true;
    }
}

class ScriptDockerNayraTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ScriptDockerNayraTraitFunctionState::reset();
        ScriptDockerNayraTraitTestHarness::clearNayraAddresses();
        ScriptDockerNayraTraitTestHarness::clearNayraEndpoint();
        app()->forgetInstance('currentTenant');

        config([
            'app.instance' => 'processmaker',
            'app.multitenancy' => false,
            'app.nayra_rest_api_host' => '',
            'app.nayra_port' => 8081,
            'app.nayra_docker_network' => '',
            'app.processmaker_scripts_docker_host' => '',
        ]);
    }

    protected function tearDown(): void
    {
        ScriptDockerNayraTraitFunctionState::reset();
        ScriptDockerNayraTraitTestHarness::clearNayraAddresses();
        ScriptDockerNayraTraitTestHarness::clearNayraEndpoint();
        app()->forgetInstance('currentTenant');

        parent::tearDown();
    }

    public function testHandleNayraDockerUsesConfiguredRestApiHostWithoutStartingDocker()
    {
        config(['app.nayra_rest_api_host' => ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST]);
        ScriptDockerNayraTraitFunctionState::$headers = [
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST => [
                ScriptDockerNayraTraitFunctionState::OK_HEADER,
            ],
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();
        $result = $runner->handleNayraDocker(
            ScriptDockerNayraTraitFunctionState::SCRIPT_CODE,
            ['foo' => 'bar'],
            [],
            30,
            ['API_TOKEN=test-token']
        );

        $this->assertSame(ScriptDockerNayraTraitFunctionState::SUCCESS_RESPONSE, $result);
        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST . '/run_script',
            ScriptDockerNayraTraitFunctionState::$curlUrl
        );
        $this->assertSame(
            [ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST],
            ScriptDockerNayraTraitFunctionState::$requestedHeaders
        );
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testConfiguredRestApiHostTakesPriorityOverCachedDockerAddress()
    {
        config(['app.nayra_rest_api_host' => ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST]);
        ScriptDockerNayraTraitTestHarness::setNayraEndpoint('http://127.0.0.1:9090');
        ScriptDockerNayraTraitFunctionState::$headers = [
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST => [
                ScriptDockerNayraTraitFunctionState::OK_HEADER,
            ],
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();
        $runner->handleNayraDocker(ScriptDockerNayraTraitFunctionState::SCRIPT_CODE, [], [], 30, []);

        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST . '/run_script',
            ScriptDockerNayraTraitFunctionState::$curlUrl
        );
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testNayraBaseUrlFallsBackToReachableCachedEndpointWithoutRestApiHost()
    {
        ScriptDockerNayraTraitTestHarness::setNayraEndpoint(ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST);
        ScriptDockerNayraTraitFunctionState::$headers = [
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST => [
                ScriptDockerNayraTraitFunctionState::OK_HEADER,
            ],
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST,
            $runner->exposedResolveNayraBaseUrl()
        );
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testNayraBaseUrlStartsDockerBeforeResolvingUrlWhenNoRestApiHostOrCachedAddressExists()
    {
        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST,
            $runner->exposedResolveNayraBaseUrl()
        );
        $this->assertSame(1, $runner->bringUpNayraCalls);
        $this->assertSame([false], $runner->bringUpNayraRestartValues);
    }

    public function testEmptyNayraPortFallsBackToDefaultPort()
    {
        config(['app.nayra_port' => '']);

        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame(8080, $runner->exposedGetNayraPort());
    }

    public function testRemoteDockerHostIsUsedForFallbackNayraEndpoint()
    {
        config([
            'app.nayra_port' => '',
            'app.processmaker_scripts_docker_host' => 'tcp://qa-remotedocker:2375',
        ]);

        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame('http://qa-remotedocker:8080', $runner->exposedGetNayraInstanceUrl());
    }

    public function testMultitenantNayraUsesStableContainerNameAcrossTenants()
    {
        config([
            'app.instance' => 'processmaker_1',
            'app.multitenancy' => true,
        ]);
        app()->instance('currentTenant', (object) ['id' => 1]);

        $tenantOneRunner = new ScriptDockerNayraTraitTestHarness();

        config(['app.instance' => 'processmaker_3']);
        app()->instance('currentTenant', (object) ['id' => 3]);

        $tenantThreeRunner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame('processmaker', $tenantOneRunner->exposedGetNayraContainerName());
        $this->assertSame('processmaker', $tenantThreeRunner->exposedGetNayraContainerName());
    }

    public function testStaleCachedEndpointIsClearedBeforeRebuildingNayraEndpoint()
    {
        ScriptDockerNayraTraitTestHarness::setNayraEndpoint('http://stale-nayra:8080');
        ScriptDockerNayraTraitFunctionState::$headers = [
            'http://stale-nayra:8080' => false,
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST,
            $runner->exposedResolveNayraBaseUrl()
        );
        $this->assertSame(1, $runner->bringUpNayraCalls);
        $this->assertSame(
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST,
            ScriptDockerNayraTraitTestHarness::getNayraEndpoint()
        );
    }

    public function testConfiguredRestApiHostConnectionFailureDoesNotStartTenantDockerContainer()
    {
        config(['app.nayra_rest_api_host' => ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST]);
        ScriptDockerNayraTraitFunctionState::$headers = [
            ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST => false,
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();

        try {
            $runner->handleNayraDocker(ScriptDockerNayraTraitFunctionState::SCRIPT_CODE, [], [], 30, []);
            $this->fail('Expected a ScriptException when the configured Nayra REST API host is unavailable.');
        } catch (ScriptException $exception) {
            $this->assertStringContainsString(
                'Could not connect to the configured Nayra REST API host: '
                    . ScriptDockerNayraTraitFunctionState::LOCAL_NAYRA_HOST,
                $exception->getMessage()
            );
        }

        $this->assertSame(0, $runner->bringUpNayraCalls);
        $this->assertNull(ScriptDockerNayraTraitFunctionState::$curlUrl);
    }
}
