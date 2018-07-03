<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Model\Process;

class ProcessSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (glob(database_path('processes') . '/*.bpmn') as $filename) {
            $process = factory(Process::class)->create([
                'bpmn' => file_get_contents($filename),
            ]);
            echo 'Process created: ', $process->uid, "\n";
        }
    }
}
