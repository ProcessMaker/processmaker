<?php
/**
 * Test harness bootstrap that sets up initial defines and builds up the initial database schema
 */
// Bring in our standard bootstrap
include_once(__DIR__ . '/../bootstrap/autoload.php');
require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ScriptExecutor;
use Tests\DatabaseHelper;

// To view these logs you must set LOG_CHANNEL=stack env var
function testLog($txt) {
    $token = env('TEST_TOKEN') ?: 'BASE';
    if (class_exists(\Log::class)) {
        \Log::info("[testlog] [$token] $txt");
    }
}

// Bootstrap laravel
app()->make(Kernel::class)->bootstrap();

testLog('Bootstrap starting ' . join(" ", $_SERVER['argv']));


$databaseHelper = new DatabaseHelper();

// if TEST_TOKEN is present, we are in a parallel test process
if (env('TEST_TOKEN')) {
    $database = 'test_' . env('TEST_TOKEN');
    $_ENV['DB_DATABASE'] = $database;
    $_ENV['DATA_DB_DATABASE'] = $database;

} else {
    // Clear cache so we don't overwrite our local development database
    Artisan::call('config:clear', ['--env' => 'testing']);

    //Ensure storage directory is linked
    Artisan::call('storage:link', []);
}

// Do not run in parallel test processes since this already ran once at the beginning
if(env('POPULATE_DATABASE') && !env('TEST_TOKEN')) {
    Artisan::call('db:wipe', ['--database' => \DB::connection()->getName()]);
    testLog('db:wipe output: ' . Artisan::output());
    Artisan::call('migrate:fresh', []);
    testLog('migrate:fresh output: ' . Artisan::output());

    ScriptExecutor::firstOrCreate(
        ['language' => 'php'],
        ['title' => 'Test Executor']
    );
    ScriptExecutor::firstOrCreate(
        ['language' => 'lua'],
        ['title' => 'Test Executor']
    );

    if (env('PARALLEL_TEST_PROCESSES')) {
        testLog("Duplicating test databases");
        $databaseHelper->createTestDBs();
    }
}

testLog("Booststrap done");