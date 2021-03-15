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
        if ($this->processExists()) {
            return;
        }

        $admin = User::where('is_administrator', true)->firstOrFail();
        $processCategory = ProcessCategory::where('is_system', true)->firstOrFail();
        $bpmn = file_get_contents(__DIR__ . '/../processes/' . static::$TEMPLATE_PROCESS_FILE);

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

    /**
     * @return bool
     */
    private function processExists(): bool
    {
        return Process::where('name', Process::ASSIGNMENT_PROCESS)->get()->count() > 0;
    }
}
