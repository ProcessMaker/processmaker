# syntax=docker/dockerfile:1
# Realtime PHP executor: fresh container per request via `docker run --rm -i`.
#
# The service pipes one JSON payload on stdin. bootstrap.php evals the script,
# prints the JSON result on stdout, and exits. The container is removed on exit.

FROM php:8.3-cli-alpine

COPY <<'EOF' /bootstrap.php
<?php
/**
 * Read one JSON payload from stdin, eval the script, print result, exit.
 *
 * Expected payload shape:
 *   { "script": "...", "data": {}, "config": {}, "env": {} }
 */

$raw = file_get_contents('php://stdin');
if ($raw === false || trim($raw) === '') {
    fwrite(STDERR, "No input on stdin\n");
    exit(1);
}

$payload = json_decode($raw, true);
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

foreach (($payload['env'] ?? []) as $key => $value) {
    $stringValue = is_scalar($value) || $value === null ? (string) $value : json_encode($value);
    putenv($key . '=' . $stringValue);
    $_ENV[$key] = $stringValue;
    $_SERVER[$key] = $stringValue;
}

// eval() does not accept opening tags.
$script = preg_replace('/^<\?php\s*/i', '', trim($script));
// ProcessMaker encodes ' as &#39; (classic executor shell quoting). Restore
// before eval so `return ['x' => ...]` is not parsed as `return [&` + comment.
$script = str_replace('&#39;', "'", $script);

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
