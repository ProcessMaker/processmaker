<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Package\Versions\Models\VersionHistory;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DeployProcessControllerTest extends TestCase
{
    use RequestHelper;

    private const DEPLOY_ENDPOINT = '/package-skeleton/deploy-process';

    private const GET_ENDPOINT = '/package-skeleton/process-bpmn';

    public function testDeployProcessResolvesNamedReferences(): void
    {
        $screen = Screen::factory()->create(['title' => 'sample-screen-ref']);
        $script = Script::factory()->create(['title' => 'sample-script-ref']);

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'sample-process',
            'bpmn' => $this->sampleBpmn(
                '${screen:sample-screen-ref}',
                '${script:sample-script-ref}'
            ),
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => 'sample-process',
            ]);

        $process = Process::where('name', 'sample-process')->firstOrFail();
        $this->assertStringContainsString('pm:screenRef="' . $screen->id . '"', $process->bpmn);
        $this->assertStringContainsString('pm:scriptRef="' . $script->id . '"', $process->bpmn);
    }

    public function testDeployProcessAcceptsDescription(): void
    {
        $summary = 'Brief summary for the process catalog.';

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'described-process',
            'description' => $summary,
            'bpmn' => $this->sampleBpmn('', ''),
        ])->assertOk();

        $process = Process::where('name', 'described-process')->firstOrFail();
        $this->assertSame($summary, $process->description);

        $updated = $summary . ' Updated.';
        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'described-process',
            'description' => $updated,
            'bpmn' => $this->sampleBpmn('', ''),
            'version_name' => 'Update process description',
        ])->assertOk();

        $this->assertSame($updated, $process->refresh()->description);
    }

    public function testDeployProcessUpdateRequiresVersionName(): void
    {
        $name = 'version-name-process';

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('', ''),
        ])->assertOk();

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('', ''),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['version_name']);
    }

    public function testDeployProcessUpdateStoresVersionName(): void
    {
        if (!class_exists(VersionHistory::class)) {
            $this->markTestSkipped('package-versions is not installed.');
        }

        $name = 'version-name-process-stored';
        $summary = 'Adjust BPMN layout';

        Screen::factory()->create(['title' => 'vn-screen']);

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('', ''),
        ])->assertOk();

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('${screen:vn-screen}', ''),
            'version_name' => $summary,
        ])->assertOk();

        $process = Process::where('name', $name)->firstOrFail();
        $version = $process->versions()->orderByDesc('id')->first();
        $this->assertInstanceOf(ProcessVersion::class, $version);

        $history = VersionHistory::query()
            ->where('versionable_id', $version->id)
            ->where('versionable_type', ProcessVersion::class)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame($summary, $history->subject);
    }

    public function testGetProcessBpmnRestoresNamedReferences(): void
    {
        Screen::factory()->create(['title' => 'restore-screen']);
        Script::factory()->create(['title' => 'restore-script']);

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'restore-process',
            'bpmn' => $this->sampleBpmn(
                '${screen:restore-screen}',
                '${script:restore-script}'
            ),
        ])->assertOk();

        $response = $this->apiCall('GET', self::GET_ENDPOINT . '?name=restore-process');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('${screen:restore-screen}', $response->json('bpmn'));
        $this->assertStringContainsString('${script:restore-script}', $response->json('bpmn'));
    }

    public function testDeployAlternativeBWritesDraftAndKeepsLiveProcess(): void
    {
        $this->requireAbTesting();
        $name = 'alt-b-draft-process';
        $screen = Screen::factory()->create(['title' => 'alt-b-screen']);

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('', ''),
        ])->assertOk();
        $process = Process::where('name', $name)->firstOrFail();
        $liveBpmn = $process->bpmn;

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'alternative' => 'B',
            'bpmn' => $this->sampleBpmn('${screen:alt-b-screen}', ''),
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'action' => 'draft', 'alternative' => 'B']);

        $this->assertSame($liveBpmn, $process->refresh()->bpmn, 'Alternative A must not change.');
        $draft = $process->versions()->where('draft', true)->where('alternative', 'B')->firstOrFail();
        $this->assertStringContainsString('pm:screenRef="' . $screen->id . '"', $draft->bpmn);
    }

    public function testDeployAlternativeBPublishLinksVersionToAbRecord(): void
    {
        $this->requireAbTesting();
        $name = 'alt-b-publish-process';

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'bpmn' => $this->sampleBpmn('', ''),
        ])->assertOk();
        $process = Process::where('name', $name)->firstOrFail();
        $liveBpmn = $process->bpmn;

        $response = $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => $name,
            'alternative' => 'B',
            'publish' => true,
            'bpmn' => str_replace('Task_1', 'Task_B', $this->sampleBpmn('', '')),
        ])->assertOk()->assertJson(['action' => 'published', 'alternative' => 'B']);

        $this->assertSame($liveBpmn, $process->refresh()->bpmn, 'Alternative A must not change.');
        $version = ProcessVersion::findOrFail($response->json('process_version_id'));
        $this->assertSame('B', $version->alternative);
        $this->assertFalse((bool) $version->draft);
        $this->assertStringContainsString('Task_B', $version->bpmn);

        $alternativeModel = 'ProcessMaker\Package\PackageABTesting\Models\Alternative';
        $record = $alternativeModel::where('process_id', $process->id)->firstOrFail();
        $this->assertSame($version->id, (int) $record->process_version_b_id);
    }

    public function testDeployAlternativeBRequiresExistingProcess(): void
    {
        $this->requireAbTesting();

        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'alt-b-missing-process',
            'alternative' => 'B',
            'bpmn' => $this->sampleBpmn('', ''),
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNull(Process::where('name', 'alt-b-missing-process')->first());
    }

    public function testDeployRejectsUnknownAlternative(): void
    {
        $this->apiCall('POST', self::DEPLOY_ENDPOINT, [
            'name' => 'alt-unknown-process',
            'alternative' => 'C',
            'bpmn' => $this->sampleBpmn('', ''),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['alternative']);
    }

    private function requireAbTesting(): void
    {
        if (!class_exists('ProcessMaker\Package\PackageABTesting\Models\Alternative')) {
            $this->markTestSkipped('package-ab-testing is not installed.');
        }
    }

    private function sampleBpmn(string $screenReference, string $scriptReference): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions
  xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1">
  <bpmn:process id="Process_1" name="Test Process">
    <bpmn:task id="Task_1" pm:screenRef="{$screenReference}" />
    <bpmn:scriptTask id="ScriptTask_1" pm:scriptRef="{$scriptReference}" />
  </bpmn:process>
</bpmn:definitions>
XML;
    }
}
