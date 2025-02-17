<?php

namespace Tests;

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
        dump('Running TestSeeder');
        $this->call([
            // UserSeeder::class,
        ]);

        ScriptExecutor::firstOrCreate(
            ['language' => 'php-nayra'],
            ['title' => 'Test Executor Nayra']
        );

        Base::initNayraPhpUnitTest();
    }
}
