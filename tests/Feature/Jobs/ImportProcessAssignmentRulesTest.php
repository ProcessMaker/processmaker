<?php

namespace Tests\Feature\Jobs;

use ProcessMaker\ImportExport\AssignmentRulesReconciliation;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class ImportProcessAssignmentRulesTest extends TestCase
{
    use HelperTrait;

    private const TASK_1 = '/bpmn:definitions/bpmn:process/bpmn:task[1]';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminUser();
    }

    public function testV1ImportRebindsAssignmentRulesByUsername(): void
    {
        $targetUser = User::factory()->create(['username' => 'ignacio']);

        $payload = $this->buildV1ProcessPackage([
            [
                'type' => 'user',
                'assignee' => 999,
                'expression' => 'form_checkbox_1==true',
                'assignmentName' => 'ignacio aranda (ignacio)',
            ],
        ]);

        $result = (new ImportProcess($payload))->handle();
        $imported = Process::findOrFail($result->process->id);
        $rules = $this->assignmentRulesOnProcess($imported);

        $this->assertSame($targetUser->id, $rules[0]->assignee);
        $this->assertFalse(property_exists($rules[0], 'unresolved'));
    }

    public function testV1ImportDoesNotBindByStaleAssigneeId(): void
    {
        $wrongUser = User::factory()->create(['username' => 'wronguser']);
        $correctUser = User::factory()->create(['username' => 'ignacio']);

        $payload = $this->buildV1ProcessPackage([
            [
                'type' => 'user',
                'assignee' => $wrongUser->id,
                'expression' => 'form_checkbox_1==true',
                'assignmentName' => 'ignacio aranda (ignacio)',
            ],
        ]);

        $result = (new ImportProcess($payload))->handle();
        $imported = Process::findOrFail($result->process->id);
        $rules = $this->assignmentRulesOnProcess($imported);

        $this->assertSame($correctUser->id, $rules[0]->assignee);
    }

    public function testV1ImportMarksUnresolvedWhenUsernameNotFound(): void
    {
        $payload = $this->buildV1ProcessPackage([
            [
                'type' => 'user',
                'assignee' => 6,
                'expression' => 'form_checkbox_1==true',
                'assignmentName' => 'ignacio aranda (ignacio)',
            ],
        ]);

        $result = (new ImportProcess($payload))->handle();
        $imported = Process::findOrFail($result->process->id);
        $rules = $this->assignmentRulesOnProcess($imported);

        $this->assertNull($rules[0]->assignee);
        $this->assertTrue($rules[0]->unresolved);
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     */
    private function buildV1ProcessPackage(array $rules): string
    {
        $process = $this->createProcess('process-with-different-kinds-of-assignments', [
            'name' => 'V1AssignmentRulesImport',
        ]);

        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignment', 'rule_expression');
        Utils::setAttributeAtXPath($process, self::TASK_1, 'pm:assignmentRules', json_encode($rules));
        $process->save();

        return json_encode([
            'type' => 'process_package',
            'version' => '1',
            'process' => (object) [
                'name' => 'V1AssignmentRulesImport',
                'description' => '',
                'status' => 'ACTIVE',
                'bpmn' => $process->bpmn,
                'created_at' => now()->toIso8601String(),
                'deleted_at' => null,
                'notifications' => (object) [],
            ],
            'process_categories' => [
                (object) [
                    'name' => 'Assignment Rules Import Category',
                    'status' => 'ACTIVE',
                    'created_at' => now()->toIso8601String(),
                ],
            ],
            'screens' => [],
            'scripts' => [],
        ]);
    }

    /**
     * @return array<int, object>
     */
    private function assignmentRulesOnProcess(Process $process): array
    {
        $raw = Utils::getAttributeAtXPath($process, self::TASK_1, 'pm:assignmentRules');

        return AssignmentRulesReconciliation::decodeRules($raw);
    }
}
