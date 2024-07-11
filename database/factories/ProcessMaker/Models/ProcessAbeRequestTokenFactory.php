<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;

/**
 * Model factory for a process request
 */
class ProcessAbeRequestTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $screen = Screen::factory()->create();
        $process = Process::factory()->create();
        $bpmn = file_get_contents(__DIR__ . '/../../Fixtures/rollback_test.bpmn');
        $bpmn = str_replace('[task_user_id]', $this->user->id, $bpmn);
        $processRequest = ProcessRequest::factory()->create([
            'process_id' => $process->getKey(),
            'bpmn' => $bpmn,
        ]);
        $processRequestToken = ProcessRequestToken::factory()->create([
            'process_id' => $process->getKey(),
            'process_request_id' => $processRequest->getKey()
        ]);

        return [
            'process_id' => $process->getKey(),
            'process_request_id' => $processRequest->getKey(),
            'process_request_token_id' => $processRequestToken->getKey(),
            'completed_screen_id' => $screen->getKey(),
            'is_answered' => 0,
            'require_login' => 0
        ];
    }
}
