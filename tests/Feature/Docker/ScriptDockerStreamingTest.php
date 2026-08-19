<?php

namespace Tests\Feature\Docker;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log as LogFacade;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Exception\ScriptException;
use ProcessMaker\Managers\DockerManager;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use ProcessMaker\Models\ScriptDockerStreamingFilesTrait;

class ScriptDockerStreamingTest extends TestCase
{
    private ?string $mockDockerPath = null;

    private ScriptDockerStreamingTestHarness $harness;

    protected function setUp(): void
    {
        $this->mockDockerPath = tempnam(sys_get_temp_dir(), 'mock-docker-');
        unlink($this->mockDockerPath);

        file_put_contents($this->mockDockerPath, str_replace(
            '__LOG_FILE__',
            sys_get_temp_dir() . '/pm-mock-docker-rm.log',
            $this->getMockDockerScript()
        ));
        chmod($this->mockDockerPath, 0755);

        $this->bootstrapLaravel([
            'app.processmaker_scripts_docker' => $this->mockDockerPath,
            'app.processmaker_scripts_docker_host' => '',
            'app.processmaker_scripts_timeout' => 'timeout',
            'app.processmaker_scripts_home' => sys_get_temp_dir(),
        ]);

        $this->harness = new ScriptDockerStreamingTestHarness();
    }

    /**
     * Bootstrap a minimal Laravel container for config(), Docker, and Log facades.
     *
     * @param array<string, mixed> $configValues
     */
    private function bootstrapLaravel(array $configValues): void
    {
        $config = [];
        foreach ($configValues as $key => $value) {
            data_set($config, $key, $value);
        }

        $app = new Application(dirname(__DIR__, 3));
        $app->singleton('config', fn () => new ConfigRepository($config));
        $app->singleton(DockerManager::class, fn () => new DockerManager());
        $logger = new Logger('test', [new NullHandler()]);
        $app->singleton('log', fn () => $logger);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        LogFacade::swap($logger);

        AliasLoader::getInstance(
            Facade::defaultAliases()->merge([
                'Docker' => \ProcessMaker\Facades\Docker::class,
            ])->all()
        )->register();

        if (!class_exists('ProcessMaker\\Models\\Log', false)) {
            // ScriptDockerCopyingFilesTrait imports Log from the current namespace.
            class_alias(LogFacade::class, 'ProcessMaker\\Models\\Log');
        }
    }

    protected function tearDown(): void
    {
        if ($this->mockDockerPath && file_exists($this->mockDockerPath)) {
            unlink($this->mockDockerPath);
        }

        $logFile = sys_get_temp_dir() . '/pm-mock-docker-rm.log';
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        parent::tearDown();
    }

    public function testStreamingModeExecutesWithMockedDocker(): void
    {
        putenv('PM_MOCK_STREAMING=1');

        $options = [
            'image' => 'processmaker/mock-streaming:latest',
            'command' => 'ignored',
            'parameters' => '',
            'timeout' => 30,
            'inputs' => [
                '/opt/executor/data.json' => '{"input":"value"}',
                '/opt/executor/config.json' => '{}',
            ],
            'outputs' => [
                'response' => '/opt/executor/output.json',
            ],
        ];

        $response = $this->harness->executeStreamingMode($options);

        $this->assertSame(0, $response['returnCode']);
        $this->assertSame('{"mocked":true}', $response['outputs']['response']);
        $this->assertTrue($this->harness->imageSupportsStreamingMode($options['image']));

        putenv('PM_MOCK_STREAMING');
    }

    public function testStreamingModeFallsBackToCopyingWhenRunStreamMissing(): void
    {
        putenv('PM_MOCK_STREAMING=0');

        $options = [
            'image' => 'processmaker/mock-no-stream:latest',
            'command' => 'echo test',
            'parameters' => '',
            'timeout' => 30,
            'inputs' => [
                '/opt/executor/data.json' => '{"input":"value"}',
            ],
            'outputs' => [
                'response' => '/opt/executor/output.json',
            ],
        ];

        $response = $this->harness->executeStreamingMode($options);

        $this->assertSame(0, $response['returnCode']);
        $this->assertSame('{"copied":true}', $response['outputs']['response']);
        $this->assertFalse($this->harness->imageSupportsStreamingMode($options['image']));

        $logFile = sys_get_temp_dir() . '/pm-mock-docker-rm.log';
        $this->assertFileExists($logFile);
        $this->assertStringContainsString('rm called', file_get_contents($logFile));

        putenv('PM_MOCK_STREAMING');
    }

    public function testCopyingModeRemovesContainerWhenStartFails(): void
    {
        putenv('PM_MOCK_START_FAIL=1');

        $options = [
            'image' => 'processmaker/mock-copy:latest',
            'command' => 'echo test',
            'parameters' => '',
            'timeout' => 30,
            'inputs' => [
                '/opt/executor/data.json' => '{}',
            ],
            'outputs' => [
                'response' => '/opt/executor/output.json',
            ],
        ];

        try {
            $this->harness->executeCopyingMode($options);
            $this->fail('Expected ScriptException was not thrown');
        } catch (ScriptException) {
            // Expected from startContainer failure path
        }

        $logFile = sys_get_temp_dir() . '/pm-mock-docker-rm.log';
        $this->assertFileExists($logFile);
        $this->assertStringContainsString('rm called', file_get_contents($logFile));

        putenv('PM_MOCK_START_FAIL');
    }

    private function getMockDockerScript(): string
    {
        return <<<'SH'
#!/bin/sh
set -e

LOG_FILE="__LOG_FILE__"
CMD="$1"
shift || true
REST="$*"

case "$CMD" in
  run)
    case "$REST" in
      *"test -f /opt/executor/run-stream.sh"*)
        if [ "${PM_MOCK_STREAMING}" = "1" ]; then exit 0; else exit 1; fi
        ;;
    esac
    case "$REST" in
      *run-stream.sh*)
        cat > /dev/null
        TMPDIR=$(mktemp -d)
        mkdir -p "$TMPDIR/opt/executor"
        printf '%s' '{"mocked":true}' > "$TMPDIR/opt/executor/output.json"
        tar cf - -C "$TMPDIR" opt/executor/output.json
        rm -rf "$TMPDIR"
        exit 0
        ;;
    esac
    ;;
  create)
    CIDFILE=""
    PREV=""
    for arg in $REST; do
      if [ "$PREV" = "--cidfile" ]; then
        CIDFILE="$arg"
      fi
      PREV="$arg"
    done
    if [ -n "$CIDFILE" ]; then
      printf '%s' 'mock-container-id' > "$CIDFILE"
    fi
    exit 0
    ;;
  cp)
    case "$REST" in
      mock-container-id:*)
        DEST=$(echo "$REST" | awk '{print $NF}')
        printf '%s' '{"copied":true}' > "$DEST"
        exit 0
        ;;
    esac
    exit 0
    ;;
  start)
    if [ "${PM_MOCK_START_FAIL}" = "1" ]; then
      echo "mock start failure" >&2
      exit 1
    fi
    exit 0
    ;;
  rm)
    echo "rm called" >> "$LOG_FILE"
    exit 0
    ;;
esac

exit 0
SH;
    }
}

class ScriptDockerStreamingTestHarness
{
    use ScriptDockerStreamingFilesTrait;
    use ScriptDockerCopyingFilesTrait;

    public function executeStreamingMode(array $options): array
    {
        return $this->executeStreaming($options);
    }

    public function imageSupportsStreamingMode(string $image): bool
    {
        return $this->imageSupportsStreaming($image);
    }

    public function executeCopyingMode(array $options): array
    {
        return $this->executeCopying($options);
    }
}
