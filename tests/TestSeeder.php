<?php

namespace Tests;

use Database\Seeders\AnonymousUserSeeder;
use Illuminate\Database\Seeder;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\ScriptRunners\Base;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
    }
}
