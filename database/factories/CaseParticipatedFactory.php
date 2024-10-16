<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Constants\CaseStatusConstants;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseUtils;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ProcessMaker\Models\CaseParticipated>
 */
class CaseParticipatedFactory extends Factory
{
    protected $model = CaseParticipated::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::get();

        $caseNumber = fake()->unique()->randomNumber();
        $caseTitle = fake()->words(4, true);

        $dataKeywords = [
            'case_number' => $caseNumber,
            'case_title' => $caseTitle,
        ];

        return [
            'user_id' => $users->random()->id,
            'case_number' => $caseNumber,
            'case_title' => $caseTitle,
            'case_title_formatted' => fake()->words(3, true),
            'case_status' => fake()->randomElement([CaseStatusConstants::IN_PROGRESS, CaseStatusConstants::COMPLETED]),
            'processes' => array_map(function () {
                return [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(2, true),
                ];
            }, range(1, 3)),
            'requests' => [
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(2, true),
                    'parent_request' => fake()->randomNumber(),
                ],
                [
                    'id' => fake()->randomNumber(),
                    'name' => fake()->words(3, true),
                    'parent_request' => fake()->randomNumber(),
                ],
            ],
            'request_tokens' => array_map(fn () => fake()->randomElement([
                fake()->randomNumber(),
                fake()->randomNumber(),
                fake()->randomNumber(),
            ]), range(1, 3)),
            'tasks' => [
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(4, true),
                ],
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(3, true),
                ],
                [
                    'id' => fake()->numerify('node_####'),
                    'name' => fake()->words(2, true),
                ],
            ],
            'participants' => array_map(fn () => fake()->randomElement($users->pluck('id')->toArray()), range(1, 3)),
            'initiated_at' => fake()->dateTime(),
            'completed_at' => fake()->dateTime(),
            'keywords' => CaseUtils::getKeywords($dataKeywords),
        ];
    }
}
