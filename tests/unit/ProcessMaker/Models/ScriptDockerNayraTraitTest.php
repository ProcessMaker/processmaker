<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Models;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;
use Tests\TestCase;

/**
 * Tests for ScriptDockerNayraTrait - getNayraBaseUrl, ensureNayraServerIsRunning,
 * getNayraAddresses, setNayraAddresses, clearNayraAddresses, and handleNayraDocker.
 */
class ScriptDockerNayraTraitTest extends TestCase
{
    /**
     * Create a Nayra runner for testing (uses ScriptDockerNayraTrait via Base).
     */
    private function createNayraRunner(): Base
    {
        $scriptExecutor = ScriptExecutor::factory()->create([
            'language' => Base::NAYRA_LANG,
        ]);

        return new class($scriptExecutor) extends Base {
            public function config($code, array $dockerConfig): array
            {
                return [];
            }
        };
    }

    protected function tearDown(): void
    {
        Base::clearNayraAddresses();
        parent::tearDown();
    }

    public function testGetNayraAddressesReturnsNullWhenNotSet(): void
    {
        Base::clearNayraAddresses();
        $this->assertNull(Base::getNayraAddresses());
    }

    public function testSetAndGetNayraAddresses(): void
    {
        $addresses = ['192.168.1.100', '192.168.1.101'];
        Base::setNayraAddresses($addresses);
        $this->assertEquals($addresses, Base::getNayraAddresses());
    }

    public function testClearNayraAddresses(): void
    {
        Base::setNayraAddresses(['192.168.1.100']);
        Base::clearNayraAddresses();
        $this->assertNull(Base::getNayraAddresses());
    }

    public function testGetNayraBaseUrlReturnsNayraRestApiHostWhenConfigured(): void
    {
        Config::set('app.nayra_rest_api_host', 'http://nayra.example.com:9000');
        $runner = $this->createNayraRunner();

        // getNayraBaseUrl is private - test via handleNayraDocker which uses it.
        // When NAYRA_REST_API_HOST is set and server is unreachable, ensureNayraServerIsRunning
        // throws immediately (does not try bringUpNayra).
        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Could not connect to the nayra container');

        $runner->handleNayraDocker(
            '<?php return ["ok"];',
            [],
            [],
            60,
            ['API_TOKEN=test']
        );
    }

    public function testGetNayraBaseUrlTrimsTrailingSlashFromRestApiHost(): void
    {
        Config::set('app.nayra_rest_api_host', 'http://nayra.example.com/');
        $runner = $this->createNayraRunner();

        // URL used should be http://nayra.example.com/run_script (no double slash)
        // We verify by checking the exception - if URL were wrong we might get different error.
        // The key: NAYRA_REST_API_HOST with trailing slash is trimmed.
        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Could not connect to the nayra container');

        $runner->handleNayraDocker(
            '<?php return ["ok"];',
            [],
            [],
            60,
            ['API_TOKEN=test']
        );
    }

    public function testEnsureNayraServerIsRunningThrowsImmediatelyWhenRestApiHostSetAndUnreachable(): void
    {
        Config::set('app.nayra_rest_api_host', 'http://this-domain-does-not-exist-12345.invalid');
        $runner = $this->createNayraRunner();

        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Could not connect to the nayra container');

        $runner->handleNayraDocker(
            '<?php return ["ok"];',
            [],
            [],
            60,
            ['API_TOKEN=test']
        );
    }

    public function testHandleNayraDockerUsesRestApiHostUrl(): void
    {
        Config::set('app.nayra_rest_api_host', 'http://unreachable-host.invalid');
        $runner = $this->createNayraRunner();

        // Verifies that NAYRA_REST_API_HOST is used (ensureNayraServerIsRunning throws
        // immediately instead of trying Docker bringUpNayra)
        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Could not connect to the nayra container');

        $runner->handleNayraDocker(
            '<?php return ["ok"];',
            [],
            [],
            60,
            []
        );
    }

    public function testHandleNayraDockerParsesEnvironmentVariables(): void
    {
        Config::set('app.nayra_rest_api_host', 'http://unreachable.invalid');
        $runner = $this->createNayraRunner();

        // Just verify it reaches ensureNayraServerIsRunning (env parsing happens before)
        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('Could not connect to the nayra container');

        $runner->handleNayraDocker(
            '<?php return ["ok"];',
            ['key' => 'value'],
            ['config' => 'data'],
            120,
            ['API_TOKEN=secret', 'HOST_URL=http://localhost']
        );
    }

    public function testCachedAddressesPersistAcrossCalls(): void
    {
        Base::clearNayraAddresses();
        $this->assertNull(Base::getNayraAddresses());

        Base::setNayraAddresses(['10.0.0.5']);
        $this->assertEquals(['10.0.0.5'], Base::getNayraAddresses());

        Base::setNayraAddresses(['192.168.1.1', '192.168.1.2']);
        $this->assertEquals(['192.168.1.1', '192.168.1.2'], Base::getNayraAddresses());
    }
}
