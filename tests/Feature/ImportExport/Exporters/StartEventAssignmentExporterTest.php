<?php

namespace Tests\Feature\ImportExport\Exporters;

use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class StartEventAssignmentExporterTest extends TestCase
{
    use HelperTrait;

    private const START_EVENT = '/bpmn:definitions/bpmn:process/bpmn:startEvent';

    private const PROCESS_NAME = 'startEventAssignmentProcess';

    public function testStartEventGroupAssignmentResolvesWhenUuidAndNameMatchOnTarget(): void
    {
        $group = Group::factory()->create(['name' => 'Main']);
        $payload = $this->exportProcessWithStartEventGroup($group);

        Process::where('name', self::PROCESS_NAME)->forceDelete();

        $this->import($payload);

        $this->assertStartEventAssignedGroups((string) $group->id);
    }

    public function testStartEventGroupAssignmentResolvesByNameWhenUuidDiffersOnTarget(): void
    {
        $group = Group::factory()->create(['name' => 'Main']);
        $payload = $this->exportProcessWithStartEventGroup($group);

        Process::where('name', self::PROCESS_NAME)->forceDelete();
        $group->delete();
        $targetGroup = Group::factory()->create(['name' => 'Main']);

        $this->import($payload);

        $this->assertStartEventAssignedGroups((string) $targetGroup->id);
    }

    public function testStartEventGroupAssignmentDoesNotResolveWhenOnlyUuidMatchesOnTarget(): void
    {
        $group = Group::factory()->create(['name' => 'Main']);
        $payload = $this->exportProcessWithStartEventGroup($group);

        Process::where('name', self::PROCESS_NAME)->forceDelete();
        $group->update(['name' => 'Finance']);

        $this->import($payload);

        $this->assertStartEventAssignedGroups('');
    }

    public function testStartEventGroupAssignmentDoesNotResolveWhenUuidAndNameAreMissingOnTarget(): void
    {
        $group = Group::factory()->create(['name' => 'Main']);
        $payload = $this->exportProcessWithStartEventGroup($group);

        Process::where('name', self::PROCESS_NAME)->forceDelete();
        $group->delete();

        $this->import($payload);

        $this->assertStartEventAssignedGroups('');
    }

    private function exportProcessWithStartEventGroup(Group $group): array
    {
        $process = $this->createStartEventProcess();
        Utils::setAttributeAtXPath($process, self::START_EVENT, 'pm:assignment', 'group');
        Utils::setAttributeAtXPath($process, self::START_EVENT, 'pm:assignedGroups', (string) $group->id);
        Utils::setAttributeAtXPath($process, self::START_EVENT, 'pm:assignedUsers', '');
        $process->save();

        return $this->export($process, ProcessExporter::class, null, false);
    }

    private function createStartEventProcess(): Process
    {
        $this->addGlobalSignalProcess();

        return $this->createProcess('process-with-start-event-assignment', [
            'name' => self::PROCESS_NAME,
        ]);
    }

    private function assertStartEventAssignedGroups(string $expected): void
    {
        $process = Process::where('name', self::PROCESS_NAME)->firstOrFail();

        $this->assertEquals(
            $expected,
            Utils::getAttributeAtXPath($process, self::START_EVENT, 'pm:assignedGroups')
        );
    }
}
