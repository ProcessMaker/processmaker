<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;

/**
 * Model factory for a process request
 */
class ProcessRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $process = Process::factory()->make();

        return [
            'name' => $this->faker->sentence(3),
            'data' => [],
            'status' => 'ACTIVE',
            'callable_id' => function () use ($process) {
                $process->save();
                $bpmnProcess = $process->getDefinitions()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process')->item(0);

                return $bpmnProcess->getAttribute('id');
            },
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'process_id' => function () use ($process) {
                $process->save();

                return $process->getKey();
            },
            'process_collaboration_id' => function () {
                return ProcessCollaboration::factory()->create()->getKey();
            },
            'process_version_id' => function (array $processRequest) {
                return Process::find($processRequest['process_id'])->getLatestVersion()->id;
            },
        ];
    }
}
