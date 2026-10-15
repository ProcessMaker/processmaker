<?php

namespace Tests\Unit\ProcessMaker\Repositories;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\ProcessExecutionRawRepository;
use Tests\TestCase;

class ProcessExecutionRawRepositoryTest extends TestCase
{
    public function testGetProcessRequestForCompleteRawLoadsColumnsWithoutData(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['marker' => 'should_not_load'],
        ]);

        $repository = new ProcessExecutionRawRepository();
        $hydrated = $repository->getProcessRequestForCompleteRaw($request->id);

        $this->assertSame($request->id, $hydrated->id);
        $this->assertArrayHasKey('do_not_sanitize', $hydrated->getAttributes());
        $this->assertArrayNotHasKey('data', $hydrated->getAttributes());
    }

    public function testGetProcessRequestForResponseRawIncludesDataColumn(): void
    {
        $request = ProcessRequest::factory()->create([
            'data' => ['marker' => 'persisted'],
        ]);

        $repository = new ProcessExecutionRawRepository();
        $hydrated = $repository->getProcessRequestForResponseRaw($request->id);

        $this->assertIsArray($hydrated->data);
        $this->assertSame('persisted', $hydrated->data['marker']);
    }

    public function testRawGroupAssignmentResolvesActiveNestedGroups(): void
    {
        $user = User::factory()->create(['status' => 'ACTIVE']);
        $parentGroup = Group::factory()->create(['status' => 'ACTIVE']);
        $childGroup = Group::factory()->create(['status' => 'ACTIVE']);

        GroupMember::withoutEvents(function () use ($parentGroup, $childGroup, $user): void {
            GroupMember::create([
                'group_id' => $parentGroup->id,
                'member_id' => $childGroup->id,
                'member_type' => Group::class,
            ]);
            GroupMember::create([
                'group_id' => $childGroup->id,
                'member_id' => $user->id,
                'member_type' => User::class,
            ]);
        });

        $repository = new ProcessExecutionRawRepository();
        $method = new \ReflectionMethod($repository, 'mergeGroupMemberUserIdsRaw');
        $method->setAccessible(true);
        $users = [];

        $method->invokeArgs($repository, [[$parentGroup->id], &$users]);

        $this->assertSame([$user->id], array_values($users));
    }
}
