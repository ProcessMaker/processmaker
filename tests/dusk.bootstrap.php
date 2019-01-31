<?php
/**
 * Test harness bootstrap that ensures empty sqlite database exists
 */ 
// Bring in our standard bootstrap
include_once(__DIR__ . '/../bootstrap/autoload.php');
require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

// Bootstrap laravel
app()->make(Kernel::class)->bootstrap();

Artisan::call('migrate:fresh', []);
