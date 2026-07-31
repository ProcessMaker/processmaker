<?php

namespace Tests\Managers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Managers\PluginManager;
use ProcessMaker\Models\Plugin;
use RuntimeException;
use Tests\TestCase;

class PluginManagerTest extends TestCase
{
    private $pluginsDir;

    private $tempPluginDir;

    public function setUpTestPluginManager()
    {
        $this->pluginsDir = storage_path('plugins');
        if (!is_dir($this->pluginsDir)) {
            mkdir($this->pluginsDir, 0755, true);
        }
    }

    public function tearDownTestPluginManager()
    {
        if ($this->tempPluginDir && is_dir($this->tempPluginDir)) {
            $this->deleteDirectory($this->tempPluginDir);
        }
    }

    public function testInstallClonesRepositoryAndValidatesPlugin()
    {
        $this->setUpTestPluginManager();

        $repoUrl = 'https://github.com/ProcessMaker/plugin-example.git';
        $pluginName = 'plugin-example';
        $pluginPath = $this->pluginsDir . '/' . $pluginName;

        // Create temporary plugin directory with valid composer.json
        $this->tempPluginDir = $pluginPath;
        mkdir($this->tempPluginDir, 0755, true);
        $composerJson = [
            'name' => 'processmaker/plugin-example',
            'description' => 'Test plugin',
            'autoload' => [
                'psr-4' => [
                    'ProcessMaker\\Plugins\\Example\\' => 'src/',
                ],
            ],
        ];
        file_put_contents(
            $this->tempPluginDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        // Fake Process to return successful results
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);

        // Mock Artisan::all() to return empty array (no plugin install command)
        Artisan::shouldReceive('all')
            ->andReturn([]);

        Event::fake([PluginLog::class]);

        $manager = new PluginManager();
        $manager->install($repoUrl);

        Event::assertDispatched(PluginLog::class, function ($event) {
            return str_contains($event->message, 'installed successfully');
        });
    }

    public function testInstallValidatesComposerJsonNamespace()
    {
        $this->setUpTestPluginManager();

        $repoUrl = 'https://github.com/ProcessMaker/invalid-plugin.git';
        $pluginName = 'invalid-plugin';
        $pluginPath = $this->pluginsDir . '/' . $pluginName;

        // Create temporary plugin directory with invalid composer.json
        $this->tempPluginDir = $pluginPath;
        mkdir($this->tempPluginDir, 0755, true);
        $composerJson = [
            'name' => 'invalid/plugin',
            'autoload' => [
                'psr-4' => [
                    'Invalid\\Namespace\\' => 'src/',
                ],
            ],
        ];
        file_put_contents(
            $this->tempPluginDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        // Fake Process to return successful results
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);

        Event::fake([PluginLog::class]);

        $manager = new PluginManager();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Plugin PSR-4 namespace must start with 'ProcessMaker\\Plugins\\'");

        $manager->install($repoUrl);
    }

    public function testUninstallRemovesPluginDirectory()
    {
        $this->setUpTestPluginManager();

        $pluginName = 'test-plugin';
        $pluginPath = $this->pluginsDir . '/' . $pluginName;

        // Create temporary plugin directory
        $this->tempPluginDir = $pluginPath;
        mkdir($this->tempPluginDir, 0755, true);
        file_put_contents($this->tempPluginDir . '/test.txt', 'test');

        // Mock Artisan::all() to return empty array (no plugin uninstall command)
        Artisan::shouldReceive('all')
            ->andReturn([]);

        Event::fake([PluginLog::class]);

        $manager = new PluginManager();
        $manager->uninstall($pluginName);

        $this->assertFalse(is_dir($pluginPath));
        Event::assertDispatched(PluginLog::class, function ($event) {
            return str_contains($event->message, 'uninstalled successfully');
        });
    }

    public function testUninstallThrowsExceptionForNonExistentPlugin()
    {
        $this->setUpTestPluginManager();

        $manager = new PluginManager();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Plugin not found: non-existent-plugin');

        $manager->uninstall('non-existent-plugin');
    }

    public function testListReturnsInstalledPlugins()
    {
        $this->setUpTestPluginManager();

        // Create test plugin directories
        $plugin1Path = $this->pluginsDir . '/plugin-one';
        $plugin2Path = $this->pluginsDir . '/plugin-two';
        $this->tempPluginDir = $this->pluginsDir;

        mkdir($plugin1Path, 0755, true);
        mkdir($plugin2Path, 0755, true);

        $composerJson1 = [
            'name' => 'test/plugin-one',
            'description' => 'First test plugin',
            'autoload' => [
                'psr-4' => [
                    'ProcessMaker\\Plugins\\One\\' => 'src/',
                ],
            ],
        ];

        $composerJson2 = [
            'name' => 'test/plugin-two',
            'description' => 'Second test plugin',
            'autoload' => [
                'psr-4' => [
                    'ProcessMaker\\Plugins\\Two\\' => 'src/',
                ],
            ],
        ];

        file_put_contents(
            $plugin1Path . '/composer.json',
            json_encode($composerJson1, JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $plugin2Path . '/composer.json',
            json_encode($composerJson2, JSON_PRETTY_PRINT)
        );

        // Mock git commands to return null (no git repo)
        Process::fake([
            '*' => Process::result(''),
        ]);

        $manager = new PluginManager();
        $plugins = $manager->list();

        $this->assertCount(2, $plugins);
        $this->assertEquals('plugin-one', $plugins[0]['name']);
        $this->assertEquals('First test plugin', $plugins[0]['description']);
        $this->assertEquals('plugin-two', $plugins[1]['name']);
        $this->assertEquals('Second test plugin', $plugins[1]['description']);
    }

    public function testListIgnoresPluginsStartingWithUnderscore()
    {
        $this->setUpTestPluginManager();

        $validPluginPath = $this->pluginsDir . '/valid-plugin';
        $hiddenPluginPath = $this->pluginsDir . '/_hidden-plugin';
        $this->tempPluginDir = $this->pluginsDir;

        mkdir($validPluginPath, 0755, true);
        mkdir($hiddenPluginPath, 0755, true);

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
            $validPluginPath . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );
        file_put_contents(
            $hiddenPluginPath . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        Process::fake();

        $manager = new PluginManager();
        $plugins = $manager->list();

        $this->assertCount(1, $plugins);
        $this->assertEquals('valid-plugin', $plugins[0]['name']);
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
