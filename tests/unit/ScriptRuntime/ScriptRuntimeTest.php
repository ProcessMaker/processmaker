<?php

namespace Tests\Unit\ScriptRuntime;

use ProcessMaker\Contracts\ScriptModuleInterface;
use ProcessMaker\Facades\ScriptRuntime;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRuntime\ScriptExecutionContext;
use ProcessMaker\ScriptRuntime\ScriptModuleRegistry;
use Tests\TestCase;

class ScriptRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withPersonalAccessClient();
        // Fresh registry per test (clear Facade cache so bindings apply)
        ScriptRuntime::clearResolvedInstances();
        $registry = new ScriptModuleRegistry();
        $runtime = new \ProcessMaker\ScriptRuntime\ScriptRuntime($registry);
        $this->app->instance(ScriptModuleRegistry::class, $registry);
        $this->app->instance(\ProcessMaker\ScriptRuntime\ScriptRuntime::class, $runtime);
        $this->app->instance('script.runtime', $runtime);
    }

    public function testRegisterModuleAndCatalog(): void
    {
        ScriptRuntime::registerModule(FakeEchoModule::class);

        $this->assertTrue(ScriptRuntime::registry()->has('echo'));
        $catalog = ScriptRuntime::catalog();
        $this->assertSame('echo', $catalog[0]['key']);
        $this->assertArrayHasKey('ping', $catalog[0]['methods']);
    }

    public function testRunScriptWithModuleVariable(): void
    {
        ScriptRuntime::registerModule(FakeEchoModule::class);
        $user = User::factory()->create();

        $output = ScriptRuntime::run(
            '<?php return ["pong" => $echo->ping($data["ping"]), "keys" => array_keys($modules)];',
            new ScriptExecutionContext(
                data: ['ping' => 'hello'],
                config: [],
                user: $user,
                source: 'preview',
            )
        );

        $this->assertSame('hello', $output['pong']);
        $this->assertContains('echo', $output['keys']);
    }

    public function testRejectsReservedModuleKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ScriptRuntime::registerModule(FakeReservedModule::class);
    }

    public function testNormalizeOutput(): void
    {
        $this->assertSame([], ScriptRuntime::normalizeOutput(null));
        $this->assertSame(['response' => 1], ScriptRuntime::normalizeOutput(1));
        $this->assertSame(['a' => 1], ScriptRuntime::normalizeOutput(['a' => 1]));
    }

    public function testExposesApiHostAndTokenViaGetenv(): void
    {
        config(['app.docker_host_url' => 'https://pm.example.test']);
        $user = User::factory()->create();

        $output = ScriptRuntime::run(
            '<?php return [
                "host" => getenv("API_HOST"),
                "has_token" => is_string(getenv("API_TOKEN")) && getenv("API_TOKEN") !== "",
                "app_url" => getenv("APP_URL"),
            ];',
            new ScriptExecutionContext(
                data: [],
                config: [],
                user: $user,
                source: 'preview',
            )
        );

        $this->assertSame('https://pm.example.test/api/1.0', $output['host']);
        $this->assertTrue($output['has_token']);
        $this->assertSame('https://pm.example.test', $output['app_url']);
        // Token must not leak into the parent process after the run
        $this->assertFalse(getenv('API_TOKEN'));
    }
}

class FakeEchoModule implements ScriptModuleInterface
{
    public static function key(): string
    {
        return 'echo';
    }

    public static function label(): string
    {
        return 'Echo';
    }

    public static function catalog(): array
    {
        return ['ping' => ['params' => ['value' => 'string']]];
    }

    public function boot(ScriptExecutionContext $context): void
    {
    }

    public function ping(string $value): string
    {
        return $value;
    }
}

class FakeReservedModule implements ScriptModuleInterface
{
    public static function key(): string
    {
        return 'data';
    }

    public static function label(): string
    {
        return 'Reserved';
    }

    public static function catalog(): array
    {
        return [];
    }

    public function boot(ScriptExecutionContext $context): void
    {
    }
}
