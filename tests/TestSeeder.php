<?php

namespace Tests;

use Database\Seeders\AnonymousUserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ScriptExecutor;

class TestSeeder extends Seeder
{
    /**
     * Seed the database once before all tests.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AnonymousUserSeeder::class,
        ]);

        ScriptExecutor::firstOrCreate(
            ['language' => 'php-nayra'],
            ['title' => 'Test Executor Nayra']
        );

        ScriptExecutor::firstOrCreate(
            ['language' => 'php'],
            ['title' => 'Test Executor Nayra']
        );
    }
}
