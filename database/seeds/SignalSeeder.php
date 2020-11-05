<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\User;

class SignalSeeder extends Seeder
{

    static $TEMPLATE_PROCESS_FILE = 'GlobalSignals';
    static $TEMPLATE_PROCESS_NAME = 'global_signals';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($this->signalProcessExists()) { return; }

        $processCategory = ProcessCategory::where('is_system', true)->firstOrFail();
        $bpmn = file_get_contents(__DIR__ . '/../processes/GlobalSignals.bpmn');

        Process::unguard();
        Process::updateOrCreate([
            'name' => static::$TEMPLATE_PROCESS_NAME,
            'process_category_id' => $processCategory->id,
            'description' => 'Store of globally created signals',
            'bpmn' => $bpmn,
            'user_id' => User::first()->id,
        ]);
        Process::reguard();
    }

    /**
     * @return bool
     */
    private function signalProcessExists(): bool
    {
        return Process::where('name', static::$TEMPLATE_PROCESS_NAME)->get()->count() > 0;
    }
}
