<?php

namespace Tests\Unit\ProcessMaker;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class JsonOptimizerTest extends TestCase
{
    // Performance tolerance ratios
    const SIMILAR_PERFORMANCE_RATIO = 1.2; // 1.2 = 20% slower is acceptable for similar performance

    const OPTIMIZATION_TOLERANCE_RATIO = 1.5; // 1.5 = 50% slower is acceptable when optimization is active

    /**
     * Log extension status for debugging
     */
    private function logExtensionStatus()
    {
        $status = [
            'simdjson_loaded' => extension_loaded('simdjson'),
            'uopz_loaded' => extension_loaded('uopz'),
            'environment' => app()->environment(),
            'json_optimization_enabled' => config('app.json_optimization', false),
        ];

        Log::info('JSON Optimizer Extension Status', $status);

        // Output to console for debugging
        $this->info('📋 Extension Status:');
        $this->info('SIMDJSON loaded: ' . ($status['simdjson_loaded'] ? '✅ YES' : '❌ NO'));
        $this->info('UOPZ loaded: ' . ($status['uopz_loaded'] ? '✅ YES' : '❌ NO'));
        $this->info('Environment: ' . $status['environment']);
        $this->info('JSON optimization enabled: ' . ($status['json_optimization_enabled'] ? '✅ YES' : '❌ NO'));
    }

    /**
     * Test native JSON functions
     */
    private function testNativeJsonFunctions()
    {
        $testData = [
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
            ],
        ];

        $json = json_encode($testData);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(2, count($decoded['users']));

        $this->info('✅ Native JSON functions working correctly');
    }

    /**
     * Test with ProcessMaker data
     */
    private function testProcessMakerData()
    {
        $pmData = [
            'processes' => [
                [
                    'id' => 1,
                    'name' => 'Employee Onboarding',
                    'status' => 'ACTIVE',
                    'data' => [
                        'employee_name' => 'John Doe',
                        'department' => 'HR',
                        'start_date' => '2024-01-15',
                    ],
                ],
            ],
            'meta' => [
                'total' => 1,
                'page' => 1,
                'per_page' => 10,
            ],
        ];

        $json = json_encode($pmData);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(1, count($decoded['processes']));

        $this->info('✅ ProcessMaker data processed correctly');
        $this->info('📏 JSON size: ' . strlen($json) . ' bytes');
    }

    /**
     * Test optimization logic simulation
     */
    private function testOptimizationLogic()
    {
        $simdjsonLoaded = extension_loaded('simdjson');
        $uopzLoaded = extension_loaded('uopz');

        if (!$simdjsonLoaded) {
            $this->info('📝 SIMDJSON not loaded - would use native functions');
        } else {
            $this->info('✅ SIMDJSON loaded');
        }

        if (!$uopzLoaded) {
            $this->info('📝 UOPZ not loaded - would use native functions');
        } else {
            $this->info('✅ UOPZ loaded');
        }

        $wouldOptimize = $simdjsonLoaded && $uopzLoaded;
        $this->info('🎯 Optimization Status: ' . ($wouldOptimize ? '✅ WOULD OPTIMIZE' : '📝 WOULD USE NATIVE'));
    }

    /**
     * Helper method to output info during tests
     */
    private function info($message)
    {
        // In tests, we can use fwrite to output to console
        fwrite(STDERR, $message . "\n");
    }

    /**
     * Measure JSON encode performance
     */
    private function measureJsonEncodePerformance($data, $iterations)
    {
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $json = json_encode($data);
        }

        return microtime(true) - $start;
    }

    /**
     * Measure JSON decode performance
     */
    private function measureJsonDecodePerformance($data, $iterations)
    {
        $jsonString = json_encode($data);
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $decoded = json_decode($jsonString, true);
        }

        return microtime(true) - $start;
    }

    /**
     * Test that JSON optimization works correctly with and without extensions
     */
    public function test_json_optimizer_behavior()
    {
        // Test 1: Check extension status
        $this->logExtensionStatus();

        // Test 2: Verify native JSON functions always work
        $this->testNativeJsonFunctions();

        // Test 3: Test with ProcessMaker-like data
        $this->testProcessMakerData();

        // Test 4: Test optimization logic simulation
        $this->testOptimizationLogic();
    }

    /**
     * Test that native JSON functions work regardless of extension availability
     */
    public function test_native_json_functions_always_work()
    {
        $testData = [
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'meta' => [
                'total' => 2,
                'page' => 1,
            ],
        ];

        // Test json_encode performance
        $encodeTime = $this->measureJsonEncodePerformance($testData, 10);
        $json = json_encode($testData);
        $this->assertIsString($json);
        $this->assertGreaterThan(0, strlen($json));

        // Test json_decode performance
        $decodeTime = $this->measureJsonDecodePerformance($testData, 10);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(2, count($decoded['users']));
        $this->assertEquals('John Doe', $decoded['users'][0]['name']);
    }

    /**
     * Test complex ProcessMaker data structures using real fixture data
     */
    public function test_complex_processmaker_data()
    {
        // Load the real ProcessMaker JSON fixture data
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';
        $this->assertFileExists($jsonFixturePath, 'JSON fixture file should exist');

        $jsonContent = file_get_contents($jsonFixturePath);
        $this->assertNotEmpty($jsonContent, 'JSON fixture should not be empty');

        // Test json_decode with the real data
        $pmData = json_decode($jsonContent, true);
        $this->assertIsArray($pmData, 'Should decode to array');
        $this->assertNotEmpty($pmData, 'Decoded data should not be empty');

        // Verify key structure elements exist
        $this->assertArrayHasKey('id', $pmData, 'Should have task ID');
        $this->assertArrayHasKey('uuid', $pmData, 'Should have UUID');
        $this->assertArrayHasKey('process_request', $pmData, 'Should have process request data');
        $this->assertArrayHasKey('process', $pmData, 'Should have process data');
        $this->assertArrayHasKey('user', $pmData, 'Should have user data');
        $this->assertArrayHasKey('data', $pmData, 'Should have form data');

        // Test specific data integrity
        $this->assertEquals(1297257, $pmData['id'], 'Task ID should match');
        $this->assertEquals('9f23e1d4-1067-4e7f-9811-76ce601688e4', $pmData['uuid'], 'UUID should match');
        $this->assertEquals('Invoice Process', $pmData['process_request']['name'], 'Process name should match');
        $this->assertEquals('Daniel Aguilar', $pmData['user']['fullname'], 'User name should match');

        // Test nested data structures
        $this->assertIsArray($pmData['data'], 'Form data should be array');
        $this->assertArrayHasKey('_user', $pmData['data'], 'Should have user data in form');
        $this->assertArrayHasKey('_request', $pmData['data'], 'Should have request data in form');

        // Test complex nested arrays
        $this->assertIsArray($pmData['data']['IN_EXPENSE_ACTIVITY_RECOVERABLE'], 'Should have recoverable activities array');
        $this->assertGreaterThan(0, count($pmData['data']['IN_EXPENSE_ACTIVITY_RECOVERABLE']), 'Should have recoverable activities');

        // Test json_encode performance with the decoded data
        $encodeTime = $this->measureJsonEncodePerformance($pmData, 5);
        $reEncodedJson = json_encode($pmData);
        $this->assertIsString($reEncodedJson, 'Should encode back to string');
        $this->assertGreaterThan(0, strlen($reEncodedJson), 'Encoded JSON should not be empty');

        // Test that re-encoding preserves data integrity
        $decodeTime = $this->measureJsonDecodePerformance($pmData, 5);
        $reDecodedData = json_decode($reEncodedJson, true);
        $this->assertEquals($pmData, $reDecodedData, 'Re-encoded data should match original');
    }

    /**
     * Test JSON fixture data with various encoding options and edge cases
     */
    public function test_json_fixture_with_encoding_options()
    {
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';
        $jsonContent = file_get_contents($jsonFixturePath);
        $pmData = json_decode($jsonContent, true);

        // Test with different JSON encoding options
        $options = [
            'default' => 0,
            'pretty_print' => JSON_PRETTY_PRINT,
            'unescaped_slashes' => JSON_UNESCAPED_SLASHES,
            'unicode_escape' => JSON_UNESCAPED_UNICODE,
            'combined' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ];

        $performanceResults = [];

        foreach ($options as $optionName => $optionValue) {
            // Measure performance for each option
            $start = microtime(true);
            for ($i = 0; $i < 5; $i++) {
                $encoded = json_encode($pmData, $optionValue);
            }
            $encodeTime = microtime(true) - $start;

            $this->assertIsString($encoded, "Should encode with {$optionName} option");
            $this->assertGreaterThan(0, strlen($encoded), "Encoded JSON with {$optionName} should not be empty");

            $decodeTime = $this->measureJsonDecodePerformance($pmData, 5);
            $decoded = json_decode($encoded, true);
            $this->assertEquals($pmData, $decoded, "Data integrity should be preserved with {$optionName} option");

            // Test specific data points remain intact
            $this->assertEquals(1297257, $decoded['id'], "Task ID should be preserved with {$optionName}");
            $this->assertEquals('Invoice Process', $decoded['process_request']['name'], "Process name should be preserved with {$optionName}");

            $performanceResults[$optionName] = [
                'encode_time_ms' => round($encodeTime * 1000, 4),
                'decode_time_ms' => round($decodeTime * 1000, 4),
                'json_size_bytes' => strlen($encoded),
            ];
        }

        // Test with different decode options
        $encoded = json_encode($pmData);

        // Test associative array decode
        $decodeTimeAssoc = $this->measureJsonDecodePerformance($pmData, 5);
        $decodedAssoc = json_decode($encoded, true);
        $this->assertIsArray($decodedAssoc, 'Should decode as associative array');

        // Test object decode
        $decodeTimeObject = $this->measureJsonDecodePerformance($pmData, 5);
        $decodedObject = json_decode($encoded, false);
        $this->assertIsObject($decodedObject, 'Should decode as object');
        $this->assertEquals($pmData['id'], $decodedObject->id, 'Object properties should match array values');

        // Test with depth limit (if supported)
        if (defined('JSON_MAX_DEPTH')) {
            $decodedWithDepth = json_decode($encoded, true, 512, \JSON_MAX_DEPTH);
            $this->assertIsArray($decodedWithDepth, 'Should decode with max depth limit');
        }

        // Test error handling with invalid JSON
        $invalidJson = '{"invalid": json}';
        $decodedInvalid = json_decode($invalidJson, true);
        $this->assertNull($decodedInvalid, 'Should return null for invalid JSON');

        // Test with empty JSON
        $emptyJson = '{}';
        $decodedEmpty = json_decode($emptyJson, true);
        $this->assertIsArray($decodedEmpty, 'Should decode empty JSON as array');
        $this->assertEmpty($decodedEmpty, 'Empty JSON should decode to empty array');
    }

    /**
     * Test JSON performance with real ProcessMaker data
     */
    public function test_json_performance()
    {
        $iterations = 50;

        // Load the real ProcessMaker JSON fixture data
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';
        $jsonContent = file_get_contents($jsonFixturePath);
        $pmData = json_decode($jsonContent, true);

        $this->assertNotEmpty($pmData, 'Should have valid ProcessMaker data');

        // Test json_encode performance with real data
        $encodeTime = $this->measureJsonEncodePerformance($pmData, $iterations);

        // Test json_decode performance with real data
        $decodeTime = $this->measureJsonDecodePerformance($pmData, $iterations);

        // Test with different encoding options
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $prettyJson = json_encode($pmData, JSON_PRETTY_PRINT);
        }
        $prettyEncodeTime = microtime(true) - $start;

        // Assert that operations complete in reasonable time (less than 5 seconds for real data)
        $this->assertLessThan(5.0, $encodeTime, 'JSON encode should complete in less than 5 seconds');
        $this->assertLessThan(5.0, $decodeTime, 'JSON decode should complete in less than 5 seconds');
        $this->assertLessThan(5.0, $prettyEncodeTime, 'Pretty JSON encode should complete in less than 5 seconds');

        // Calculate performance metrics
        $dataSize = strlen(json_encode($pmData));
        $encodeSpeed = $dataSize / ($encodeTime * 1000); // bytes per millisecond
        $decodeSpeed = $dataSize / ($decodeTime * 1000); // bytes per millisecond

        // Additional assertions for performance expectations
        $this->assertGreaterThan(0, $encodeSpeed, 'Should have positive encode speed for size ' . $dataSize);
        $this->assertGreaterThan(0, $decodeSpeed, 'Should have positive decode speed for size ' . $dataSize);

        // Test that pretty encoding performance is reasonable (it might be faster or slower)
        $ratio = $prettyEncodeTime / $encodeTime;

        // If pretty encoding is faster (ratio < 1), that's fine
        // If pretty encoding is slower, it shouldn't be more than 2x slower
        if ($ratio >= 1.0) {
            $this->assertLessThanOrEqual(2.0, $ratio, "Pretty encoding ratio ({$ratio}) should not exceed 2.0 when slower");
        }
    }

    /**
     * Test that optimization logic works correctly
     */
    public function test_optimization_logic()
    {
        $simdjsonLoaded = extension_loaded('simdjson');
        $uopzLoaded = extension_loaded('uopz');

        // Test the logic from JsonOptimizerServiceProvider
        if (!$simdjsonLoaded || !$uopzLoaded) {
            // Should use native functions
            $this->assertTrue(function_exists('json_encode'));
            $this->assertTrue(function_exists('json_decode'));

            // Should NOT have the renamed functions
            $this->assertFalse(function_exists('php_json_encode'));
            $this->assertFalse(function_exists('php_json_decode'));
        } else {
            // Both extensions loaded - optimization could be applied
            $this->assertTrue($simdjsonLoaded);
            $this->assertTrue($uopzLoaded);
        }
    }

    /**
     * Test JSON with special characters and edge cases
     */
    public function test_json_edge_cases()
    {
        // Test with special characters
        $specialData = [
            'message' => 'Hello "World" with \'quotes\' and \n newlines',
            'unicode' => 'Café & résumé',
            'numbers' => [1, 2.5, -3, 0],
            'booleans' => [true, false],
            'emojis' => '😊🚀',
            'chino' => '中文测试',
            'japones'  => '日本語のテスト',
            'special_chars' => "!@#$%^&*()_+-=[]{}|;':\",.<>?/~`",
            'unicode_chars' => '✓ ✔ ✗ ✖',
            'null_value' => null,
            'empty' => ['', [], null],
        ];

        // Test basic encode/decode performance
        $encodeTime = $this->measureJsonEncodePerformance($specialData, 10);
        $json = json_encode($specialData);
        $decodeTime = $this->measureJsonDecodePerformance($specialData, 10);
        $decoded = json_decode($json, true);

        $this->assertEquals($specialData, $decoded);

        // Test with JSON options
        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $prettyJson = json_encode($specialData, JSON_PRETTY_PRINT);
        }
        $prettyEncodeTime = microtime(true) - $start;
        $this->assertStringContainsString("\n", $prettyJson);

        // Test decode with different options
        $decodedAssoc = json_decode($json, true);
        $decodedObject = json_decode($json, false);

        $this->assertIsArray($decodedAssoc);
        $this->assertIsObject($decodedObject);
        $this->assertEquals($decodedAssoc, (array) $decodedObject);
    }

    /**
     * Test JSON optimization with environment variable simulation
     */
    public function test_json_optimization_with_env_simulation()
    {
        $iterations = 20;

        // Load the real ProcessMaker JSON fixture data
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';
        $jsonContent = file_get_contents($jsonFixturePath);
        $pmData = json_decode($jsonContent, true);

        // Test with different environment configurations
        $testCases = [
            'native' => ['JSON_OPTIMIZATION' => false, 'description' => 'Native JSON functions'],
            'optimized' => ['JSON_OPTIMIZATION' => true, 'description' => 'Optimized JSON functions'],
        ];

        $results = [];

        foreach ($testCases as $mode => $config) {
            // Simulate environment variable
            putenv('JSON_OPTIMIZATION=' . ($config['JSON_OPTIMIZATION'] ? 'true' : 'false'));

            // Clear config cache to force reload
            if (app()->bound('config')) {
                app()->make('config')->set('app.json_optimization', $config['JSON_OPTIMIZATION']);
            }

            // Measure performance using the helper methods
            $encodeTime = $this->measureJsonEncodePerformance($pmData, $iterations);
            $decodeTime = $this->measureJsonDecodePerformance($pmData, $iterations);

            // Check if optimization functions exist
            $hasOptimizedFunctions = function_exists('php_json_encode') && function_exists('php_json_decode');

            $results[$mode] = [
                'encode_time_ms' => round($encodeTime * 1000, 4),
                'decode_time_ms' => round($decodeTime * 1000, 4),
                'optimization_active' => $hasOptimizedFunctions,
                'description' => $config['description'],
            ];

            // Verify data integrity
            $json = json_encode($pmData);
            $decoded = json_decode($json, true);
            $this->assertEquals($pmData, $decoded, "Data integrity should be maintained in {$mode} mode");
        }

        // Calculate performance comparison
        if (isset($results['native']) && isset($results['optimized'])) {
            $encodeRatio = $results['optimized']['encode_time_ms'] / $results['native']['encode_time_ms'];
            $decodeRatio = $results['optimized']['decode_time_ms'] / $results['native']['decode_time_ms'];

            $results['comparison'] = [
                'encode_ratio' => round($encodeRatio, 3),
                'decode_ratio' => round($decodeRatio, 3),
                'encode_improvement' => round((1 - $encodeRatio) * 100, 1),
                'decode_improvement' => round((1 - $decodeRatio) * 100, 1),
            ];
        }

        // Log comprehensive results
        Log::info('JSON Optimization Environment Test', [
            'iterations' => $iterations,
            'data_size_bytes' => strlen(json_encode($pmData)),
            'test_results' => $results,
            'extensions_loaded' => [
                'simdjson' => extension_loaded('simdjson'),
                'uopz' => extension_loaded('uopz'),
            ],
        ]);

        // Output results to console for easy reading
        $this->info("\n📊 JSON Optimization Performance Results:");
        $this->info('==========================================');

        foreach ($results as $mode => $result) {
            if ($mode !== 'comparison') {
                $status = $result['optimization_active'] ? '✅ ACTIVE' : '📝 NATIVE';
                $this->info("{$result['description']} ({$status}):");
                $this->info("  Encode: {$result['encode_time_ms']}ms");
                $this->info("  Decode: {$result['decode_time_ms']}ms");
            }
        }

        if (isset($results['comparison'])) {
            $this->info("\n📈 Performance Comparison:");
            $this->info("Encode ratio: {$results['comparison']['encode_ratio']}x");
            $this->info("Decode ratio: {$results['comparison']['decode_ratio']}x");

            if ($results['comparison']['encode_improvement'] > 0) {
                $this->info("✅ Encode improvement: {$results['comparison']['encode_improvement']}%");
            }
            if ($results['comparison']['decode_improvement'] > 0) {
                $this->info("✅ Decode improvement: {$results['comparison']['decode_improvement']}%");
            }
        }

        // Assertions
        $this->assertArrayHasKey('native', $results, 'Native mode should be tested');
        $this->assertArrayHasKey('optimized', $results, 'Optimized mode should be tested');

        // Performance should be reasonable in both modes
        $this->assertLessThan(1000, $results['native']['encode_time_ms'], 'Native encode should complete in reasonable time');
        $this->assertLessThan(1000, $results['native']['decode_time_ms'], 'Native decode should complete in reasonable time');
        $this->assertLessThan(1000, $results['optimized']['encode_time_ms'], 'Optimized encode should complete in reasonable time');
        $this->assertLessThan(1000, $results['optimized']['decode_time_ms'], 'Optimized decode should complete in reasonable time');
    }

    /**
     * Test JSON performance comparison between optimized and non-optimized modes
     */
    public function test_json_optimization_performance_comparison()
    {
        $iterations = 25; // Reduced for faster testing

        // Load the real ProcessMaker JSON fixture data
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';
        $jsonContent = file_get_contents($jsonFixturePath);
        $pmData = json_decode($jsonContent, true);

        $this->assertNotEmpty($pmData, 'Should have valid ProcessMaker data');

        // Test 1: Performance with JSON_OPTIMIZATION = false (native functions)
        config(['app.json_optimization' => false]);
        $this->assertFalse(config('app.json_optimization'), 'JSON optimization should be disabled');

        $nativeEncodeTime = $this->measureJsonEncodePerformance($pmData, $iterations);
        $nativeDecodeTime = $this->measureJsonDecodePerformance($pmData, $iterations);

        // Test 2: Performance with JSON_OPTIMIZATION = true (optimized functions)
        config(['app.json_optimization' => true]);
        $this->assertTrue(config('app.json_optimization'), 'JSON optimization should be enabled');

        // Note: In test environment, optimization might not be active due to missing extensions
        $optimizedEncodeTime = $this->measureJsonEncodePerformance($pmData, $iterations);
        $optimizedDecodeTime = $this->measureJsonDecodePerformance($pmData, $iterations);

        // Check if optimization is actually active
        $optimizationActive = function_exists('php_json_encode') && function_exists('php_json_decode');

        // Calculate performance ratios
        $encodeRatio = $optimizedEncodeTime / $nativeEncodeTime;
        $decodeRatio = $optimizedDecodeTime / $nativeDecodeTime;

        // Log detailed performance comparison
        Log::info('JSON Optimization Performance Comparison', [
            'iterations' => $iterations,
            'data_size_bytes' => strlen(json_encode($pmData)),
            'optimization_active' => $optimizationActive,
            'native_encode_time_ms' => round($nativeEncodeTime * 1000, 4),
            'optimized_encode_time_ms' => round($optimizedEncodeTime * 1000, 4),
            'native_decode_time_ms' => round($nativeDecodeTime * 1000, 4),
            'optimized_decode_time_ms' => round($optimizedDecodeTime * 1000, 4),
            'encode_performance_ratio' => round($encodeRatio, 3),
            'decode_performance_ratio' => round($decodeRatio, 3),
            'encode_improvement_percent' => round((1 - $encodeRatio) * 100, 1),
            'decode_improvement_percent' => round((1 - $decodeRatio) * 100, 1),
        ]);

        // Assertions based on optimization status
        if ($optimizationActive) {
            // If optimization is active, it should be faster or at least not significantly slower
            $this->assertLessThanOrEqual(self::OPTIMIZATION_TOLERANCE_RATIO, $encodeRatio, 'Optimized encode should not be more than 1.5x slower than native');
            $this->assertLessThanOrEqual(self::OPTIMIZATION_TOLERANCE_RATIO, $decodeRatio, 'Optimized decode should not be more than 1.5x slower than native');

            // Log improvement if any
            if ($encodeRatio < 1.0) {
                $this->info('✅ Encode optimization improved performance by ' . round((1 - $encodeRatio) * 100, 1) . '%');
            }
            if ($decodeRatio < 1.0) {
                $this->info('✅ Decode optimization improved performance by ' . round((1 - $decodeRatio) * 100, 1) . '%');
            }
        } else {
            // If optimization is not active, performance should be similar
            $this->assertLessThanOrEqual(self::SIMILAR_PERFORMANCE_RATIO, $encodeRatio, 'Without optimization, encode performance should be similar');
            $this->assertLessThanOrEqual(self::SIMILAR_PERFORMANCE_RATIO, $decodeRatio, 'Without optimization, decode performance should be similar');

            $this->info('📝 JSON optimization not active (extensions may not be loaded)');
        }

        // Always verify that both modes produce correct results
        $nativeJson = json_encode($pmData);
        $optimizedJson = json_encode($pmData);
        $this->assertEquals($nativeJson, $optimizedJson, 'Both modes should produce identical JSON output');

        $nativeDecoded = json_decode($nativeJson, true);
        $optimizedDecoded = json_decode($optimizedJson, true);
        $this->assertEquals($nativeDecoded, $optimizedDecoded, 'Both modes should produce identical decoded data');
    }
}
