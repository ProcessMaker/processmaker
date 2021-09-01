<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

class AssignmentProcess extends Seeder
{
    public static $TEMPLATE_PROCESS_FILE = 'AssignmentProcess.bpmn';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        if ($this->processExists()) {
//            return;
//        }

        $admin = User::where('is_administrator', true)->firstOrFail();
        $processCategory = ProcessCategory::where('is_system', true)->firstOrFail();
        $bpmn = file_get_contents(__DIR__ . '/../processes/' . static::$TEMPLATE_PROCESS_FILE);

        $process = $this->getAssignmentProcess();

        // If the process does not exist
        if (empty($process)) {
            Process::unguard();
            Process::updateOrCreate([
                'name' => Process::ASSIGNMENT_PROCESS,
                'process_category_id' => $processCategory->id,
                'description' => 'Assignment Process',
                'bpmn' => $bpmn,
                'user_id' => $admin->id,
            ]);
            Process::reguard();
        }

        //if the process exists and the bpmn has changed, update it
        if ($process && strcmp($process->bpmn , $bpmn) !== 0) {
            $process->bpmn = $bpmn;
            $process->saveOrFail();
        }
    }

    private function getAssignmentProcess()
    {
        return Process::where('name', Process::ASSIGNMENT_PROCESS)->first();
    }
}
