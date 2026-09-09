<?php

namespace Tests\Unit\ScriptRunners;

use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Models\User;
use ProcessMaker\ScriptRunners\InProcessPhpRunner;
use Tests\TestCase;

class InProcessPhpRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withPersonalAccessClient();
    }

    public function testRunsPhpScriptAndReturnsArray(): void
    {
        $runner = new InProcessPhpRunner();
        $user = User::factory()->create();

        $output = $runner->run(
            '<?php return ["pong" => $data["ping"], "cfg" => $config["mode"] ?? null];',
            ['ping' => 'hello'],
            ['mode' => 'test'],
            30,
            $user
        );

        $this->assertSame('hello', $output['pong']);
        $this->assertSame('test', $output['cfg']);
    }

    public function testThrowsOnInvalidPhp(): void
    {
        $this->expectException(ScriptException::class);

        $runner = new InProcessPhpRunner();
        $runner->run(
            '<?php this is not valid php;;;',
            [],
            [],
            30,
            User::factory()->create()
        );
    }

    public function testRespectsTimeout(): void
    {
        $this->expectException(ScriptTimeoutException::class);

        $runner = new InProcessPhpRunner();
        $runner->run(
            '<?php sleep(5); return ["ok" => true];',
            [],
            [],
            1,
            User::factory()->create()
        );
    }

    public function testExposesApiHostAndTokenViaGetenv(): void
    {
        config(['app.docker_host_url' => 'https://pm.example.test']);

        $runner = new InProcessPhpRunner();
        $output = $runner->run(
            '<?php return [
                "host" => getenv("API_HOST"),
                "has_token" => is_string(getenv("API_TOKEN")) && getenv("API_TOKEN") !== "",
                "app_url" => getenv("APP_URL"),
            ];',
            [],
            [],
            30,
            User::factory()->create()
        );

        $this->assertSame('https://pm.example.test/api/1.0', $output['host']);
        $this->assertTrue($output['has_token']);
        $this->assertSame('https://pm.example.test', $output['app_url']);
    }
}
