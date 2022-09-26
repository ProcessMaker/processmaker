<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class ProcessExporter extends ExporterBase
{
    public ExportManager $manager;

    public function export() : void
    {
        $process = $this->model;

        $this->manager = resolve(ExportManager::class);

        $this->addDependent('user', $process->user, UserExporter::class);

        // Process Categories.
        $this->exportCategories();

        // Notification Settings.
        $this->addReference('notification_settings', $process->notification_settings->toArray());

        // Screens.
        foreach ($this->getScreens($process) as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }

        // Subprocesses.
        foreach ($this->getSubprocesses($process) as $subProcess) {
            $this->addDependent(DependentType::SUB_PROCESSES, $subProcess, self::class);
        }

        // Task Assignments.
        $taskAssignments = $process->assignments;
        $this->addReference('task_assignments', $taskAssignments->toArray());
        $taskAssignmentUsers = $taskAssignments->filter(fn ($t) => $t->assignment_type === User::class)->map->assigned;
        foreach ($taskAssignmentUsers as $user) {
            $this->addDependent('task_assignments_users', $user, UserExporter::class);
        }
        $taskAssignmentGroups = $taskAssignments->filter(fn ($t) => $t->assignment_type === Group::class)->map->assigned;
        foreach ($taskAssignmentGroups as $group) {
            $this->addDependent('task_assignments_groups', $group, GroupExporter::class);
        }
    }

    public function import() : bool
    {
        $process = $this->model;

        $process->user_id = $this->getDependents('user')[0]->model->id;

        $this->associateCategories(ProcessCategory::class, 'process_category_id');

        // TODO
        // Update screenRef
        $this->associateSubProcesses($process);

        $process->bpmn = $process->getDefinitions()->saveXML();
        $process->save();

        $process->notification_settings()->delete();
        foreach ($this->getReference('notification_settings') as $setting) {
            unset($setting['process_id']);
            $process->notification_settings()->create($setting);
        }

        $this->associateTaskAssignments($process);

        return true;
    }

    private function getScreens($process): Collection
    {
        $ids = array_merge(
            [
                $process->cancel_screen_id,
                $process->request_detail_screen_id,
            ],
            $this->manager->getDependenciesOfType(Screen::class, $process, [], false)
        );

        return Screen::findMany($ids);
    }

    private function getSubprocesses($process): Collection
    {
        $ids = [];
        $elements = Utils::getSubprocesses($process);
        foreach ($elements as $element) {
            $calledElementValue = $element->getAttributeNode('calledElement')->value;
            $values = explode('-', $calledElementValue);
            $ids[] = $values[1];
        }

        return Process::findMany($ids);
    }

    private function associateSubProcesses($process): void
    {
        $dependents = collect($this->getDependents(DependentType::SUB_PROCESSES));
        $elements = Utils::getSubprocesses($process);
        foreach ($elements as $element) {
            $calledElementValue = $element->getAttributeNode('calledElement')->value;
            $values = explode('-', $calledElementValue);
            $dependent = $dependents->first(function ($dependent) use ($values) {
                return $dependent->originalId === (int) $values[1];
            });
            $newId = $dependent->model->id;
            $value = "ProcessId-{$newId}";
            $element->setAttribute('calledElement', $value);
            Utils::setPmConfigValue($element, 'calledElement', $value);
            Utils::setPmConfigValue($element, 'processId', $newId);
        }
    }

    private function associateTaskAssignments($process): void
    {
        $process->assignments()->delete();
        $taskAssignmentDependents = collect($this->getDependents('task_assignments_users'))->merge($this->getDependents('task_assignments_groups'));
        foreach ($this->getReference('task_assignments') as $taskAssignment) {
            unset($taskAssignment['process_id']);
            $taskAssignment['assignment_id'] = $this->associateAssignmentId($taskAssignment, $taskAssignmentDependents);
            $process->assignments()->create($taskAssignment);
        }
    }

    private function associateAssignmentId(array $taskAssignment, Collection $dependents): int
    {
        return $dependents->first(function ($dependent) use ($taskAssignment) {
            return $dependent->originalId === $taskAssignment['assignment_id']
                && get_class($dependent->model) === $taskAssignment['assignment_type'];
        })->model->id;
    }
}
