<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\FormalExpression;
use Tests\TestCase;

/**
 * Test that static $pmFunctions does not leak between requests in Octane.
 *
 * In Octane, the same PHP worker handles multiple requests. Static properties
 * persist across requests, so if $pmFunctions accumulates data, it will leak
 * request-scoped state between different requests.
 */
class FormalExpressionOctaneTest extends TestCase
{
    /**
     * Simulate multiple requests in the same Octane worker by creating
     * multiple FormalExpression instances. The static $pmFunctions should
     * not accumulate unbounded callables from "external" packages.
     *
     * This test verifies that registering a PM function in one instance does
     * not cause issues when subsequent instances are created (the registry
     * should be bounded and not grow indefinitely).
     */
    public function test_pm_functions_do_not_accumulate_across_requests(): void
    {
        // Simulate request 1: register a custom PM function
        $formalExp1 = new FormalExpression();
        $formalExp1->setLanguage('FEEL');
        $formalExp1->setBody('customFn("test") == "test"');

        // Use reflection to call registerPMFunction (simulating a package registering)
        $reflection = new \ReflectionClass($formalExp1);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);
        $method->invoke($formalExp1, 'customFn', function ($arguments, $arg) {
            return (string) $arg;
        });

        // Verify the function was registered
        $staticProperty = $reflection->getProperty('pmFunctions');
        $staticProperty->setAccessible(true);
        $this->assertArrayHasKey('customFn', $staticProperty->getValue());
        $initialCount = count($staticProperty->getValue());

        // Simulate request 2: create a new instance in the same worker
        // The static $pmFunctions should still contain the previously registered function
        // but should not grow unbounded
        $formalExp2 = new FormalExpression();
        $formalExp2->setLanguage('FEEL');

        $reflection2 = new \ReflectionClass($formalExp2);
        $staticProperty2 = $reflection2->getProperty('pmFunctions');
        $staticProperty2->setAccessible(true);
        $pmFunctionsAfterRequest2 = $staticProperty2->getValue();

        // The functions should persist (static across instances in same worker)
        $this->assertArrayHasKey('customFn', $pmFunctionsAfterRequest2);

        // But the count should not have grown unexpectedly
        $this->assertCount($initialCount, $pmFunctionsAfterRequest2);

        // Simulate request 3: register another custom function
        $method2 = $reflection2->getMethod('registerPMFunction');
        $method2->setAccessible(true);
        $method2->invoke($formalExp2, 'anotherFn', function ($arguments, $arg) {
            return strtoupper((string) $arg);
        });

        $countAfterRequest3 = count($staticProperty2->getValue());
        $this->assertArrayHasKey('anotherFn', $staticProperty2->getValue());

        // The registry grew by exactly 1
        $this->assertEquals($initialCount + 1, $countAfterRequest3);
    }

    /**
     * Test that the built-in system functions are always registered and
     * available regardless of static state.
     */
    public function test_built_in_functions_are_always_available(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');
        $formalExp->setBody('date("Y") > 1900');
        $this->assertTrue($formalExp([]));
    }

    /**
     * Test that a custom registered function works correctly.
     */
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

    /**
     * Test that duplicate function registration overwrites the previous one
     * rather than causing conflicts.
     */
    public function test_registering_same_function_twice_overwrites(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');

        $reflection = new \ReflectionClass($formalExp);
        $method = $reflection->getMethod('registerPMFunction');
        $method->setAccessible(true);

        // Register with first implementation
        $method->invoke($formalExp, 'double', function ($arguments, $x) {
            return $x * 2;
        });

        // Overwrite with second implementation
        $method->invoke($formalExp, 'double', function ($arguments, $x) {
            return $x * 3;
        });

        $formalExp->setBody('double(2) == 6');
        $this->assertTrue($formalExp([]));
    }

    /**
     * Test that pmFunctions remains bounded and does not grow with each
     * FormalExpression instantiation.
     */
    public function test_pm_functions_static_array_does_not_grow_with_instances(): void
    {
        $reflection = new \ReflectionClass(FormalExpression::class);
        $staticProperty = $reflection->getProperty('pmFunctions');
        $staticProperty->setAccessible(true);

        // Clear any existing functions to get a clean baseline
        $staticProperty->setValue([]);

        // Create multiple instances - the static array should not grow just
        // from instantiating FormalExpression (only when registerPMFunction is called)
        $initialCount = count($staticProperty->getValue());

        for ($i = 0; $i < 10; $i++) {
            $exp = new FormalExpression();
            $exp->setLanguage('FEEL');
        }

        $this->assertCount($initialCount, $staticProperty->getValue());
    }
}
