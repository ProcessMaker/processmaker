<?php

namespace Tests\Managers;

use ProcessMaker\Managers\ScreenBuilderManager;
use Tests\TestCase;

class ScreenBuilderManagerTest extends TestCase
{
    private string $pluginsDir;

    /** @var list<string> */
    private array $pluginDirsToRemove = [];

    /** @var list<string> */
    private array $publicAssetPaths = [];

    protected function setUp(): void
    {
        chdir(realpath(__DIR__ . '/../..'));
        parent::setUp();

        $this->pluginsDir = storage_path('plugins');
        if (!is_dir($this->pluginsDir)) {
            mkdir($this->pluginsDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->pluginDirsToRemove as $dir) {
            if (is_dir($dir)) {
                $this->deleteDirectory($dir);
            }
        }
        foreach ($this->publicAssetPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        chdir(realpath(__DIR__ . '/../..'));

        parent::tearDown();
    }

    public function testStoragePluginScreenBuilderScripts(): void
    {
        $enabledPackage = 'test-plg-plugin-' . uniqid();
        $enabledDir = $this->pluginsDir . '/' . $enabledPackage;
        $this->pluginDirsToRemove[] = $enabledDir;
        mkdir($enabledDir, 0755, true);
        file_put_contents($enabledDir . '/composer.json', json_encode([
            'name' => 'processmaker/' . $enabledPackage,
        ]));
        $this->touchPublicAsset($enabledPackage, 'screen-builder-form-components.js');

        $manager = new ScreenBuilderManager();
        $manager->addPackageScripts('FORM');
        $scripts = $manager->getScripts();
        $this->assertTrue($this->scriptsContainPath(
            $scripts,
            '/vendor/processmaker/packages/' . $enabledPackage . '/js/screen-builder-form-components.js'
        ));

        $disabledPackage = 'disabled-plg-plugin-' . uniqid();
        $disabledDir = $this->pluginsDir . '/_' . $disabledPackage;
        $this->pluginDirsToRemove[] = $disabledDir;
        mkdir($disabledDir, 0755, true);
        file_put_contents($disabledDir . '/composer.json', json_encode([
            'name' => 'processmaker/' . $disabledPackage,
        ]));
        $this->touchPublicAsset($disabledPackage, 'screen-builder-form-components.js');

        $managerDisabled = new ScreenBuilderManager();
        $managerDisabled->addPackageScripts('FORM');
        $disabledScripts = $managerDisabled->getScripts();
        $this->assertFalse($this->scriptsContainPath(
            $disabledScripts,
            '/vendor/processmaker/packages/' . $disabledPackage . '/js/screen-builder-form-components.js'
        ));

        $displayOnlyPackage = 'test-plg-display-only-' . uniqid();
        $displayDir = $this->pluginsDir . '/' . $displayOnlyPackage;
        $this->pluginDirsToRemove[] = $displayDir;
        mkdir($displayDir, 0755, true);
        file_put_contents($displayDir . '/composer.json', json_encode([
            'name' => 'processmaker/' . $displayOnlyPackage,
        ]));
        $this->touchPublicAsset($displayOnlyPackage, 'screen-builder-form-components.js');

        $managerDisplay = new ScreenBuilderManager();
        $managerDisplay->addPackageScripts('DISPLAY');
        $displayScripts = $managerDisplay->getScripts();
        $this->assertFalse($this->scriptsContainPath(
            $displayScripts,
            '/vendor/processmaker/packages/' . $displayOnlyPackage . '/js/screen-builder-form-components.js'
        ));

        $folderName = 'my-plugin-folder-' . uniqid();
        $resolvedName = 'resolved-plg-name-' . uniqid();
        $folderDir = $this->pluginsDir . '/' . $folderName;
        $this->pluginDirsToRemove[] = $folderDir;
        mkdir($folderDir, 0755, true);
        file_put_contents($folderDir . '/composer.json', json_encode([
            'name' => 'processmaker/' . $resolvedName,
        ]));
        $this->touchPublicAsset($resolvedName, 'screen-builder-form-components.js');

        $managerResolved = new ScreenBuilderManager();
        $managerResolved->addPackageScripts('FORM');
        $resolvedScripts = $managerResolved->getScripts();
        $this->assertTrue($this->scriptsContainPath(
            $resolvedScripts,
            '/vendor/processmaker/packages/' . $resolvedName . '/js/screen-builder-form-components.js'
        ));
    }

    private function touchPublicAsset(string $packageName, string $fileName): string
    {
        $dir = public_path('vendor/processmaker/packages/' . $packageName . '/js');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $fileName;
        file_put_contents($path, '// test asset');
        $this->publicAssetPaths[] = $path;

        return $path;
    }

    private function scriptsContainPath(array $scripts, string $path): bool
    {
        foreach ($scripts as $script) {
            if (str_starts_with($script, $path)) {
                return true;
            }
        }

        return false;
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path) && !is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
