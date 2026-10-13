<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Health\CaddyPhpIni;
use ProcessMaker\Health\CliPhpIni;

Route::get('/health/live', function () {
    if (request()->expectsJson() || request()->query('format') === 'json') {
        return response()->json([
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'php' => CaddyPhpIni::fromWorker(),
            'extensions' => CliPhpIni::extensionsLoaded(),
            'caddyfile' => CaddyPhpIni::caddyfilePath(),
        ]);
    }

    return response('ok', 200, ['Content-Type' => 'text/plain']);
})->name('health.live');
