<?php

use Faker\Generator as Faker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;

/**
 * Model factory for a process request
 */
$factory->define(ProcessRequest::class, function (Faker $faker) {
    $process = factory(Process::class)->make();
    return [
        'name' => $faker->sentence(3),
        'data' => [],
        'status' => $faker->randomElement(['DRAFT', 'ACTIVE', 'COMPLETED']),
        'callable_id' => function () use ($process) {
            $process->save();
            $bpmnProcess = $process->getDefinitions()->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process')->item(0);
            return $bpmnProcess->getAttribute('id');
        },
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
        'process_id' => function () use ($process) {
            $process->save();
            return $process->getKey();
        },
        'process_collaboration_id' => function () {
            return factory(ProcessCollaboration::class)->create()->getKey();
        }
    ];
});
