<?php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

$GLOBALS['david_t0'] = microtime(true);

require_once __DIR__ . '/../bootstrap/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

error_log('load kernel ' . (microtime(true) - $GLOBALS['david_t0']));

$response = $kernel->handle(
    $request = Request::capture()
);

error_log('handle request ' . (microtime(true) - $GLOBALS['david_t0']));

$response->send();
$kernel->terminate($request, $response);
