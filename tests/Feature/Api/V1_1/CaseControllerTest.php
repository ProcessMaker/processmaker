<?php

namespace Tests\Feature\Api\V1_1;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseControllerTest extends TestCase
{
    use RequestHelper;

    public static function createUser(string $username, string $password = 'secret', string $status = 'ACTIVE'): User
    {
        return User::factory()->create([
            'username' => $username,
            'password' => Hash::make($password),
            'status' => $status,
        ]);
    }

    public static function createCasesStartedForUser(int $userId, int $count = 1, $data = [])
    {
        return CaseStarted::factory()->count($count)->create(array_merge(['user_id' => $userId], $data));
    }

    public static function createCasesParticipatedForUser(int $userId, int $count = 1, $data = [])
    {
        return CaseParticipated::factory()->count($count)->create(array_merge(['user_id' => $userId], $data));
    }

    public function test_get_all_cases(): void
    {
        $userA = self::createUser('user_a');
        $cases = self::createCasesStartedForUser($userA->id, 10);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
    }

    public function test_get_in_progress(): void
    {
        $userA = self::createUser('user_a');
        $cases = self::createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonMissing(['case_status' => 'COMPLETED']);

        // The status parameter should be ignored
        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress'), ['status' => 'COMPLETED']);
        $response->assertJsonCount($cases->count(), 'data');
    }

    public function test_get_completed(): void
    {
        $userA = self::createUser('user_a');
        $cases = self::createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);

        $response = $this->apiCall('GET', route('api.1.1.cases.completed'));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonFragment(['case_status' => 'COMPLETED']);
        $response->assertJsonMissing(['case_status' => 'IN_PROGRESS']);

        // The status parameter should be ignored
        $response = $this->apiCall('GET', route('api.1.1.cases.completed'), ['status' => 'IN_PROGRESS']);
        $response->assertJsonCount($cases->count(), 'data');
    }

    public function test_get_all_cases_by_users(): void
    {
        $userA = self::createUser('user_a');
        $userB = self::createUser('user_b');

        $casesA = self::createCasesStartedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesB = self::createCasesStartedForUser($userB->id, 6, ['case_status' => 'COMPLETED']);
        $casesC = self::createCasesStartedForUser($userA->id, 4, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));

        $total = $casesA->count() + $casesB->count() + $casesC->count();
        $response->assertStatus(200);
        $response->assertJsonCount($total, 'data');

        $totalUserA = $casesA->count() + $casesC->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['userId' => $userA->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($totalUserA, 'data');
        $response->assertJsonMissing(['user_id' => $userB->id]);

        $totalUserB = $casesB->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['userId' => $userB->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($totalUserB, 'data');
        $response->assertJsonMissing(['user_id' => $userA->id]);
    }

    public function test_get_all_cases_by_status(): void
    {
        $userA = self::createUser('user_a');
        $userB = self::createUser('user_b');

        $casesA = self::createCasesStartedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);
        $casesB = self::createCasesStartedForUser($userB->id, 6, ['case_status' => 'IN_PROGRESS']);
        $casesC = self::createCasesStartedForUser($userA->id, 4, ['case_status' => 'COMPLETED']);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['status' => 'IN_PROGRESS']));
        $response->assertStatus(200);
        $response->assertJsonCount($casesB->count(), 'data');

        $totalCompleted = $casesA->count() + $casesC->count();
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['status' => 'COMPLETED']));
        $response->assertStatus(200);
        $response->assertJsonCount($totalCompleted, 'data');
    }

    public function test_get_in_progress_by_user(): void
    {
        $userA = self::createUser('user_a');
        $userB = self::createUser('user_b');
        $userC = self::createUser('user_c');
        $casesA = self::createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesB = self::createCasesParticipatedForUser($userB->id, 6, ['case_status' => 'IN_PROGRESS']);
        $casesC = self::createCasesParticipatedForUser($userC->id, 4, ['case_status' => 'IN_PROGRESS']);

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userA->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesA->count(), 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userB->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesB->count(), 'data');

        $response = $this->apiCall('GET', route('api.1.1.cases.in_progress', ['userId' => $userC->id]));
        $response->assertStatus(200);
        $response->assertJsonCount($casesC->count(), 'data');
    }

    public function test_get_all_cases_sort_by_case_number(): void
    {
        $userA = self::createUser('user_a');
        $cases = self::createCasesStartedForUser($userA->id, 10);

        $casesSorted = $cases->sortBy('case_number');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'case_number:asc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.case_number', $casesSorted->first()->case_number);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'case_number:desc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.case_number', $casesSorted->last()->case_number);
    }

    public function test_get_all_cases_sort_by_completed_at(): void
    {
        $userA = self::createUser('user_a');
        $cases = self::createCasesStartedForUser($userA->id, 10);
        $casesSorted = $cases->sortBy('completed_at');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'completed_at:asc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.completed_at', $casesSorted->first()->completed_at->format('Y-m-d H:i:s'));

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => 'completed_at:desc']));
        $response->assertStatus(200);
        $response->assertJsonCount($cases->count(), 'data');
        $response->assertJsonPath('data.0.completed_at', $casesSorted->last()->completed_at->format('Y-m-d H:i:s'));
    }

    public function test_get_all_cases_sort_by_invalid_field(): void
    {
        $invalidField = 'invalid_field';

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => $invalidField]));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The sortBy must be a comma-separated list of field:asc|desc.');

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['sortBy' => "$invalidField:asc"]));
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => "Sort by field $invalidField is not allowed."]);
    }

    public function test_filter_by_case_number(): void
    {
        $userA = self::createUser('user_a');
        $caseNumber = 123456;
        self::createCasesStartedForUser($userA->id, 5);
        self::createCasesStartedForUser($userA->id, 1, ['case_number' => $caseNumber]);

        $filterBy = [
            'filterBy' => json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => 'case_number'],
                    'operator' => '=',
                    'value' => $caseNumber,
                ],
            ]),
        ];

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['case_number' => $caseNumber]);
    }

    public function test_filter_by_case_status(): void
    {
        $userA = self::createUser('user_a');
        $casesA = self::createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $casesB = self::createCasesStartedForUser($userA->id, 1, [
            'case_number' => $caseNumber,
            'case_status' => 'IN_PROGRESS',
        ]);

        $filterBy = [
            'filterBy' => json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => 'case_status'],
                    'operator' => '=',
                    'value' => 'IN_PROGRESS',
                ],
            ]),
        ];

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(200);

        $total = $casesA->where('case_status', 'IN_PROGRESS')->count() +
            $casesB->where('case_status', 'IN_PROGRESS')->count();
        $response->assertJsonCount($total, 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonMissing(['case_status' => 'COMPLETED']);
    }

    public function test_filter_by_user_and_case_status(): void
    {
        $userA = self::createUser('user_a');
        $casesA = self::createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $casesB = self::createCasesStartedForUser($userA->id, 1, [
            'case_number' => $caseNumber,
            'case_status' => 'IN_PROGRESS',
        ]);

        $filterBy = [
            'filterBy' => json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => 'user_id'],
                    'operator' => '=',
                    'value' => $userA->id,
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'case_status'],
                    'operator' => '=',
                    'value' => 'IN_PROGRESS',
                ],
            ]),
        ];

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(200);

        $total = $casesA->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')->count() +
            $casesB->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')->count();
        $response->assertJsonCount($total, 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonFragment(['user_id' => $userA->id]);
    }

    public function test_filter_by_user_case_status_and_created_at(): void
    {
        $userA = self::createUser('user_a');
        $casesA = self::createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $casesB = self::createCasesStartedForUser($userA->id, 1, [
            'case_number' => $caseNumber,
            'case_status' => 'IN_PROGRESS',
        ]);

        $filterBy = [
            'filterBy' => json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => 'user_id'],
                    'operator' => '=',
                    'value' => $userA->id,
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'case_status'],
                    'operator' => '=',
                    'value' => 'IN_PROGRESS',
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'created_at'],
                    'operator' => '>',
                    'value' => '2023-02-10',
                ],
            ]),
        ];

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(200);

        $total = $casesA->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')
            ->where('created_at', '>', '2023-02-10')->count() +
            $casesB->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')
            ->where('created_at', '>', '2023-02-10')->count();
        $response->assertJsonCount($total, 'data');
        $response->assertJsonFragment(['case_status' => 'IN_PROGRESS']);
        $response->assertJsonFragment(['user_id' => $userA->id]);
    }

    public function test_filter_by_user_case_status_created_at_and_completed_at(): void
    {
        $userA = self::createUser('user_a');
        $casesA = self::createCasesStartedForUser($userA->id, 5);
        $caseNumber = 123456;
        $casesB = self::createCasesStartedForUser($userA->id, 1, [
            'case_number' => $caseNumber,
            'case_status' => 'IN_PROGRESS',
        ]);

        $filterBy = [
            'filterBy' => json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => 'user_id'],
                    'operator' => '=',
                    'value' => $userA->id,
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'case_status'],
                    'operator' => '=',
                    'value' => 'IN_PROGRESS',
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'created_at'],
                    'operator' => '>',
                    'value' => '2023-02-10',
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'completed_at'],
                    'operator' => '>',
                    'value' => '2023-04-01',
                ],
            ]),
        ];

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(200);

        $total = $casesA->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')
            ->where('created_at', '>', '2023-02-10')
            ->where('completed_at', '>', '2023-04-01')->count() +
            $casesB->where('user_id', $userA->id)
            ->where('case_status', 'IN_PROGRESS')
            ->where('created_at', '>', '2023-02-10')
            ->where('completed_at', '>', '2023-04-01')->count();

        $response->assertJsonCount($total, 'data');
        $json = $response->json();
        $metaTotal = $json['meta']['total'];
        $this->assertEquals($total, $metaTotal, 'The total count of cases does not match the expected value. ' . json_encode($json));
    }

    public function test_get_all_cases_filter_by_invalid_field(): void
    {
        $invalidField = 'invalid_field';
        $filterBy = ['filterBy' => '[invalid_json'];
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', $filterBy));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The Filter by field must be a valid JSON string.');
        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases', ['filterBy' => [$invalidField => 'value']]));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The Filter by field must be a valid JSON string.');
    }

    public function test_get_my_cases_counters_ok(): void
    {
        /**
         * Creating missing permissions, probably this part should be removed when
         * the permissions were added in another ticket
         */
        Permission::create([
            'name' => 'view-all_cases',
            'title' => 'View All Cases',
        ]);
        Permission::create([
            'name' => 'view-my_requests',
            'title' => 'View My Requests',
        ]);

        $userA = self::createUser('user_a');
        $userB = self::createUser('user_b');

        $userA->giveDirectPermission('view-all_cases');
        $userA->giveDirectPermission('view-my_requests');
        $userB->giveDirectPermission('view-all_cases');
        $userB->giveDirectPermission('view-my_requests');

        $casesA = self::createCasesStartedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);
        $casesB = self::createCasesStartedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesC = self::createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'COMPLETED']);
        $casesD = self::createCasesParticipatedForUser($userA->id, 5, ['case_status' => 'IN_PROGRESS']);

        $casesE = self::createCasesStartedForUser($userB->id, 5, ['case_status' => 'COMPLETED']);
        $casesF = self::createCasesStartedForUser($userB->id, 5, ['case_status' => 'IN_PROGRESS']);
        $casesG = self::createCasesParticipatedForUser($userB->id, 5, ['case_status' => 'COMPLETED']);
        $casesH = self::createCasesParticipatedForUser($userB->id, 5, ['case_status' => 'IN_PROGRESS']);

        $in_progress = ProcessRequest::factory(5)->create([
            'status' => 'ACTIVE',
            'user_id' => $userA->id,
        ]);

        $response = $this->apiCall('GET', route('api.1.1.cases.my_cases_counters'), ['userId' => $userA->id]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['totalAllCases' => 20]);
        $response->assertJsonFragment(['totalMyCases' => 10]);
        $response->assertJsonFragment(['totalInProgress' => 5]);
        $response->assertJsonFragment(['totalCompleted' => 5]);
        $response->assertJsonFragment(['totalMyRequest' => 5]);
    }

    public function test_get_all_cases_participants(): void
    {
        $userA = $this->createUser('user_a');
        $userB = $this->createUser('user_b');

        $casesA = $this->createCasesStartedForUser($userA->id, 1, ['case_status' => 'IN_PROGRESS', 'participants' => [$userA->id, $userB->id]]);
        $casesB = $this->createCasesStartedForUser($userB->id, 1, ['case_status' => 'COMPLETED', 'participants' => [$userA->id]]);
        $casesC = $this->createCasesStartedForUser($userA->id, 1, ['case_status' => 'IN_PROGRESS', 'participants' => [$userB->id]]);

        $response = $this->apiCall('GET', route('api.1.1.cases.all_cases'));

        $total = $casesA->count() + $casesB->count() + $casesC->count();
        $response->assertStatus(200);
        $response->assertJsonCount($total, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'participants' => [
                        '*' => [
                            'id',
                            'name',
                            'title',
                            'avatar',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJsonFragment(['participants' => [
            [
                'id' => $userA->id,
                'name' => $userA->fullname,
                'title' => $userA->title,
                'avatar' => $userA->avatar,
            ],
            [
                'id' => $userB->id,
                'name' => $userB->fullname,
                'title' => $userB->title,
                'avatar' => $userB->avatar,
            ],
        ]]);

        $response->assertJsonPath('data.1.participants', [
            [
                'id' => $userA->id,
                'name' => $userA->fullname,
                'title' => $userA->title,
                'avatar' => $userA->avatar,
            ],
        ]);

        $response->assertJsonPath('data.2.participants', [
            [
                'id' => $userB->id,
                'name' => $userB->fullname,
                'title' => $userB->title,
                'avatar' => $userB->avatar,
            ],
        ]);
    }
}
