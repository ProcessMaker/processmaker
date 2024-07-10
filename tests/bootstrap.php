<?php
/**
 * Test harness bootstrap that sets up initial defines and builds up the initial database schema
 */
// Bring in our standard bootstrap
include_once __DIR__ . '/../bootstrap/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;

// Bootstrap laravel
app()->make(Kernel::class)->bootstrap();

// Cache with new config so we don't overwrite our local development database
Artisan::call('config:cache', ['--env' => 'testing']);

// Ensure storage directory is linked
Artisan::call('storage:link', []);

if (env('RUN_MSSQL_TESTS')) {
    // Setup our testexternal database
    config(['database.connections.testexternal' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        // We set database to null to ensure we can create the testexternal database
        'database' => null,
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]]);

    // First create the test external mysql database as well as our test database
    DB::connection('testexternal')->unprepared('CREATE DATABASE IF NOT EXISTS testexternal');

    // First create the test external mysql database as well as our test database
    DB::connection('testexternal')->unprepared('CREATE DATABASE IF NOT EXISTS userexternal');

    // Now set the database name properly
    config(['database.connections.testexternal.database' => env('DB_TESTEXTERNAL_DB', 'testexternal')]);
    DB::connection('testexternal')->reconnect();

    // Now, drop all test tables and repopulate with schema
    Schema::connection('testexternal')->dropIfExists('test');

    Schema::connection('testexternal')->create('test', function ($table) {
        $table->increments('id');
        $table->string('value');
    });
    DB::connection('testexternal')->table('test')->insert([
        'value' => 'testvalue',
    ]);

    // Only do if we are supporting MSSql tests

    config(['database.connections.mssql' => [
        'driver' => 'sqlsrv',
        'host' => env('MSSQL_HOST', '127.0.0.1'),
        'database' => null,
        'username' => env('MSSQL_USERNAME', 'root'),
        'password' => env('MSSQL_PASSWORD', ''),
    ]]);

    $mssqlDBName = env('MSSQL_DATABASE', 'testexternal');

    // First create the test external mysql database as well as our test database
    DB::connection('mssql')->unprepared("if db_id('" . $mssqlDBName . "') is null\nCREATE DATABASE " . $mssqlDBName);

    // Now set the database name properly
    config(['database.connections.mssql.database' => $mssqlDBName]);

    DB::connection('mssql')->reconnect();

    Schema::connection('mssql')->dropIfExists('test');
    Schema::connection('mssql')->create('test', function ($table) {
        $table->increments('id');
        $table->string('value');
    });
    DB::connection('mssql')->table('test')->insert([
        'value' => 'testvalue',
    ]);
}

// setup parallel test databases
if (env('TEST_TOKEN')) {
    $database = 'test_' . env('TEST_TOKEN');
    $_ENV['DB_DATABASE'] = $database;
    $_ENV['DATA_DB_DATABASE'] = $database;
} elseif (env('POPULATE_DATABASE')) {
    Artisan::call('db:wipe', ['--database' => \DB::connection()->getName()]);
    Artisan::call('migrate:fresh', []);
    Artisan::call('db:seed', ['--class' => 'AnonymousUserSeeder']);

    \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = true;

    ScriptExecutor::firstOrCreate(
        ['language' => 'php'],
        ['title' => 'Test Executor']
    );
    ScriptExecutor::firstOrCreate(
        ['language' => 'php-nayra'],
        ['title' => 'Test Executor Nayra']
    );
    ScriptExecutor::firstOrCreate(
        ['language' => 'lua'],
        ['title' => 'Test Executor']
    );
    Base::initNayraPhpUnitTest();

    if (env('PARALLEL_TEST_PROCESSES')) {
        Artisan::call('processmaker:create-test-dbs');
    }
}
