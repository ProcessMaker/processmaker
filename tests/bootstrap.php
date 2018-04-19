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
    // Now, drop all test tables and repopulate with schema
    DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
    $colname = 'Tables_in_' . env('DB_DATABASE');
    $tables = DB::select('SHOW TABLES');
    $drop = [];
    foreach ($tables as $table) {
        $drop[] = $table->$colname;
    }
    if (count($drop)) {
        $drop = implode(',', $drop);
        DB::statement("DROP TABLE $drop");
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');
    }

    // Repopulate with schema and standard inserts
    DB::unprepared(file_get_contents('database/sql/workflow/schema.sql'));
    DB::unprepared(file_get_contents('database/sql/rbac/schema.sql'));
    DB::unprepared(file_get_contents('database/sql/workflow/insert.sql'));
    DB::unprepared(file_get_contents('database/sql/rbac/insert.sql'));

    // Set our APP_SEQUENCE val
    DB::table('APP_SEQUENCE')->insert([
        'ID' => 1
    ]);

    // Setup our initial oauth client for our web designer
    DB::table('OAUTH_CLIENTS')->insert([
        'CLIENT_ID' => 'x-pm-local-client',
        'CLIENT_SECRET' => '179ad45c6ce2cb97cf1029e212046e81',
        'CLIENT_NAME' => 'PM Web Designer',
        'CLIENT_DESCRIPTION' => 'ProcessMaker Web Designer App',
        'CLIENT_WEBSITE' => 'www.processmaker.com',
        'REDIRECT_URI' => config('app.url') . '/sys' . config('system.workspace').'/en/neoclassic/oauth2/grant',
        'USR_UID' => '00000000000000000000000000000001'
    ]);
    DB::table('OAUTH_ACCESS_TOKENS')->insert([
        'ACCESS_TOKEN' => '39704d17049f5aef45e884e7b769989269502f83',
        'CLIENT_ID' => 'x-pm-local-client',
        'USER_ID' => '00000000000000000000000000000001',
        'EXPIRES' => '2017-06-15 17:55:19',
        'SCOPE' => 'view_processes edit_processes *'
    ]);
    Artisan::call('migrate');
}

