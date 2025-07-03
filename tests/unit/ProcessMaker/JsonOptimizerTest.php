<?php

namespace Tests;

use Illuminate\Support\Facades\Log;
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
     * Test that the helper function json_optimize_encode encodes data correctly.
     */
    public function test_helper_encodes_data_correctly()
    {
        config(['app.json_optimization_encode' => true]);

        $encoded = json_optimize_encode($this->data);

        $this->assertIsString($encoded, 'The encoded result should be a string.');
        $this->assertJson($encoded, 'The result should be a valid JSON string.');

        $decoded = json_decode($encoded, true);

        $this->assertEquals($this->data, $decoded, 'The decoded JSON should match the original array.');
    }

    /**
     * Test the performance of the json_encode function.
     */
    public function test_json_encode_performance()
    {
        $start = microtime(true);
        $json = json_encode($this->testData);
        $duration = microtime(true) - $start;

        $this->assertIsString($json);
        echo "\njson_encode duration: {$duration} sec";
    }

    /**
     * Test the performance of the json_optimize_encode function.
     */
    public function test_json_optimize_encode_performance()
    {
        $start = microtime(true);
        $json = json_optimize_encode($this->testData);
        $duration = microtime(true) - $start;

        $this->assertIsString($json);
        echo "\njson_optimize_encode duration: {$duration} sec";
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
        $json = json_encode($this->testData);

        $start = microtime(true);
        $data = json_optimize_decode($json, true);
        $duration = microtime(true) - $start;

        $this->assertIsArray($data);
        echo "\njson_optimize_decode duration: {$duration} sec";
    }
}
