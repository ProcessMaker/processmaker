<?php

namespace Tests\Feature\ImportExport;

use ProcessMaker\ImportExport\AssignmentRulesReconciliation;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

class AssignmentRulesReconciliationTest extends TestCase
{
    use HelperTrait;

    private const TASK_1 = '/bpmn:definitions/bpmn:process/bpmn:task[1]';

    public function testReconcileUserByUsernameInAssignmentNameNotByStaleAssigneeId(): void
    {
        $target = User::factory()->create(['username' => 'ignacio']);

        $rule = (object) [
            'type' => 'user',
            'assignee' => 999,
            'expression' => 'x==true',
            'assignmentName' => 'ignacio aranda (ignacio)',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertSame($target->id, $reconciled->assignee);
        $this->assertFalse(property_exists($reconciled, 'unresolved'));
    }

    public function testReconcileDoesNotBindWhenStaleIdBelongsToDifferentUser(): void
    {
        $wrongIdUser = User::factory()->create(['username' => 'wronguser']);
        $correctUser = User::factory()->create(['username' => 'ignacio']);

        $rule = (object) [
            'type' => 'user',
            'assignee' => $wrongIdUser->id,
            'expression' => 'x==true',
            'assignmentName' => 'ignacio aranda (ignacio)',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertSame($correctUser->id, $reconciled->assignee);
    }

    public function testReconcileUserWithInactiveSuffixInAssignmentName(): void
    {
        $target = User::factory()->create(['username' => 'ignacio', 'status' => 'INACTIVE']);

        $rule = (object) [
            'type' => 'user',
            'assignee' => 999,
            'expression' => 'x==true',
            'assignmentName' => 'ignacio aranda (ignacio - Inactive)',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertSame($target->id, $reconciled->assignee);
    }

    public function testReconcileMarksUnresolvedWhenUsernameNotFound(): void
    {
        $rule = (object) [
            'type' => 'user',
            'assignee' => 6,
            'expression' => 'x==true',
            'assignmentName' => 'ignacio aranda (ignacio)',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertNull($reconciled->assignee);
        $this->assertTrue($reconciled->unresolved);
    }

    public function testReconcileMarksUnresolvedWhenAssignmentNameHasNoUsername(): void
    {
        User::factory()->create(['username' => 'ignacio']);

        foreach ([null, 'Ignacio Aranda', '()'] as $assignmentName) {
            $rule = (object) [
                'type' => 'user',
                'assignee' => 6,
                'expression' => 'x==true',
                'assignmentName' => $assignmentName,
            ];

            $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

            $this->assertNull($reconciled->assignee);
            $this->assertTrue($reconciled->unresolved);
        }
    }

    public function testReconcileGroupByAssignmentName(): void
    {
        $group = Group::factory()->create(['name' => 'Accounting']);

        $rule = (object) [
            'type' => 'group',
            'assignee' => 999,
            'expression' => 'x==true',
            'assignmentName' => 'Accounting',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertSame($group->id, $reconciled->assignee);
        $this->assertFalse(property_exists($reconciled, 'unresolved'));
    }

    public function testReconcileGroupUnresolvedWhenNameIsAmbiguousOrMissing(): void
    {
        Group::factory()->create(['name' => 'Accounting']);
        Group::factory()->create(['name' => 'Accounting']);

        $rule = (object) [
            'type' => 'group',
            'assignee' => 999,
            'expression' => 'x==true',
            'assignmentName' => 'Accounting',
        ];

        $reconciled = AssignmentRulesReconciliation::reconcileRuleAfterImport($rule);

        $this->assertNull($reconciled->assignee);
        $this->assertTrue($reconciled->unresolved);
    }

    public function testUsernameFromAssignmentName(): void
    {
        $this->assertSame('ignacio', AssignmentRulesReconciliation::usernameFromAssignmentName('ignacio aranda (ignacio)'));
        $this->assertSame('agus', AssignmentRulesReconciliation::usernameFromAssignmentName('agus ara pelle (agus)'));
        $this->assertNull(AssignmentRulesReconciliation::usernameFromAssignmentName('Ignacio Aranda'));
        $this->assertNull(AssignmentRulesReconciliation::usernameFromAssignmentName(null));
    }

    public function testProcessExporterImportRebindsAssignmentRulesByUsername(): void
    {
        $sourceUser = User::factory()->create(['username' => 'ignacio']);
        $process = $this->createProcess('process-with-different-kinds-of-assignments', [
            'name' => 'V2AssignmentRulesImport',
        ]);
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignment', 'rule_expression');
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignmentRules', json_encode([
            [
                'type' => 'user',
                'assignee' => 999,
                'expression' => 'form_checkbox_1==true',
                'assignmentName' => 'ignacio aranda (ignacio)',
            ],
            [
                'type' => 'user',
                'assignee' => 47,
                'expression' => null,
                'assignmentName' => 'missing user (missinguser)',
                'default' => true,
            ],
        ]));
        $process->save();

        $targetUser = null;
        $this->runExportAndImport($process, ProcessExporter::class, function () use ($process, $sourceUser, &$targetUser) {
            $process->forceDelete();
            $sourceUser->forceDelete();
            $targetUser = User::factory()->create(['username' => 'ignacio']);
        });

        $imported = Process::where('name', 'V2AssignmentRulesImport')->firstOrFail();
        $raw = Utils::getAttributeAtXPath($imported, self::TASK_1, 'pm:assignmentRules');
        $rules = AssignmentRulesReconciliation::decodeRules($raw);

        $this->assertSame($targetUser->id, $rules[0]->assignee);
        $this->assertFalse(property_exists($rules[0], 'unresolved'));
        $this->assertNull($rules[1]->assignee);
        $this->assertTrue($rules[1]->unresolved);
    }
}
