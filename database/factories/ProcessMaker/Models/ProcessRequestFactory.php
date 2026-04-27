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
        return [
            'name' => $this->faker->sentence(3),
            'data' => [],
            'status' => 'ACTIVE',
            'callable_id' => function () {
                $process = Process::factory()->create();
                $bpmnProcess = $process->getDefinitions()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process')->item(0);

                return $bpmnProcess->getAttribute('id');
            },
            'user_id' => function () {
                return User::factory()->create()->getKey();
            },
            'process_id' => function () {
                $process = Process::factory()->create();

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

    public function withCaseNumber(int $caseNumber): self
    {
        $caseTitle = $this->faker->words(4, true);

        return $this->state([
            'case_number' => $caseNumber,
            'case_title' => $caseTitle,
            'case_title_formatted' => $caseTitle,
        ])->afterCreating(function (ProcessRequest $request) use ($caseNumber, $caseTitle) {
            $request->case_number = $caseNumber;
            $request->case_title = $caseTitle;
            $request->case_title_formatted = $caseTitle;
            $request->save();
        });
    }
}
