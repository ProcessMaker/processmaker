<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class AssignmentExporterTest extends TestCase
{
    use HelperTrait;

    private const TASK_1 = '/bpmn:definitions/bpmn:process/bpmn:task[1]';

    private const TASK_2 = '/bpmn:definitions/bpmn:process/bpmn:task[2]';

    private const MANUAL_TASK_1 = '/bpmn:definitions/bpmn:process/bpmn:manualTask[1]';

    private const MANUAL_TASK_2 = '/bpmn:definitions/bpmn:process/bpmn:manualTask[2]';

    private const CALL_ACTIVITY_1 = '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]';

    private const CALL_ACTIVITY_2 = '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]';

    public function testExportImportAssignments()
    {
        // Create users and groups
        $users = User::factory(12)->create();
        $groups = Group::factory(10)->create();

        // Assign three users to group 1, assign two users to group 2, assign one user to group 3
        foreach ($users as $key => $user) {
            if ($key <= 2) {
                $group = $groups[0];
            } elseif ($key <= 4) {
                $group = $groups[1];
            } elseif ($key <= 5) {
                $group = $groups[2];
            } else {
                continue;
            }

            GroupMember::factory()->create([
                'member_type' => User::class,
                'member_id' => $user->id,
                'group_id' => $group->id,
            ]);
        }

        $this->addGlobalSignalProcess();

        // Create process
        $process = $this->createProcess('process-with-different-kinds-of-assignments', ['name' => 'processTest']);

        // Assign users to process assignments
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers', implode(',', [$users[0]->id, $users[1]->id, $users[2]->id]));
        Utils::setAttributeAtXPath($process, self::TASK_2, 'pm:assignedUsers', implode(',', [$users[3]->id, $users[4]->id]));
        Utils::setAttributeAtXPath($process, self::MANUAL_TASK_1, 'pm:assignedUsers', implode(',', [$users[5]->id, $users[6]->id]));
        Utils::setAttributeAtXPath($process, self::MANUAL_TASK_2, 'pm:assignedUsers', implode(',', [$users[7]->id]));
        Utils::setAttributeAtXPath($process, self::CALL_ACTIVITY_1, 'pm:assignedUsers', implode(',', [$users[8]->id, $users[9]->id]));
        Utils::setAttributeAtXPath($process, self::CALL_ACTIVITY_2, 'pm:assignedUsers', implode(',', [$users[10]->id]));

        // Assign groups to process assignments
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups', implode(',', [$groups[0]->id]));
        Utils::setAttributeAtXPath($process, self::TASK_2, 'pm:assignedGroups', implode(',', [$groups[1]->id, $groups[2]->id]));
        Utils::setAttributeAtXPath($process, self::MANUAL_TASK_1, 'pm:assignedGroups', implode(',', [$groups[3]->id]));
        Utils::setAttributeAtXPath($process, self::MANUAL_TASK_2, 'pm:assignedGroups', implode(',', [$groups[4]->id, $groups[5]->id]));
        Utils::setAttributeAtXPath($process, self::CALL_ACTIVITY_1, 'pm:assignedGroups', implode(',', [$groups[6]->id, $groups[7]->id, $groups[8]->id]));
        Utils::setAttributeAtXPath($process, self::CALL_ACTIVITY_2, 'pm:assignedGroups', implode(',', [$groups[9]->id]));

        $process->save();

        $this->runExportAndImport($process, ProcessExporter::class, function () {
            User::query()->forceDelete();
            Group::query()->forceDelete();
            GroupMember::query()->forceDelete();
            Process::query()->forceDelete();

            $this->assertEquals(0, User::get()->count());
            $this->assertEquals(0, Group::get()->count());
            $this->assertEquals(0, GroupMember::get()->count());
            $this->assertEquals(0, Process::get()->count());
        });

        // Users are groups are no longer exported
        $this->assertEquals(11, User::whereIn('username', $users->pluck('username'))->get()->count());
        $this->assertEquals(10, Group::whereIn('name', $groups->pluck('name'))->get()->count());
        $this->assertDatabaseHas('processes', ['name' => $process->name]);
        $process = Process::where('name', $process->name)->firstOrFail();

        // Get new user/group Ids imported...
        $newUserIds = User::whereIn('username', $users->pluck('username'))
            ->orderBy('id', 'asc')
            ->get()
            ->pluck('id');
        $newGroupIds = Group::whereIn('name', $groups->pluck('name'))
            ->orderBy('id', 'asc')
            ->get()
            ->pluck('id');

        // Assert the new imported user and groups are correctly assigned to the process

        $this->assertEquals("$newUserIds[0],$newUserIds[1],$newUserIds[2]", Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[3],$newUserIds[4]", Utils::getAttributeAtXPath($process, self::TASK_2, 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[5],$newUserIds[6]", Utils::getAttributeAtXPath($process, self::MANUAL_TASK_1, 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[7]", Utils::getAttributeAtXPath($process, self::MANUAL_TASK_2, 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[8],$newUserIds[9]", Utils::getAttributeAtXPath($process, self::CALL_ACTIVITY_1, 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[10]", Utils::getAttributeAtXPath($process, self::CALL_ACTIVITY_2, 'pm:assignedUsers'));

        $this->assertEquals("$newGroupIds[0]", Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[1],$newGroupIds[2]", Utils::getAttributeAtXPath($process, self::TASK_2, 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[3]", Utils::getAttributeAtXPath($process, self::MANUAL_TASK_1, 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[4],$newGroupIds[5]", Utils::getAttributeAtXPath($process, self::MANUAL_TASK_2, 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[6],$newGroupIds[7],$newGroupIds[8]", Utils::getAttributeAtXPath($process, self::CALL_ACTIVITY_1, 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[9]", Utils::getAttributeAtXPath($process, self::CALL_ACTIVITY_2, 'pm:assignedGroups'));
    }

    public function testSomeAssignmentsDoNotExistOnTarget()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        $process = $this->createProcess('process-with-different-kinds-of-assignments', ['name' => 'processTest']);

        // Assign users to process assignments
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers', $user1->id . ',' . $user2->id);
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups', $group1->id . ',' . $group2->id);

        $process->save();

        $this->runExportAndImport($process, ProcessExporter::class, function () use ($user1, $group1) {
            $user1->forceDelete();
            $group1->forceDelete();
            Process::query()->forceDelete();
        }, false);

        $process = Process::where('name', 'processTest')->firstOrFail();
        $this->assertEquals($user2->id, Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers'));
        $this->assertEquals($group2->id, Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups'));
    }

    public function testAllAssignmentsDoNotExistOnTarget()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $process = $this->createProcess('process-with-different-kinds-of-assignments', ['name' => 'processTest']);

        // Assign users to process assignments
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers', $user->id);
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups', $group->id);

        $process->save();

        $this->runExportAndImport($process, ProcessExporter::class, function () {
            User::query()->forceDelete();
            Group::query()->forceDelete();
            Process::query()->forceDelete();
        }, false);

        $process = Process::where('name', 'processTest')->firstOrFail();
        $this->assertEquals('', Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedUsers'));
        $this->assertEquals('', Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups'));
    }

    public function testGroupAssignmentResolvesWhenUuidAndNameMatchOnTarget()
    {
        $group = Group::factory()->create(['name' => 'Accounting']);
        $payload = $this->exportProcessWithGroupAssignment($group);

        Process::where('name', 'processTest')->forceDelete();

        $this->import($payload);

        $this->assertImportedGroupAssignment((string) $group->id);
    }

    public function testGroupAssignmentResolvesByNameWhenUuidDiffersOnTarget()
    {
        $group = Group::factory()->create(['name' => 'Accounting']);
        $payload = $this->exportProcessWithGroupAssignment($group);

        Process::where('name', 'processTest')->forceDelete();
        $group->delete();
        $targetGroup = Group::factory()->create(['name' => 'Accounting']);

        $this->import($payload);

        $this->assertImportedGroupAssignment((string) $targetGroup->id);
    }

    public function testGroupAssignmentDoesNotResolveWhenOnlyUuidMatchesOnTarget()
    {
        $group = Group::factory()->create(['name' => 'Accounting']);
        $payload = $this->exportProcessWithGroupAssignment($group);

        Process::where('name', 'processTest')->forceDelete();
        $group->update(['name' => 'Finance']);

        $this->import($payload);

        $this->assertImportedGroupAssignment('');
    }

    public function testGroupAssignmentDoesNotResolveWhenUuidAndNameAreMissingOnTarget()
    {
        $group = Group::factory()->create(['name' => 'Accounting']);
        $payload = $this->exportProcessWithGroupAssignment($group);

        Process::where('name', 'processTest')->forceDelete();
        $group->delete();

        $this->import($payload);

        $this->assertImportedGroupAssignment('');
    }

    public function testGroupAssignmentKeepsOnlyGroupsResolvedByName()
    {
        $keptGroup = Group::factory()->create(['name' => 'Accounting']);
        $uuidOnlyGroup = Group::factory()->create(['name' => 'Operations']);
        $missingGroup = Group::factory()->create(['name' => 'Support']);
        $payload = $this->exportProcessWithGroupAssignment($keptGroup, $uuidOnlyGroup, $missingGroup);

        Process::where('name', 'processTest')->forceDelete();
        $keptGroup->delete();
        $uuidOnlyGroup->update(['name' => 'Renamed Operations']);
        $missingGroup->delete();
        $targetGroup = Group::factory()->create(['name' => 'Accounting']);

        $this->import($payload);

        $this->assertImportedGroupAssignment((string) $targetGroup->id);
    }

    private function exportProcessWithGroupAssignment(Group ...$groups): array
    {
        $this->addGlobalSignalProcess();

        $process = $this->createProcess('process-with-different-kinds-of-assignments', ['name' => 'processTest']);

        Utils::setAttributeAtXPath(
            $process,
            self::TASK_1,
            'pm:assignedGroups',
            collect($groups)->pluck('id')->join(',')
        );

        $process->save();

        return $this->export($process, ProcessExporter::class, null, false);
    }

    private function assertImportedGroupAssignment(string $expected): void
    {
        $process = Process::where('name', 'processTest')->firstOrFail();

        $this->assertEquals(
            $expected,
            Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignedGroups')
        );
    }
}
