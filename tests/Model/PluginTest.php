<?php

namespace Tests\Model;

use ProcessMaker\Models\Plugin;
use RuntimeException;
use Tests\TestCase;

class PluginTest extends TestCase
{
    private $tempDir;

    public function setUpTestPlugin()
    {
        $this->tempDir = sys_get_temp_dir() . '/plugin-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    public function tearDownTestPlugin()
    {
        if ($this->tempDir && is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    public function testFromPathCreatesPluginInstance()
    {
        $this->setUpTestPlugin();
        $plugin = Plugin::fromPath($this->tempDir);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertEquals(basename($this->tempDir), $plugin->getName());
        $this->assertEquals($this->tempDir, $plugin->getPath());
    }

    public function testFromPathThrowsExceptionForNonExistentDirectory()
    {
        $this->expectException(RuntimeException::class);
        Plugin::fromPath('/non/existent/path');
    }

    public function testGetComposerJsonReadsAndParsesFile()
    {
        $this->setUpTestPlugin();
        $composerJson = [
            'name' => 'test/plugin',
            'description' => 'Test plugin',
            'autoload' => [
                'psr-4' => [
                    'ProcessMaker\\Plugins\\Test\\' => 'src/',
                ],
            ],
        ];

        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        $plugin = Plugin::fromPath($this->tempDir);
        $result = $plugin->getComposerJson();

        $this->assertEquals($composerJson, $result);
    }

    public function testGetComposerJsonThrowsExceptionForMissingFile()
    {
        $this->setUpTestPlugin();
        $plugin = Plugin::fromPath($this->tempDir);

        $this->expectException(RuntimeException::class);
        $plugin->getComposerJson();
    }

    public function testGetComposerJsonThrowsExceptionForInvalidJson()
    {
        $this->setUpTestPlugin();
        file_put_contents($this->tempDir . '/composer.json', '{ invalid json }');

        $plugin = Plugin::fromPath($this->tempDir);

        $this->expectException(RuntimeException::class);
        $plugin->getComposerJson();
    }

    public function testGetDescriptionReturnsDescriptionFromComposerJson()
    {
        $this->setUpTestPlugin();
        $composerJson = [
            'name' => 'test/plugin',
            'description' => 'Test plugin description',
        ];

        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        $plugin = Plugin::fromPath($this->tempDir);
        $this->assertEquals('Test plugin description', $plugin->getDescription());
    }

    public function testGetDescriptionReturnsNullWhenMissing()
    {
        $this->setUpTestPlugin();
        $composerJson = ['name' => 'test/plugin'];

        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        $plugin = Plugin::fromPath($this->tempDir);
        $this->assertNull($plugin->getDescription());
    }

    public function testGetNameReturnsDirectoryName()
    {
        $this->setUpTestPlugin();
        $plugin = Plugin::fromPath($this->tempDir);
        $this->assertEquals(basename($this->tempDir), $plugin->getName());
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
