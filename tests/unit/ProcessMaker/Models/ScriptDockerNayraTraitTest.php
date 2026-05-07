<?php

namespace Tests\Unit\ProcessMaker\Models;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Models\ScriptDockerNayraTrait;
use ReflectionClass;

class ScriptDockerNayraTraitTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $app->instance('config', new ConfigRepository([
            'app' => [
                'nayra_rest_api_host' => '',
                'nayra_port' => 8080,
            ],
        ]));

        $this->cachePath = sys_get_temp_dir() . '/processmaker-nayra-test-cache-' . uniqid('', true);
        Cache::swap(new CacheRepository(new FileStore(new Filesystem(), $this->cachePath)));
    }

    protected function tearDown(): void
    {
        TestNayraScriptRunner::clearNayraAddresses();
        (new Filesystem())->deleteDirectory($this->cachePath);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testConfiguredNayraRestApiHostIsUsedInsteadOfCachedDockerAddress(): void
    {
        config([
            'app.nayra_rest_api_host' => 'http://nayra-service:8080/',
            'app.nayra_port' => 8080,
        ]);
        TestNayraScriptRunner::setNayraAddresses(['172.18.0.5']);

        $this->assertSame(
            'http://nayra-service:8080',
            $this->getNayraInstanceUrl()
        );
    }

    public function testDockerAddressIsUsedWhenNayraRestApiHostIsNotConfigured(): void
    {
        config([
            'app.nayra_rest_api_host' => '',
            'app.nayra_port' => 8081,
        ]);
        TestNayraScriptRunner::setNayraAddresses(['172.18.0.5']);

        $this->assertSame(
            'http://172.18.0.5:8081',
            $this->getNayraInstanceUrl()
        );
    }

    private function getNayraInstanceUrl(): string
    {
        $runner = new TestNayraScriptRunner();
        $reflection = new ReflectionClass($runner);
        $method = $reflection->getMethod('getNayraInstanceUrl');

        return $method->invoke($runner);
    }
}

class TestNayraScriptRunner
{
    use ScriptDockerNayraTrait;
}
