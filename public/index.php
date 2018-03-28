<?php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require_once __DIR__ . '/../bootstrap/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

$response->send();
$kernel->terminate($request, $response);
