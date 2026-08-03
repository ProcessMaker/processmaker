<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Octane\ResetRequestState;
use ProcessMaker\Support\PmFunctionRegistry;
use Tests\TestCase;

/**
 * Test that pmFunctions registry does not leak state between Octane requests.
 */
class FormalExpressionOctaneTest extends TestCase
{
    private function registry(): PmFunctionRegistry
    {
        // Fresh registry instance for test isolation
        return app()->make(PmFunctionRegistry::class);
    }

    public function test_pm_functions_do_not_accumulate_across_requests(): void
    {
        PmFunctionRegistry::clear();
        FormalExpression::resetPmFunctions();

        // Request 1: register a custom PM function (runtime registration)
        $formalExp1 = new FormalExpression();
        $formalExp1->setLanguage('FEEL');
        $formalExp1->setBody('customFn("test") == "test"');

        $reflection = new \ReflectionClass($formalExp1);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);
        $method->invoke($formalExp1, 'customFn', function ($arguments, $arg) {
            return (string) $arg;
        });

        $this->assertCount(1, $this->registry()->all());

        // Simulate Octane RequestTerminated reset
        $resetState = new ResetRequestState();
        $resetState->handle();

        // After reset, runtime-registered functions should be cleared
        $this->assertCount(0, $this->registry()->all());

        // Request 2: register another custom function after reset
        $formalExp2 = new FormalExpression();
        $formalExp2->setLanguage('FEEL');

        $reflection2 = new \ReflectionClass($formalExp2);
        $method2 = $reflection2->getMethod('registerPMFunction');
        $method2->setAccessible(true);
        $method2->invoke($formalExp2, 'anotherFn', function ($arguments, $arg) {
            return strtoupper((string) $arg);
        });

        // Only the new function should exist after reset
        $this->assertCount(1, $this->registry()->all());
        $this->assertArrayHasKey('anotherFn', $this->registry()->all());
    }

    public function test_built_in_functions_are_always_available(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');
        $formalExp->setBody('date("Y") > 1900');
        $this->assertTrue($formalExp([]));
    }

    public function test_custom_pm_function_is_evaluable(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');
        $formalExp->setBody('greet("World") == "Hello, World!"');

        $reflection = new \ReflectionClass($formalExp);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);
        $method->invoke($formalExp, 'greet', function ($arguments, $name) {
            return 'Hello, ' . $name . '!';
        });

        $this->assertTrue($formalExp([]));
    }

    public function test_registering_same_function_twice_overwrites(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');

        $reflection = new \ReflectionClass($formalExp);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);

        $method->invoke($formalExp, 'double', function ($arguments, $x) {
            return $x * 2;
        });

        $method->invoke($formalExp, 'double', function ($arguments, $x) {
            return $x * 3;
        });

        $formalExp->setBody('double(2) == 6');
        $this->assertTrue($formalExp([]));
    }

    public function test_pm_functions_static_array_does_not_grow_with_instances(): void
    {
        PmFunctionRegistry::clear();
        FormalExpression::resetPmFunctions();

        $initialCount = count($this->registry()->all());

        for ($i = 0; $i < 10; $i++) {
            $exp = new FormalExpression();
            $exp->setLanguage('FEEL');
        }

        $this->assertCount($initialCount, $this->registry()->all());
    }

    public function test_reset_pm_functions_restores_boot_time_state(): void
    {
        PmFunctionRegistry::clear();

        // Register a boot-time function
        $formalExp = new FormalExpression();
        $reflection = new \ReflectionClass($formalExp);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);
        $method->invoke($formalExp, 'bootFn', function () {
        });

        // Mark current state as boot baseline
        FormalExpression::resetPmFunctions();

        // Register a runtime function after the boot baseline
        $method->invoke($formalExp, 'runtimeFn', function () {
        });

        $this->assertCount(2, $this->registry()->all());

        // Reset should clear runtime functions, keeping only boot-time
        FormalExpression::resetPmFunctions();

        $this->assertCount(1, $this->registry()->all());
        $this->assertArrayHasKey('bootFn', $this->registry()->all());
        $this->assertArrayNotHasKey('runtimeFn', $this->registry()->all());
    }

    public function test_boot_time_functions_survive_reset(): void
    {
        PmFunctionRegistry::clear();

        // Register a boot-time function
        $formalExp = new FormalExpression();
        $reflection = new \ReflectionClass($formalExp);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);
        $bootFn = function () {
        };
        $method->invoke($formalExp, 'bootFn', $bootFn);

        // Mark current state as boot baseline
        FormalExpression::resetPmFunctions();

        // Register a runtime function after the boot baseline
        $method->invoke($formalExp, 'runtimeFn', function () {
        });

        // Reset should preserve bootFn
        FormalExpression::resetPmFunctions();

        $this->assertCount(1, $this->registry()->all());
        $this->assertSame($bootFn, $this->registry()->all()['bootFn']);
    }
}
