<?php
/**
 * Test harness bootstrap that sets up initial defines and builds up the initial database schema
 */
// Bring in our standard bootstrap
include_once(__DIR__ . '/../bootstrap/autoload.php');
require_once __DIR__ . '/../bootstrap/app.php';
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
// Bootstrap laravel
app()->make(Kernel::class)->bootstrap();
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
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
]]);
// First create the test external mysql database as well as our test database
DB::connection('testexternal')->unprepared('CREATE DATABASE IF NOT EXISTS testexternal');
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
    'value' => 'testvalue'
]);
// Only do if we are supporting MSSql tests
if (env('RUN_MSSQL_TESTS')) {
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
        'value' => 'testvalue'
    ]);
}
// THIS IS FOR STANDARD PROCESSMAKER TABLES
if (env('POPULATE_DATABASE')) {
    Artisan::call('migrate:fresh', ['--seed' => true]);
}
