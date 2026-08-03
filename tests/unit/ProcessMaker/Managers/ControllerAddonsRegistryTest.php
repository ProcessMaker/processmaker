<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Managers;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ProcessMaker\Http\Controllers\Admin\UserController;
use ProcessMaker\Managers\ControllerAddonsRegistry;

class ControllerAddonsRegistryTest extends BaseTestCase
{
    private ControllerAddonsRegistry $registry;

    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ControllerAddonsRegistry();
        $this->bindRegistryInContainer();
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_register_addon_is_retrieved_for_matching_scope_and_method(): void
    {
        $this->registry->register(UserController::class, [
            'id' => 'test-addon',
            'method' => 'edit',
            'title' => 'Test Addon',
            'content' => 'addon-content',
        ]);

        $addons = $this->registry->getAddons(UserController::class, 'edit', []);

        $this->assertCount(1, $addons);
        $this->assertSame('test-addon', $addons[0]['id']);
        $this->assertSame('addon-content', $addons[0]['content']);
    }

    public function test_addons_from_other_controllers_are_not_returned(): void
    {
        $this->registry->register(UserController::class, [
            'id' => 'user-addon',
            'method' => 'edit',
            'content' => 'user-content',
        ]);
        $this->registry->register('Other\\Controller', [
            'id' => 'other-addon',
            'method' => 'edit',
            'content' => 'other-content',
        ]);

        $addons = $this->registry->getAddons(UserController::class, 'edit', []);

        $this->assertCount(1, $addons);
        $this->assertSame('user-addon', $addons[0]['id']);
    }

    public function test_get_plugin_addons_does_not_mutate_registered_addons(): void
    {
        $this->registry->register(UserController::class, [
            'id' => 'test-addon',
            'method' => 'edit',
            'content' => 'original-content',
        ]);

        $this->registry->getAddons(UserController::class, 'edit', []);
        $this->registry->getAddons(UserController::class, 'edit', []);

        $addons = $this->registry->getAddons(UserController::class, 'edit', []);

        $this->assertCount(1, $addons);
        $this->assertSame('original-content', $addons[0]['content']);
    }

    public function test_register_addon_static_method_delegates_to_registry(): void
    {
        UserController::registerAddon([
            'id' => 'static-addon',
            'method' => 'edit.settings',
            'content' => 'settings-content',
        ]);

        $controller = new UserController();
        $addons = $this->invokeGetPluginAddons($controller, 'edit.settings', []);

        $this->assertCount(1, $addons);
        $this->assertSame('static-addon', $addons[0]['id']);
    }

    public function test_callable_data_modifier_is_applied_when_resolving_addons(): void
    {
        $this->registry->register(UserController::class, [
            'id' => 'callable-addon',
            'method' => 'edit',
            'content' => 'content',
            'data' => fn (array $data) => array_merge($data, ['extra' => 'value']),
        ]);

        $addons = $this->registry->getAddons(UserController::class, 'edit', ['base' => 'data']);

        $this->assertCount(1, $addons);
    }

    private function bindRegistryInContainer(): void
    {
        $this->previousContainer = Container::getInstance();

        $container = new Container();
        $container->singleton(ControllerAddonsRegistry::class, fn () => $this->registry);
        Container::setInstance($container);

        if (!function_exists('app')) {
            require_once dirname(__DIR__, 4) . '/vendor/laravel/framework/src/Illuminate/Foundation/helpers.php';
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invokeGetPluginAddons(object $controller, string $method, array $data): array
    {
        $reflection = new \ReflectionMethod($controller, 'getPluginAddons');

        return $reflection->invoke($controller, $method, $data);
    }
}
