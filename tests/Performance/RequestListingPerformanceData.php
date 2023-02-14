<?php

namespace Tests\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;

class RequestListingPerformanceData
{
    public $user;

    public $group;

    public $chunkSize = 100;

    public $processCount = 1;

    public $tokensPerRequest = 10;

    public $requestCount = 1_000;

    public $userCount = 200;

    public $dataSize = 1_000;

    public $processes = [];

    public $processIds = null;

    public function run()
    {
        $this->user = User::where('username', 'perfuser')->first();
        if (!$this->user) {
            $this->user = User::factory()->create([
                'username' => 'perfuser',
                'password' => Hash::make('pass'),
                'status' => 'ACTIVE',
            ]);
        }

        $requestStarterUser = User::factory()->create();

        $this->processIds = collect();
        for ($i = 0; $i < $this->processCount; $i++) {
            $process = Process::factory()->create();
            $collaboration = ProcessCollaboration::factory()->create([
                'process_id' => $process->id,
            ]);
            $process->update(['name' => 'Perf test' . $process->id]);
            $this->processes[] = $process;
            $this->processIds->push([$process->id, $collaboration->id]);
        }

        $this->group = Group::factory()->create();
        $this->user->groups()->sync([$this->group->id]);

        $userParams = [
            'password' => 'abc123',
            'status' => 'ACTIVE',
            'is_administrator' => false,
            'is_system' => false,
        ];

        $userStartId = (DB::select('select max(id) as id from `users`')[0]->id ?? 0) + 1;
        $userEndId = $userStartId;
        foreach (range(1, ceil($this->userCount / $this->chunkSize)) as $ci) {
            $data = [];
            foreach (range(1, $this->chunkSize) as $ui) {
                $data[] = array_merge($userParams, ['id' => $userEndId, 'username' => 'user' . $userEndId]);
                $userEndId++;
            }
            User::insert($data);
        }
        $userEndId--;

        $requestParams = [
            'user_id' => $requestStarterUser->id,
            'process_version_id' => $process->getLatestVersion()->id,
            'callable_id' => 'ProcessId',
            'data' => '{"foo":"bar", "random":"' . $this->generateRandomString($this->dataSize) . '"}',
        ];

        $tokenParams = [
            'process_request_id' => null,
            'element_id' => 'node_1',
            'element_type' => 'task',
            'data' => '{"foo":"bar", "random":"' . $this->generateRandomString($this->dataSize) . '"}',
        ];

        $requestStartId = (DB::select('select max(id) as id from `process_requests`')[0]->id ?? 0) + 1;
        $requestEndId = $requestStartId;
        foreach (range(1, ceil($this->requestCount / $this->chunkSize)) as $ic) {
            $chunkStartId = $requestEndId;
            $data = [];
            foreach (range(1, $this->chunkSize) as $ir) {
                [$processId, $collaborationId] = $this->processIds->random();
                $data[] = array_merge($requestParams, [
                    'id' => $requestEndId,
                    'process_id' => $processId,
                    'name' => 'Perf test ' . $processId,
                    'process_collaboration_id' => $collaborationId,
                ]);
                $requestEndId++;
            }
            ProcessRequest::insert($data);

            $tokenData = [];
            foreach (range(1, $this->chunkSize) as $ip) {
                foreach (range(1, $this->tokensPerRequest) as $it) {
                    $userId = rand($userStartId, $userEndId);
                    $tokenData[] = array_merge($tokenParams, [
                        'process_id' => $processId,
                        'process_request_id' => $chunkStartId,
                        'user_id' => $userId,
                    ]);
                }
                $chunkStartId++;
            }
            ProcessRequestToken::insert($tokenData);
        }
        $requestEndId--;
    }

    public function associateWithUser(
        $selfServeUsersCount = 1,
        $selfServeGroupsCount = 1,
        $participateCount = 1
    ) {
        $total = $selfServeUsersCount + $selfServeGroupsCount + $participateCount;
        $prs = ProcessRequest::whereIn('process_id', $this->processIds->map(fn ($p) => $p[0]))
            ->select('id')
            ->inRandomOrder()
            ->limit($total)
            ->get();

        $ri = 0;
        $ti = $this->tokensPerRequest - 1;

        for ($i = 0; $i < $participateCount; $i++) {
            $t = $prs[$ri]->tokens[$ti];
            $t->user_id = $this->user->id;
            $t->saveOrFail();
            $ri++;
        }

        for ($i = 0; $i < $selfServeGroupsCount; $i++) {
            $t = $prs[$ri]->tokens[$ti];
            $t->is_self_service = true;
            $t->self_service_groups = ['users' => [], 'groups' => ['123', (string) $this->group->id]];
            $t->saveOrFail();
            $ri++;
        }

        for ($i = 0; $i < $selfServeUsersCount; $i++) {
            $t = $prs[$ri]->tokens[$ti];
            $t->is_self_service = true;
            $t->self_service_groups = ['users' => [(string) $this->user->id], 'groups' => []];
            $t->saveOrFail();
            $ri++;
        }
    }

    /**
     * @author Stephen Watkins https://stackoverflow.com/a/4356295/176751
     */
    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
