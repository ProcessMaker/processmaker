<?php

namespace Tests\Unit\ProcessMaker\Models;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Models\ScriptDockerCopyingFilesTrait;
use ProcessMaker\Models\ScriptDockerStreamingFilesTrait;
use RuntimeException;

class ScriptDockerStreamingFilesTraitTest extends TestCase
{
    private ScriptDockerStreamingTestHarness $harness;

    protected function setUp(): void
    {
        $this->harness = new ScriptDockerStreamingTestHarness();
    }

    public function testBuildUstarTarCreatesValidArchive(): void
    {
        $inputs = [
            '/opt/executor/data.json' => '{"foo":"bar"}',
            '/opt/executor/config.json' => '{"key":"value"}',
            '/opt/executor/script.php' => '<?php return ["ok" => true];',
        ];

        $tar = $this->harness->buildTar($inputs);
        $parsed = $this->harness->parseTar($tar);

        foreach ($inputs as $path => $content) {
            $normalized = ltrim($path, '/');
            $this->assertArrayHasKey($normalized, $parsed);
            $this->assertSame($content, $parsed[$normalized]);
        }
    }

    public function testBuildUstarTarHandlesLongPaths(): void
    {
        $longPath = '/opt/executor/' . str_repeat('nested/', 20) . 'data.json';
        $content = '{"long":"path"}';
        $inputs = [$longPath => $content];

        $tar = $this->harness->buildTar($inputs);
        $parsed = $this->harness->parseTar($tar);

        $this->assertSame($content, $parsed[ltrim($longPath, '/')]);
    }

    public function testValidateUstarTarThrowsOnMissingFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tar validation failed');

        $this->harness->validateTar(str_repeat("\0", 1024), [
            '/opt/executor/missing.json' => '{}',
        ]);
    }

    public function testParseUstarTarReturnsEmptyForBlankArchive(): void
    {
        $this->assertSame([], $this->harness->parseTar(str_repeat("\0", 1024)));
    }
}

class ScriptDockerStreamingTestHarness
{
    use ScriptDockerStreamingFilesTrait;
    use ScriptDockerCopyingFilesTrait;

    public function buildTar(array $inputs): string
    {
        return $this->buildUstarTar($inputs);
    }

    public function parseTar(string $tar): array
    {
        return $this->parseUstarTar($tar);
    }

    public function validateTar(string $tar, array $expected): void
    {
        $this->validateUstarTar($tar, $expected);
    }

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
