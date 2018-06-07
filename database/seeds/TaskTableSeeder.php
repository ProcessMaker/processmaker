<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;

class TaskTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $process = factory(Process::class)->create([
            'creator_user_id' => User::where('username', 'admin')->first()->id
        ]);

        factory(Task::class, 10)->create([
            'process_id' => $process->id
        ]);
    }
}
