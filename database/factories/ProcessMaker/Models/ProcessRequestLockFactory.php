<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;

class ProcessRequestLockFactory extends Factory
{
    protected $model = ProcessRequestLock::class;

    public function definition(): array
    {
        return [
            'process_request_id' => function () {
                return ProcessRequest::factory()->create()->id;
            },
            'process_request_token_id' => function () {
                return ProcessRequestToken::factory()->create()->id;
            },
            'due_at' => now()->addMinute(),
        ];
    }
}
