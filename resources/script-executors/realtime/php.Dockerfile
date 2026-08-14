# syntax=docker/dockerfile:1
# Experiment: warm PHP container blocked on stdin
#
# Instead of starting a fresh container per request (cold start), we keep a
# container running with PHP already loaded and blocked on fgets(STDIN). A
# separate client attaches and writes one JSON payload; bootstrap.php evals
# the script, prints the result on stdout, and exits so the container stops.
# Eventually a pool of these warm containers could sit ready for fast attach.
#
# Usage:
#   ./warm.sh          # build + start warm container (foreground via docker wait)
#   ./run.sh           # attach, send payload.json, print result

FROM php:8.3-cli-alpine

COPY <<'EOF' /bootstrap.php
<?php
/**
 * Wait for one JSON payload on stdin, eval the script, print result, exit.
 *
 * Expected payload shape (see ../../payload.schema.json):
 *   { "script": "...", "data": {}, "config": {}, "env": {} }
 */

stream_set_blocking(STDIN, true);

$line = fgets(STDIN);
if ($line === false) {
    fwrite(STDERR, "No input on stdin\n");
    exit(1);
}

$payload = json_decode(trim($line), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, json_encode([
        'error' => 'Invalid JSON: ' . json_last_error_msg(),
        'code' => 0,
        'file' => '',
        'line' => 0,
        'trace' => '',
    ], JSON_UNESCAPED_UNICODE) . "\n");
    fflush(STDERR);
    exit(1);
}

$script = $payload['script'] ?? null;
if ($script === null || $script === '') {
    fwrite(STDERR, json_encode([
        'error' => 'Missing "script" field',
        'code' => 0,
        'file' => '',
        'line' => 0,
        'trace' => '',
    ], JSON_UNESCAPED_UNICODE) . "\n");
    fflush(STDERR);
    exit(1);
}

// Available to the evaluated script (matches executor-php convention).
$data = $payload['data'] ?? [];
$config = $payload['config'] ?? [];

// Apply per-request env before eval (warm containers can't use docker run -e).
foreach (($payload['env'] ?? []) as $key => $value) {
    $stringValue = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
    putenv($key . '=' . $stringValue);
    $_ENV[$key] = $stringValue;
    $_SERVER[$key] = $stringValue;
}

// eval() does not accept opening tags.
$script = preg_replace('/^<\?php\s*/i', '', trim($script));

try {
    $result = eval($script);
    fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
    fflush(STDOUT);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ], JSON_UNESCAPED_UNICODE) . "\n");
    fflush(STDERR);
    exit(1);
}
EOF

CMD ["php", "/bootstrap.php"]
