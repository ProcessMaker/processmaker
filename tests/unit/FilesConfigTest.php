<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FilesConfigTest extends TestCase
{
    private const ENV_ALLOWED = 'ALLOWED_EXTENSIONS';

    private const ENV_DANGEROUS = 'DANGEROUS_EXTENSIONS';

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('env')) {
            require_once __DIR__ . '/../../vendor/laravel/framework/src/Illuminate/Foundation/helpers.php';
        }
    }

    protected function tearDown(): void
    {
        $this->unsetEnv(self::ENV_ALLOWED);
        $this->unsetEnv(self::ENV_DANGEROUS);

        parent::tearDown();
    }

    public function test_allowed_extensions_use_defaults_when_env_unset(): void
    {
        $this->unsetEnv(self::ENV_ALLOWED);

        $config = $this->loadFilesConfig();

        $this->assertContains('pdf', $config['allowed_extensions']);
        $this->assertContains('mp4', $config['allowed_extensions']);
    }

    public function test_allowed_extensions_parsed_from_env(): void
    {
        $this->setEnv(self::ENV_ALLOWED, 'pdf, custom ,TXT');

        $config = $this->loadFilesConfig();

        $this->assertSame(['pdf', 'custom', 'txt'], $config['allowed_extensions']);
    }

    public function test_dangerous_extensions_parsed_from_env(): void
    {
        $this->setEnv(self::ENV_DANGEROUS, 'zip,rar');

        $config = $this->loadFilesConfig();

        $this->assertSame(['zip', 'rar'], $config['dangerous_extensions']);
    }

    public function test_dangerous_extensions_defaults_when_env_empty(): void
    {
        $this->unsetEnv(self::ENV_DANGEROUS);

        $config = $this->loadFilesConfig();

        $this->assertContains('zip', $config['dangerous_extensions']);
        $this->assertContains('7z', $config['dangerous_extensions']);
    }

    private function loadFilesConfig(): array
    {
        return include __DIR__ . '/../../config/files.php'; // NOSONAR: load fresh config after changing environment variables.
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function unsetEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
