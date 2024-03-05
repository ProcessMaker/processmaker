<?php

namespace Database\Factories\ProcessMaker\Models;

use ProcessMaker\Models\InboxRuleLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\InboxRule>
 */
class InboxRuleLogFactory extends Factory
{
    protected $model = InboxRuleLog::class;

    public function definition()
    {
        return [
            'inbox_rule_id' => null,
            'task_id' => null,
            'inbox_rule_data' => json_encode(['key1' => 'value1', 'key2' => 'value2']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
