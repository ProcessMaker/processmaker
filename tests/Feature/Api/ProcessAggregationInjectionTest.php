<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Spec for the process aggregation SQL injection.
 *
 * These tests describe the safe behavior. They fail while aggregation is
 * interpolated into selectRaw and while unsafe values can be stored by a
 * user who only has create-processes.
 */
class ProcessAggregationInjectionTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const STAGE_ID = 4242;

    protected function withUserSetup(): void
    {
        $this->user->is_administrator = false;
        $this->user->save();
    }

    public function testStageMappingAttackDoesNotReturnAnotherUsersColumn(): void
    {
        $username = 'aggcanary';
        $victim = User::factory()->create(['username' => $username]);
        // Numeric slice of a string column. The same statement shape reads any
        // string the database user can select; a raw non-numeric string trips
        // the response sum and is not rendered.
        $canary = (string) base_convert(bin2hex(substr($username, 0, 6)), 16, 10);
        $process = $this->makeProcess();
        $this->grant('create-processes');

        $payload = $this->unionPayload(
            '(SELECT CONV(HEX(SUBSTRING(username,1,6)),16,10) FROM users WHERE id=' . (int) $victim->id . ' LIMIT 1)'
        );

        $this->apiCall('POST', route('api.processes.saveStages', ['process' => $process->id]), [
            'stages' => [$this->stageDefinition()],
        ])->assertOk();

        $this->apiCall('POST', route('api.processes.save-aggregation', ['process' => $process->id]), [
            'aggregation' => $payload,
        ]);

        $response = $this->apiCall('GET', route('api.processes.stage-mapping', ['process' => $process->id]));

        $this->assertAggregationDoesNotLeak($response, $canary);
    }

    public function testStageMappingAttackDoesNotReturnAnotherProcessCaseData(): void
    {
        $canary = '815730246';
        $otherCase = ProcessRequest::factory()->create([
            'data' => ['wire_amount' => (int) $canary],
        ]);
        $process = $this->makeProcess();
        $this->grant('create-processes');

        $payload = $this->unionPayload(
            "(SELECT JSON_EXTRACT(data,'$.wire_amount') FROM process_requests WHERE id=" . (int) $otherCase->id . ' LIMIT 1)'
        );

        $this->apiCall('POST', route('api.processes.saveStages', ['process' => $process->id]), [
            'stages' => [$this->stageDefinition()],
        ])->assertOk();

        $this->apiCall('POST', route('api.processes.save-aggregation', ['process' => $process->id]), [
            'aggregation' => $payload,
        ]);

        $response = $this->apiCall('GET', route('api.processes.stage-mapping', ['process' => $process->id]));

        $this->assertAggregationDoesNotLeak($response, $canary);
    }

    public function testStoredAggregationIsNotInterpolatedWhenRead(): void
    {
        $canary = '815730246';
        $otherCase = ProcessRequest::factory()->create([
            'data' => ['wire_amount' => (int) $canary],
        ]);
        $process = $this->makeProcess();
        $process->stages = [$this->stageDefinition()];
        $process->save();

        $payload = $this->unionPayload(
            "(SELECT JSON_EXTRACT(data,'$.wire_amount') FROM process_requests WHERE id=" . (int) $otherCase->id . ' LIMIT 1)'
        );
        DB::table('processes')->where('id', $process->id)->update(['aggregation' => $payload]);

        $response = $this->apiCall('GET', route('api.processes.stage-mapping', ['process' => $process->id]));

        $this->assertAggregationDoesNotLeak($response, $canary);
    }

    public function testSaveAggregationRejectsValuesThatAreNotIdentifiers(): void
    {
        $process = $this->makeProcess();
        $process->aggregation = 'amount';
        $process->save();
        $this->grant('create-processes', 'edit-processes');

        $response = $this->apiCall('POST', route('api.processes.save-aggregation', ['process' => $process->id]), [
            'aggregation' => $this->unionPayload('(SELECT id FROM users LIMIT 1)'),
        ]);

        $response->assertStatus(422);
        $process->refresh();
        $this->assertSame('amount', $process->aggregation);
    }

    public function testUpdateProcessRejectsAggregationThatIsNotAnIdentifier(): void
    {
        $process = $this->makeProcess();
        $process->aggregation = 'amount';
        $process->save();
        $this->grant('edit-processes');

        $response = $this->apiCall('PUT', route('api.processes.update', ['process' => $process->id]), [
            'description' => $process->description,
            'aggregation' => "amount') OR (SELECT username FROM users)#",
        ]);

        $response->assertStatus(422);
        $process->refresh();
        $this->assertSame('amount', $process->aggregation);
    }

    public function testCreateProcessesPermissionCannotSaveAggregationOrStages(): void
    {
        $process = $this->makeProcess();
        $process->aggregation = 'amount';
        $process->stages = null;
        $process->save();
        $this->grant('create-processes');

        $aggregationResponse = $this->apiCall('POST', route('api.processes.save-aggregation', ['process' => $process->id]), [
            'aggregation' => 'var_amount',
        ]);
        $stagesResponse = $this->apiCall('POST', route('api.processes.saveStages', ['process' => $process->id]), [
            'stages' => [$this->stageDefinition()],
        ]);

        $aggregationResponse->assertStatus(403);
        $stagesResponse->assertStatus(403);
        $process->refresh();
        $this->assertSame('amount', $process->aggregation);
        $this->assertEmpty($process->stages);
    }

    public function testDottedAggregationIdentifierStillSumsCaseData(): void
    {
        $stages = [
            ['id' => 101, 'order' => 1, 'name' => 'Custom Stage 1', 'selected' => false],
            ['id' => 102, 'order' => 2, 'name' => 'Custom Stage 2', 'selected' => false],
        ];
        $process = $this->makeProcess();
        $this->grant('create-processes', 'edit-processes');

        $this->apiCall('POST', route('api.processes.saveStages', ['process' => $process->id]), [
            'stages' => $stages,
        ])->assertOk();

        $this->apiCall('POST', route('api.processes.save-aggregation', ['process' => $process->id]), [
            'aggregation' => 'parent.child',
        ])->assertOk();

        ProcessRequest::factory()->count(2)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => 101,
            'last_stage_name' => 'Custom Stage 1',
            'data' => ['parent' => ['child' => '10']],
        ]);
        ProcessRequest::factory()->count(3)->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'last_stage_id' => 102,
            'last_stage_name' => 'Custom Stage 2',
            'data' => ['parent' => ['child' => '4']],
        ]);

        $response = $this->apiCall('GET', route('api.processes.stage-mapping', ['process' => $process->id]));
        $response->assertOk();

        $data = $response->json('data');
        $firstStage = collect($data['stages'])->firstWhere('stage_id', 101);
        $secondStage = collect($data['stages'])->firstWhere('stage_id', 102);

        $this->assertEquals(20, $firstStage['agregation_sum']);
        $this->assertEquals(2, $firstStage['agregation_count']);
        $this->assertEquals(12, $secondStage['agregation_sum']);
        $this->assertEquals(3, $secondStage['agregation_count']);
    }

    private function assertAggregationDoesNotLeak($response, string $canary): void
    {
        $this->assertStringNotContainsString($canary, $response->getContent());
        $this->assertContains($response->getStatusCode(), [200, 422]);

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $data = $response->json('data');
        $this->assertIsArray($data);
        $sums = [$data['total']['agregation_sum'] ?? null];
        foreach ($data['stages'] ?? [] as $stage) {
            $sums[] = $stage['agregation_sum'] ?? null;
        }

        foreach ($sums as $sum) {
            $this->assertTrue(is_numeric($sum));
            $this->assertStringNotContainsString($canary, (string) $sum);
        }
    }

    private function unionPayload(string $scalarSubquery): string
    {
        // A trailing "-- " comment is trimmed away with the request body, so the
        // statement uses MySQL's "#" comment, which needs no following space.
        $payload = "x') AS CHAR)) AS d,0 AS var_aggregation_sum FROM process_requests WHERE process_id=? AND 0 "
            . 'GROUP BY last_stage_id UNION SELECT ' . self::STAGE_ID . ',1,0,(' . $scalarSubquery . ')#';

        $this->assertLessThanOrEqual(255, strlen($payload));

        return $payload;
    }

    private function stageDefinition(): array
    {
        return [
            'id' => self::STAGE_ID,
            'order' => 1,
            'name' => 'Mapped stage',
            'selected' => true,
        ];
    }

    private function makeProcess(): Process
    {
        return Process::factory()->create([
            'status' => 'ACTIVE',
            'aggregation' => 'amount',
        ]);
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            $this->user->permissions()->syncWithoutDetaching([
                Permission::byName($permission)->id,
            ]);
        }

        $this->user->invalidatePermissionCache();
        $this->user->refresh();
    }
}
