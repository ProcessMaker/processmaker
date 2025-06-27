<?php

namespace Tests;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use ProcessMaker\Support\JsonOptimizer;

class JsonOptimizerTest extends TestCase
{
    protected string $json;
    protected array $data;

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
            ]
        ];

        $this->json = json_encode($this->data);
    }

    /**
     */
    public function test_it_decodes_json_correctly_with_optimizer()
    {
        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertIsArray($decoded);
        $this->assertEquals($this->data['name'], $decoded['name']);
        $this->assertEquals($this->data['meta']['version'], $decoded['meta']['version']);
    }

    /**
     */
    public function test_it_falls_back_to_native_decode_on_invalid_json()
    {
        $invalidJson = '{"name": "ProcessMaker", "invalid": }'; // malformed JSON

        $result = JsonOptimizer::decode($invalidJson, true);

        $this->assertNull($result);
    }

    /**
     */
    public function test_it_uses_simdjson_when_available()
    {
        if (!extension_loaded('simdjson')) {
            $this->markTestSkipped('SIMDJSON extension not loaded.');
        }

        config(['app.json_optimization' => true]);

        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     */
    public function test_it_respects_config_flag_json_optimization()
    {
        config(['app.json_optimization' => false]);

        $decoded = JsonOptimizer::decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }
    /**
     */
    public function test_helper_function_decodes_valid_json_correctly()
    {
        $decoded = json_optimize_decode($this->json, true);

        $this->assertIsArray($decoded);
        $this->assertEquals('ProcessMaker', $decoded['name']);
        $this->assertCount(3, $decoded['meta']);
    }

    /**
     */
    public function test_helper_function_returns_null_on_invalid_json()
    {
        $invalidJson = '{"type": "workflow", "meta": ['; // malformed JSON

        $decoded = json_optimize_decode($invalidJson, true);

        $this->assertNull($decoded);
    }

    /**
     */
    public function test_helper_respects_config_setting()
    {
        config(['app.json_optimization' => false]);

        $decoded = json_optimize_decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     */
    public function test_helper_uses_simdjson_when_enabled()
    {
        if (!extension_loaded('simdjson')) {
            $this->markTestSkipped('SIMDJSON extension not available');
        }

        config(['app.json_optimization' => true]);

        $decoded = json_optimize_decode($this->json, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     */
    public function test_it_encodes_data_correctly()
    {
        $encoded = JsonOptimizer::encode($this->data);

        $this->assertIsString($encoded);
        $this->assertJson($encoded);

        $decoded = json_decode($encoded, true);

        $this->assertEquals($this->data, $decoded);
    }

    /**
     */
    public function test_helper_encodes_data_correctly()
    {
        $encoded = json_optimize_encode($this->data);

        $this->assertIsString($encoded, 'The encoded result should be a string.');
        $this->assertJson($encoded, 'The result should be a valid JSON string.');

        $decoded = json_decode($encoded, true);

        $this->assertEquals($this->data, $decoded, 'The decoded JSON should match the original array.');
    }
}