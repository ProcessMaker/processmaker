<?php

namespace Tests;

use ProcessMaker\Support\JsonOptimizer;
use Tests\TestCase;

class JsonOptimizerTest extends TestCase
{
    protected string $json;

    protected array $data;

    protected array $testData = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'id' => 1,
            'name' => 'ProcessMaker',
            'features' => ['BPMN', 'LowCode', 'Workflow'],
            'meta' => [
                'version' => '4.0',
                'active' => true,
                'users' => 15000,
            ],
        ];

        $this->json = json_encode($this->data);

        //generate big data.
        $this->testData = [];

        for ($i = 0; $i < 1_000_000; $i++) {
            $this->testData[] = [
                'id' => $i,
                'name' => 'Usuario_' . $i,
                'email' => "user{$i}@example.com",
                'active' => $i % 2 === 0,
            ];
        }
    }

    /**
     * Test that the json_optimize_decode function decodes JSON correctly.
     */
    public function test_it_decodes_json_correctly_with_optimizer()
    {
        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertIsArray($decoded);
        $this->assertEquals($this->data['name'], $decoded['name']);
        $this->assertEquals($this->data['meta']['version'], $decoded['meta']['version']);
    }

    /**
     * Test that the json_optimize_decode function falls back to native decode on invalid JSON.
     */
    public function test_it_falls_back_to_native_decode_on_invalid_json()
    {
        $invalidJson = '{"name": "ProcessMaker", "invalid": }'; // malformed JSON

        $result = JsonOptimizer::decode($invalidJson, true);

        $this->assertNull($result);
    }

    /**
     * Test that the json_optimize_decode function uses simdjson when available.
     */
    public function test_it_uses_simdjson_when_available()
    {
        if (!extension_loaded('simdjson_plus')) {
            $this->markTestSkipped('SIMDJSON extension not loaded.');
        }

        config(['app.json_optimization_decode' => true]);

        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     * Test that the config flag json_optimization_decode is respected.
     */
    public function test_it_respects_config_flag_json_optimization()
    {
        config(['app.json_optimization_decode' => false]);

        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     * Test that the helper function json_optimize_decode decodes valid JSON correctly.
     */
    public function test_helper_function_decodes_valid_json_correctly()
    {
        $decoded = json_optimize_decode($this->json, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('ProcessMaker', $decoded['name']);
        $this->assertCount(3, $decoded['meta']);
    }

    /**
     * Test that the helper function json_optimize_decode returns null on invalid JSON.
     */
    public function test_helper_function_returns_null_on_invalid_json()
    {
        $invalidJson = '{"type": "workflow", "meta": ['; // malformed JSON

        $decoded = json_optimize_decode($invalidJson, true);

        $this->assertNull($decoded);
    }

    /**
     * Test that the helper function json_optimize_decode respects the config setting.
     */
    public function test_helper_respects_config_setting()
    {
        config(['app.json_optimization_decode' => false]);

        $decoded = json_optimize_decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     * Test that the helper function json_optimize_decode uses simdjson when enabled.
     */
    public function test_helper_uses_simdjson_when_enabled()
    {
        if (!extension_loaded('simdjson_plus')) {
            $this->markTestSkipped('SIMDJSON extension not available');
        }

        config(['app.json_optimization_decode' => true]);

        $decoded = json_optimize_decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     * Test that the helper function json_optimize_encode encodes data correctly.
     */
    public function test_it_encodes_data_correctly()
    {
        config(['app.json_optimization_encode' => true]);

        $encoded = JsonOptimizer::encode($this->data);

        $this->assertIsString($encoded);
        $this->assertJson($encoded);

        $decoded = json_decode($encoded, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     * Test the performance of the json_decode function.
     */
    public function test_json_decode_performance()
    {
        $json = json_encode($this->testData);

        $start = microtime(true);
        $data = json_decode($json, true);
        $duration = microtime(true) - $start;

        $this->assertIsArray($data);
        echo "\njson_decode duration: {$duration} sec";
    }

    /**
     * Test the performance of the json_optimize_decode function.
     */
    public function test_json_optimize_decode_performance()
    {
        if (!extension_loaded('simdjson_plus')) {
            $this->markTestSkipped('SIMDJSON extension not available');
        }

        $json = json_encode($this->testData);

        $start = microtime(true);
        $data = json_optimize_decode($json, true);
        $duration = microtime(true) - $start;

        $this->assertIsArray($data);
        echo "\njson_optimize_decode duration: {$duration} sec";
    }

    /**
     * Test JSON encoding and decoding with edge cases and special characters.
     */
    public function test_json_decode_edge_cases()
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

        // Test helper functions
        $encoded = json_encode($specialData);
        $this->assertIsString($encoded);
        $this->assertJson($encoded);

        if (extension_loaded('simdjson_plus')) {
            $decoded = json_optimize_decode($encoded, true);
        } else {
            $decoded = json_decode($encoded, true);
        }
        $this->assertIsArray($decoded);
        $this->assertEquals($specialData, $decoded);

        // Verify specific edge cases
        $this->assertEquals('Hello "World" with \'quotes\' and \n newlines', $decoded['message']);
        $this->assertEquals('Café & résumé', $decoded['unicode']);
        $this->assertEquals('😊🚀', $decoded['emojis']);
        $this->assertEquals('中文测试', $decoded['chino']);
        $this->assertEquals('日本語のテスト', $decoded['japones']);
        $this->assertNull($decoded['null_value']);
        $this->assertEquals([1, 2.5, -3, 0], $decoded['numbers']);
        $this->assertEquals([true, false], $decoded['booleans']);
    }

    /**
     * Test performance comparison between json_optimize_decode and json_decode using fixture data.
     */
    public function test_json_optimize_decode_performance_vs_native()
    {
        $jsonFixturePath = __DIR__ . '/../../Fixtures/json_optimizer_test_example.json';

        // Check if fixture exists
        if (!file_exists($jsonFixturePath)) {
            $this->markTestSkipped('JSON fixture file not found: ' . $jsonFixturePath);
        }

        // Load fixture data
        $fixtureData = json_decode(file_get_contents($jsonFixturePath), true);
        $this->assertIsArray($fixtureData, 'Fixture data should be an array');

        // Generate JSON using native json_encode
        $jsonString = json_encode($fixtureData);
        $this->assertIsString($jsonString);
        $this->assertJson($jsonString);

        $jsonSize = strlen($jsonString);
        echo "\n📊 JSON size: " . number_format($jsonSize) . ' bytes';

        // Test iterations for performance measurement
        $iterations = 100;

        // Test native json_decode performance
        $nativeStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $nativeDecoded = json_decode($jsonString, true);
        }
        $nativeTime = microtime(true) - $nativeStart;

        // Test json_optimize_decode performance
        $optimizedStart = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            if (extension_loaded('simdjson_plus')) {
                $optimizedDecoded = json_optimize_decode($jsonString, true);
            } else {
                $optimizedDecoded = json_decode($jsonString, true);
            }
        }
        $optimizedTime = microtime(true) - $optimizedStart;

        // Verify both decoders produce the same result
        $this->assertEquals($nativeDecoded, $optimizedDecoded, 'Both decoders should produce identical results');

        // Calculate performance metrics
        $nativeAvg = ($nativeTime / $iterations) * 1000; // Convert to milliseconds
        $optimizedAvg = ($optimizedTime / $iterations) * 1000; // Convert to milliseconds

        // Verify data integrity
        $this->assertIsArray($optimizedDecoded);
        $this->assertEquals($fixtureData, $optimizedDecoded, 'Decoded data should match original fixture data');
    }
}
