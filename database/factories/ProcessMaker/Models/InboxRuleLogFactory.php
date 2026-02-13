<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\InboxRule>
 */
class InboxRuleLogFactory extends Factory
{
    protected $model = InboxRuleLog::class;

    public function definition()
    {
        return [
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'inbox_rule_id' => function () {
                return InboxRule::factory()->create()->id;
            },
            'process_request_token_id' => function () {
                return ProcessRequestToken::factory()->create()->id;
            },
            'inbox_rule_attributes' => [
                'make_draft' => false,
                'submit_data' => false,
                'mark_as_priority' => false,
                'reassign_to_user_id' => null,
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
