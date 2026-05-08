<?php

namespace ProcessMaker\Models;

use ProcessMaker\Exception\ScriptException;
use Tests\TestCase;

class ScriptDockerNayraTraitFunctionState
{
    public static bool $enabled = false;

    public static array $headers = [];

    public static array $requestedHeaders = [];

    public static ?string $curlUrl = null;

    public static array $curlOptions = [];

    public static string $curlResult = '{"status":"ok"}';

    public static int $curlHttpStatus = 200;

    public static bool $curlClosed = false;

    public static function reset(): void
    {
        self::$enabled = false;
        self::$headers = [];
        self::$requestedHeaders = [];
        self::$curlUrl = null;
        self::$curlOptions = [];
        self::$curlResult = '{"status":"ok"}';
        self::$curlHttpStatus = 200;
        self::$curlClosed = false;
    }
}

if (!function_exists(__NAMESPACE__ . '\get_headers')) {
    function get_headers($url, $associative = false, $context = null)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \get_headers($url, $associative, $context);
        }

        ScriptDockerNayraTraitFunctionState::$requestedHeaders[] = $url;

        return ScriptDockerNayraTraitFunctionState::$headers[$url] ?? false;
    }
}

if (!function_exists(__NAMESPACE__ . '\curl_init')) {
    function curl_init($url = null)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \curl_init($url);
        }

        ScriptDockerNayraTraitFunctionState::$curlUrl = $url;

        return (object) ['url' => $url];
    }
}

if (!function_exists(__NAMESPACE__ . '\curl_setopt')) {
    function curl_setopt($handle, $option, $value)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \curl_setopt($handle, $option, $value);
        }

        ScriptDockerNayraTraitFunctionState::$curlOptions[$option] = $value;

        return true;
    }
}

if (!function_exists(__NAMESPACE__ . '\curl_exec')) {
    function curl_exec($handle)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \curl_exec($handle);
        }

        return ScriptDockerNayraTraitFunctionState::$curlResult;
    }
}

if (!function_exists(__NAMESPACE__ . '\curl_getinfo')) {
    function curl_getinfo($handle, $option = null)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \curl_getinfo($handle, $option);
        }

        if ($option === CURLINFO_HTTP_CODE) {
            return ScriptDockerNayraTraitFunctionState::$curlHttpStatus;
        }

        return ['http_code' => ScriptDockerNayraTraitFunctionState::$curlHttpStatus];
    }
}

if (!function_exists(__NAMESPACE__ . '\curl_close')) {
    function curl_close($handle)
    {
        if (!ScriptDockerNayraTraitFunctionState::$enabled) {
            return \curl_close($handle);
        }

        ScriptDockerNayraTraitFunctionState::$curlClosed = true;
    }
}

class ScriptDockerNayraTraitTestHarness
{
    use ScriptDockerNayraTrait {
        getNayraInstanceUrl as public exposedGetNayraInstanceUrl;
        resolveNayraBaseUrl as public exposedResolveNayraBaseUrl;
    }

    public int $bringUpNayraCalls = 0;

    public array $bringUpNayraRestartValues = [];

    public function bringUpNayra($restart = false)
    {
        $this->bringUpNayraCalls++;
        $this->bringUpNayraRestartValues[] = $restart;

        if (!$restart) {
            self::setNayraAddresses(['172.18.0.9']);
        }
    }
}

class ScriptDockerNayraTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ScriptDockerNayraTraitFunctionState::reset();
        ScriptDockerNayraTraitTestHarness::clearNayraAddresses();

        config([
            'app.nayra_rest_api_host' => '',
            'app.nayra_port' => 8081,
        ]);
    }

    protected function tearDown(): void
    {
        ScriptDockerNayraTraitFunctionState::reset();
        ScriptDockerNayraTraitTestHarness::clearNayraAddresses();

        parent::tearDown();
    }

    public function testHandleNayraDockerUsesConfiguredRestApiHostWithoutStartingDocker()
    {
        config(['app.nayra_rest_api_host' => 'http://127.0.0.1:8081']);
        ScriptDockerNayraTraitFunctionState::$enabled = true;
        ScriptDockerNayraTraitFunctionState::$headers = [
            'http://127.0.0.1:8081' => ['HTTP/1.1 200 OK'],
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();
        $result = $runner->handleNayraDocker('<?php return [];', ['foo' => 'bar'], [], 30, ['API_TOKEN=test-token']);

        $this->assertSame('{"status":"ok"}', $result);
        $this->assertSame('http://127.0.0.1:8081/run_script', ScriptDockerNayraTraitFunctionState::$curlUrl);
        $this->assertSame(['http://127.0.0.1:8081'], ScriptDockerNayraTraitFunctionState::$requestedHeaders);
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testConfiguredRestApiHostTakesPriorityOverCachedDockerAddress()
    {
        config(['app.nayra_rest_api_host' => 'http://127.0.0.1:8081']);
        ScriptDockerNayraTraitTestHarness::setNayraAddresses(['172.18.0.5']);
        ScriptDockerNayraTraitFunctionState::$enabled = true;
        ScriptDockerNayraTraitFunctionState::$headers = [
            'http://127.0.0.1:8081' => ['HTTP/1.1 200 OK'],
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();
        $runner->handleNayraDocker('<?php return [];', [], [], 30, []);

        $this->assertSame('http://127.0.0.1:8081/run_script', ScriptDockerNayraTraitFunctionState::$curlUrl);
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testNayraBaseUrlFallsBackToCachedDockerAddressWithoutRestApiHost()
    {
        ScriptDockerNayraTraitTestHarness::setNayraAddresses(['172.18.0.5']);

        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame('http://172.18.0.5:8081', $runner->exposedResolveNayraBaseUrl());
        $this->assertSame(0, $runner->bringUpNayraCalls);
    }

    public function testNayraBaseUrlStartsDockerBeforeResolvingUrlWhenNoRestApiHostOrCachedAddressExists()
    {
        $runner = new ScriptDockerNayraTraitTestHarness();

        $this->assertSame('http://172.18.0.9:8081', $runner->exposedResolveNayraBaseUrl());
        $this->assertSame(1, $runner->bringUpNayraCalls);
        $this->assertSame([false], $runner->bringUpNayraRestartValues);
    }

    public function testConfiguredRestApiHostConnectionFailureDoesNotStartTenantDockerContainer()
    {
        config(['app.nayra_rest_api_host' => 'http://127.0.0.1:8081']);
        ScriptDockerNayraTraitFunctionState::$enabled = true;
        ScriptDockerNayraTraitFunctionState::$headers = [
            'http://127.0.0.1:8081' => false,
        ];

        $runner = new ScriptDockerNayraTraitTestHarness();

        try {
            $runner->handleNayraDocker('<?php return [];', [], [], 30, []);
            $this->fail('Expected a ScriptException when the configured Nayra REST API host is unavailable.');
        } catch (ScriptException $exception) {
            $this->assertStringContainsString(
                'Could not connect to the configured Nayra REST API host: http://127.0.0.1:8081',
                $exception->getMessage()
            );
        }

        $this->assertSame(0, $runner->bringUpNayraCalls);
        $this->assertNull(ScriptDockerNayraTraitFunctionState::$curlUrl);
    }
}
