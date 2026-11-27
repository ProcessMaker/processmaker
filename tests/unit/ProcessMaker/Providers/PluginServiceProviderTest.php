<?php

namespace Tests\unit\ProcessMaker\Providers;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Providers\PluginServiceProvider;
use ReflectionMethod;
use Tests\TestCase;

class PluginServiceProviderTest extends TestCase
{
    public function testIncompletePluginIsSkippedInsteadOfRegisteringItsProvider(): void
    {
        Log::spy();

        $pluginPath = storage_path('plugins/incomplete-plugin-' . uniqid());
        mkdir($pluginPath, 0755, true);
        file_put_contents($pluginPath . '/composer.json', json_encode([
            'extra' => [
                'laravel' => [
                    'providers' => [
                        'ProcessMaker\\Package\\Missing\\PackageServiceProvider',
                    ],
                ],
            ],
        ]));

        try {
            $provider = new PluginServiceProvider(app());
            $method = new ReflectionMethod($provider, 'loadPlugin');
            $method->invoke($provider, $pluginPath);

            $this->assertNull(app()->getProvider('ProcessMaker\\Package\\Missing\\PackageServiceProvider'));
            Log::shouldHaveReceived('warning')
                ->once()
                ->withArgs(fn (string $message) => str_contains($message, 'dependencies are not installed'));
        } finally {
            @unlink($pluginPath . '/composer.json');
            @rmdir($pluginPath);
        }
    }
}
