<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\Tree;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessNotificationSetting;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\SignalEventDefinition;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class AssignmentExporterTest extends TestCase
{
    use HelperTrait;

    private function fixtures()
    {
        // Create simple screens. Extensive screen tests are in ScreenExporterTest.php
        $cancelScreen = $this->createScreen('basic-form-screen', ['title' => 'Cancel Screen']);
        $requestDetailScreen = $this->createScreen('basic-display-screen', ['title' => 'Request Detail Screen']);

        $manager = User::factory()->create(['username' => 'manager']);
        $group = Group::factory()->create(['name' => 'Group', 'description' => 'My Example Group', 'manager_id' => $manager->id]);
        $user = User::factory()->create(['username' => 'testuser']);
        $user->groups()->sync([$group->id]);

        $process = $this->createProcess('basic-process', [
            'name' => 'Process',
            'user_id' => $user->id,
            'cancel_screen_id' => $cancelScreen->id,
            'request_detail_screen_id' => $requestDetailScreen->id,
        ]);

        // Notification Settings.
        $processNotificationSetting1 = ProcessNotificationSetting::factory()->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
        ]);
        $processNotificationSetting2 = ProcessNotificationSetting::factory()->create([
            'process_id' => $process->id,
            'notifiable_type' => 'requester',
            'notification_type' => 'assigned',
            'element_id' => 'node_3',
        ]);

        return [$process, $cancelScreen, $requestDetailScreen, $user, $processNotificationSetting1, $processNotificationSetting2];
    }

    public function testExportImportAssignments()
    {
        // Create users and groups
        $users = User::factory(12)->create();
        $groups = Group::factory(10)->create();

        // Assign three users to group 1, assign two users to group 2, assign one user to group 3
        foreach ($users as $key => $user) {
            if ($key <= 2) {
                $group = $groups[0];
            }
            if ($key > 2 and $key <= 4) {
                $group = $groups[1];
            }
            if ($key > 4 and $key <= 5) {
                $group = $groups[2];
            }

            // Assign last user to last group
            if ($key == 11) {
                $group = $groups[9];
            }

            if ($key > 5) {
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
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:assignedUsers', implode(',', [$users[0]->id, $users[1]->id, $users[2]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:assignedUsers', implode(',', [$users[3]->id, $users[4]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[1]', 'pm:assignedUsers', implode(',', [$users[5]->id, $users[6]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[2]', 'pm:assignedUsers', implode(',', [$users[7]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'pm:assignedUsers', implode(',', [$users[8]->id, $users[9]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'pm:assignedUsers', implode(',', [$users[10]->id]));

        // Assign groups to process assignments
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:assignedGroups', implode(',', [$groups[0]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:assignedGroups', implode(',', [$groups[1]->id, $groups[2]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[1]', 'pm:assignedGroups', implode(',', [$groups[3]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[2]', 'pm:assignedGroups', implode(',', [$groups[4]->id, $groups[5]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'pm:assignedGroups', implode(',', [$groups[6]->id, $groups[7]->id, $groups[8]->id]));
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'pm:assignedGroups', implode(',', [$groups[9]->id]));

        $process->save();

        $this->runExportAndImport($process, ProcessExporter::class, function () use ($process) {
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

        $this->assertEquals("$newUserIds[0],$newUserIds[1],$newUserIds[2]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[3],$newUserIds[4]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[5],$newUserIds[6]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[1]', 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[7]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[2]', 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[8],$newUserIds[9]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'pm:assignedUsers'));
        $this->assertEquals("$newUserIds[10]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'pm:assignedUsers'));

        $this->assertEquals("$newGroupIds[0]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[1]', 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[1],$newGroupIds[2]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:task[2]', 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[3]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[1]', 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[4],$newGroupIds[5]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:manualTask[2]', 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[6],$newGroupIds[7],$newGroupIds[8]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[1]', 'pm:assignedGroups'));
        $this->assertEquals("$newGroupIds[9]", Utils::getAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:callActivity[2]', 'pm:assignedGroups'));
    }
}
