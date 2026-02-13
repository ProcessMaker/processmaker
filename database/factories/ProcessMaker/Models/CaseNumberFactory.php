<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\ProcessRequest;

class CaseNumberFactory extends Factory
{
    protected $model = CaseNumber::class;

    public function definition(): array
    {
        return [
            'process_request_id' => function () {
                return ProcessRequest::factory()->create()->id;
            },
        ];
    }
}
