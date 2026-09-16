<?php

/**
 * Bootstrap for Core Service Task in-process PHP execution.
 *
 * Args: absolute path to work directory containing:
 *   - data.json
 *   - config.json
 *   - script.php
 *
 * Writes JSON {"output": ...} to stdout.
 */
$workDir = $argv[1] ?? null;
if (!$workDir || !is_dir($workDir)) {
    fwrite(STDERR, "Work directory missing or invalid.\n");
    exit(1);
}

$dataPath = $workDir . '/data.json';
$configPath = $workDir . '/config.json';
$scriptPath = $workDir . '/script.php';

$data = json_decode(@file_get_contents($dataPath), true);
if (!is_array($data)) {
    fwrite(STDERR, "Data JSON file does not exist or is invalid.\n");
    exit(100);
}

$config = json_decode(@file_get_contents($configPath), true);
if (!is_array($config)) {
    fwrite(STDERR, "Config JSON file does not exist or is invalid.\n");
    exit(101);
}

if (!file_exists($scriptPath)) {
    fwrite(STDERR, "Script file does not exist.\n");
    exit(102);
}

// Optional ProcessMaker PHP SDK (same contract as docker-executor-php).
$api = null;
if (getenv('API_TOKEN') && getenv('API_HOST') && class_exists('ProcessMaker\\Client\\Configuration')) {
    $apiConfig = new ProcessMaker\Client\Configuration();
    $apiConfig->setAccessToken(getenv('API_TOKEN'));
    $apiConfig->setHost(getenv('API_HOST'));
    if (class_exists('ProcessMaker\\Client\\Api\\ScriptsApi')) {
        // SDK clients vary by generated package; leave $api as configuration helper when full Executor\Api is absent.
        $api = $apiConfig;
    }
    if (class_exists('Executor\\Api')) {
        $api = new Executor\Api(
            $apiConfig,
            isset($_ENV['API_SSL_VERIFY']) ? (bool) $_ENV['API_SSL_VERIFY'] : true
        );
    }
}

$response = require $scriptPath;

echo json_encode(['output' => $response]);
