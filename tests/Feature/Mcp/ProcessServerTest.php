<?php

namespace Tests\Feature\Mcp;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use ProcessMaker\Events\ProcessCreated;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Resources\McpSkillResource;
use ProcessMaker\Mcp\Servers\ProcessServer;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Mcp\Tools\GetMcpSkillTool;
use ProcessMaker\Mcp\Tools\Processes\CreateProcessTool;
use ProcessMaker\Mcp\Tools\Processes\LayoutBpmnTool;
use ProcessMaker\Mcp\Tools\Processes\GetProcessTool;
use ProcessMaker\Mcp\Tools\Processes\ListGroupsTool;
use ProcessMaker\Mcp\Tools\Processes\ListProcessesTool;
use ProcessMaker\Mcp\Tools\Processes\ListUsersTool;
use ProcessMaker\Mcp\Tools\Processes\PlanProcessTool;
use ProcessMaker\Mcp\Tools\Processes\UpdateProcessTool;
use ProcessMaker\Mcp\Tools\Processes\ValidateProcessTool;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProcessServerTest extends TestCase
{
    use RequestHelper;

    public function testAdminCanCreateListUpdateAndGetAProcess(): void
    {
        Event::fake([ProcessCreated::class]);

        $category = ProcessCategory::factory()->create();
        $bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');

        $created = ProcessServer::actingAs($this->user)->tool(CreateProcessTool::class, [
            'name' => 'MCP Intake Flow',
            'description' => 'Created via MCP',
            'process_category_id' => (string) $category->id,
            'bpmn' => $bpmn,
        ]);

        $created->assertOk()
            ->assertHasNoErrors()
            ->assertSee('MCP Intake Flow')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('name', 'MCP Intake Flow')
                ->where('description', 'Created via MCP')
                ->whereType('bpmn', 'string')
                ->etc());

        $process = Process::where('name', 'MCP Intake Flow')->firstOrFail();
        Event::assertDispatched(ProcessCreated::class);

        ProcessServer::actingAs($this->user)->tool(ListProcessesTool::class, [
            'filter' => 'MCP Intake',
        ])->assertOk()->assertSee('MCP Intake Flow');

        ProcessServer::actingAs($this->user)->tool(GetProcessTool::class, [
            'id' => $process->id,
        ])->assertOk()->assertSee('Created via MCP');

        ProcessServer::actingAs($this->user)->tool(UpdateProcessTool::class, [
            'id' => $process->id,
            'description' => 'Updated via MCP',
        ])->assertOk()->assertSee('Updated via MCP');

        $this->assertSame('Updated via MCP', $process->refresh()->description);
    }

    public function testProcessToolsRequireAnAuthenticatedUserWithPermission(): void
    {
        ProcessServer::tool(ListProcessesTool::class)
            ->assertHasErrors(['Authenticate with a ProcessMaker API token']);

        $member = User::factory()->create([
            'is_administrator' => false,
        ]);

        ProcessServer::actingAs($member)->tool(ListProcessesTool::class)
            ->assertHasErrors(['view-processes']);
    }

    public function testPlanProcessReturnsOpenQuestionsThenBlueprint(): void
    {
        ProcessServer::actingAs($this->user)->tool(PlanProcessTool::class, [
            'goal' => 'Employee leave request with manager approval',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('ready', false)
                ->whereType('open_questions', 'array')
                ->where('open_questions.0.id', 'name')
                ->etc());

        ProcessServer::actingAs($this->user)->tool(PlanProcessTool::class, [
            'goal' => 'Employee leave request with manager approval',
            'name' => 'Leave Request',
            'purpose' => 'Employees request PTO; managers approve or reject.',
            'tasks' => '1) Submit request (form) 2) Manager review (form)',
            'decisions' => 'After review: Approved vs Rejected on approval_status',
            'assignments' => 'Submit=requester; Review=process_manager',
            'process_manager' => 'admin',
            'screens' => 'Create FORM submit + FORM approval + DISPLAY summary',
            'start' => 'Requester from Requests UI with interstitial',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('ready', true)
                ->where('blueprint.name', 'Leave Request')
                ->whereType('blueprint.next_steps', 'array')
                ->etc());
    }

    public function testListUsersAndGroupsForAssignment(): void
    {
        $peer = User::factory()->create([
            'username' => 'pto.manager',
            'firstname' => 'Pto',
            'lastname' => 'Manager',
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);
        $group = Group::factory()->create([
            'name' => 'PTO Approvers',
            'status' => 'ACTIVE',
        ]);

        ProcessServer::actingAs($this->user)->tool(ListUsersTool::class, [
            'filter' => 'pto.manager',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.0.id', $peer->id)
                ->where('data.0.username', 'pto.manager')
                ->etc());

        ProcessServer::actingAs($this->user)->tool(ListGroupsTool::class, [
            'filter' => 'PTO Approvers',
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.0.id', $group->id)
                ->where('data.0.name', 'PTO Approvers')
                ->etc());
    }

    public function testValidateProcessReportsMissingScreenRef(): void
    {
        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
    xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
    xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
    xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
    targetNamespace="http://bpmn.io/schema/bpmn"
    exporter="ProcessMaker Modeler" exporterVersion="1.0">
  <bpmn:process id="ProcessId" name="Bad Task" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start">
      <bpmn:outgoing>node_4</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Form Task" pm:assignment="requester">
      <bpmn:incoming>node_4</bpmn:incoming>
      <bpmn:outgoing>node_5</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3" name="End">
      <bpmn:incoming>node_5</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_4" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_5" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagramId">
    <bpmndi:BPMNPlane id="BPMNPlaneId" bpmnElement="ProcessId" />
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        ProcessServer::actingAs($this->user)->tool(ValidateProcessTool::class, [
            'bpmn' => $bpmn,
        ])->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('ok', false)
                ->where('errors.0.code', 'missing_screen_ref')
                ->etc());
    }

    public function testHttpServerRequiresABearerToken(): void
    {
        $this->postJson('/mcp/processes', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ])->assertUnauthorized();
    }

    public function testGetMcpGuidanceReturnsProcessScopedCatalog(): void
    {
        $this->seedMcpGuidance();

        ProcessServer::actingAs($this->user)
            ->tool(GetMcpGuidanceTool::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee([
                'Prefer clear process names',
                'Process creation rules',
            ])
            ->assertDontSee('screen-creation-rules')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('system_prompt', 'Prefer clear process names')
                ->where('skills.0.slug', 'process-creation-rules')
                ->missing('skills.0.content')
                ->etc());
    }

    public function testGetMcpSkillReturnsFullSkillBodyBySlug(): void
    {
        $this->seedMcpGuidance();

        ProcessServer::actingAs($this->user)
            ->tool(GetMcpSkillTool::class, ['slug' => 'process-creation-rules'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Author BPMN carefully')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('slug', 'process-creation-rules')
                ->where('content', 'Author BPMN carefully')
                ->where('uri', 'pm://mcp/skills/process-creation-rules')
                ->etc());
    }

    /**
     * @group requires-node
     */
    public function testLayoutBpmnToolAppliesAutoLayout(): void
    {
        $this->assumeBpmnLayoutAvailable();

        $bpmn = file_get_contents(base_path('tools/mcp/bpmn-auto-layout/fixtures/linear.bpmn'));
        $this->assertNotFalse($bpmn);

        ProcessServer::actingAs($this->user)->tool(LayoutBpmnTool::class, [
            'bpmn' => $bpmn,
        ])->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function (AssertableJson $json): void {
                $json->where('layout_engine', 'bpmn-auto-layout-extended')
                    ->whereType('bpmn', 'string')
                    ->whereType('warnings', 'array');

                $out = $json->toArray();
                $this->assertStringContainsString('pm:screenRef="1"', $out['bpmn'] ?? '');
                preg_match_all('/dc:Bounds x="(\d+)"/', $out['bpmn'] ?? '', $matches);
                $xs = array_map('intval', $matches[1] ?? []);
                $this->assertGreaterThanOrEqual(3, count($xs));
                $this->assertTrue($xs[1] > $xs[0] && $xs[2] > $xs[1]);
                $json->etc();
            });
    }

    /**
     * @group requires-node
     */
    public function testCreateProcessWithoutBpmnEdgesStillAutoLayoutsBeforeSave(): void
    {
        $this->assumeBpmnLayoutAvailable();
        Event::fake([ProcessCreated::class]);

        $bpmn = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" isExecutable="true">
    <bpmn:startEvent id="node_1"><bpmn:outgoing>flow_1</bpmn:outgoing></bpmn:startEvent>
    <bpmn:task id="node_2" pm:screenRef="1" pm:assignment="requester">
      <bpmn:incoming>flow_1</bpmn:incoming>
      <bpmn:outgoing>flow_2</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3"><bpmn:incoming>flow_2</bpmn:incoming></bpmn:endEvent>
    <bpmn:sequenceFlow id="flow_1" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="flow_2" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="shape_1" bpmnElement="node_1"><dc:Bounds x="0" y="0" width="36" height="36" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_2" bpmnElement="node_2"><dc:Bounds x="0" y="0" width="116" height="76" /></bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="shape_3" bpmnElement="node_3"><dc:Bounds x="0" y="0" width="36" height="36" /></bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
XML;

        ProcessServer::actingAs($this->user)->tool(CreateProcessTool::class, [
            'name' => 'MCP forced layout edges',
            'description' => 'Shapes only input',
            'bpmn' => $bpmn,
            'auto_layout_bpmn' => false,
        ])->assertOk()->assertHasNoErrors()
            ->assertStructuredContent(function (AssertableJson $json): void {
                $stored = $json->toArray()['bpmn'] ?? '';
                $this->assertGreaterThanOrEqual(2, substr_count($stored, 'bpmndi:BPMNEdge'));
                $this->assertGreaterThanOrEqual(4, substr_count($stored, 'di:waypoint'));
                $json->etc();
            });
    }

    /**
     * @group requires-node
     */
    public function testCreateProcessAutoLayoutSpreadsDiagramCoordinates(): void
    {
        $this->assumeBpmnLayoutAvailable();
        Event::fake([ProcessCreated::class]);

        $bpmn = file_get_contents(base_path('tools/mcp/bpmn-auto-layout/fixtures/linear.bpmn'));
        $this->assertNotFalse($bpmn);

        $created = ProcessServer::actingAs($this->user)->tool(CreateProcessTool::class, [
            'name' => 'MCP ELK Layout Flow',
            'description' => 'Auto layout test',
            'bpmn' => $bpmn,
            'auto_layout_bpmn' => true,
        ]);

        $created->assertOk()->assertHasNoErrors()
            ->assertStructuredContent(function (AssertableJson $json): void {
                $payload = $json->toArray();
                preg_match_all('/dc:Bounds x="(\d+)"/', $payload['bpmn'] ?? '', $matches);
                $xs = array_map('intval', $matches[1] ?? []);
                $this->assertTrue($xs[1] > $xs[0] && $xs[2] > $xs[1]);
                $json->etc();
            });
    }

    public function testServerInstructionsIncludeProcessCatalogWithoutSkillBodies(): void
    {
        $this->seedMcpGuidance();

        $server = app(ProcessServer::class, ['transport' => new FakeTransporter()]);
        $instructions = $server->createContext()->instructions;

        $this->assertStringContainsString('get-mcp-guidance', $instructions);
        $this->assertStringContainsString('validate-process', $instructions);
        $this->assertStringContainsString('layout-bpmn', $instructions);
        $this->assertStringContainsString('Prefer clear process names', $instructions);
        $this->assertStringContainsString('process-creation-rules', $instructions);
        $this->assertStringNotContainsString('Author BPMN carefully', $instructions);
        $this->assertStringNotContainsString('screen-creation-rules', $instructions);
    }

    public function testSkillResourcesAreProcessScoped(): void
    {
        $this->seedMcpGuidance();

        $server = app(ProcessServer::class, ['transport' => new FakeTransporter()]);
        $resources = $server->createContext()->resources();

        $this->assertCount(1, $resources);
        $this->assertInstanceOf(McpSkillResource::class, $resources->first());
        $this->assertSame('pm://mcp/skills/process-creation-rules', $resources->first()->uri());
    }

    private function assumeBpmnLayoutAvailable(): void
    {
        $script = config('mcp.bpmn_layout.script_path');
        if (!is_string($script) || !is_file($script)) {
            $this->markTestSkipped('BPMN auto-layout script is not installed.');
        }

        $node = config('mcp.bpmn_layout.node_binary', 'node');
        $check = new \Symfony\Component\Process\Process([$node, '--version']);
        try {
            $check->mustRun();
        } catch (\Throwable) {
            $this->markTestSkipped('Node.js is not available for BPMN layout.');
        }
    }

    private function seedMcpGuidance(): void
    {
        Setting::factory()->create([
            'key' => McpGuidance::SYSTEM_PROMPT_KEY,
            'format' => 'textarea',
            'config' => 'Prefer clear process names',
            'name' => 'System Prompt',
            'group' => 'MCP',
        ]);

        Setting::factory()->create([
            'key' => McpGuidance::SKILLS_KEY,
            'format' => 'component',
            'config' => [
                [
                    'id' => 'mcp-system-screen-creation-rules',
                    'name' => 'Screen creation rules',
                    'slug' => 'screen-creation-rules',
                    'when' => 'Before create-screen',
                    'content' => 'Use Vue mounts',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_SCREENS,
                ],
                [
                    'id' => 'mcp-system-process-creation-rules',
                    'name' => 'Process creation rules',
                    'slug' => 'process-creation-rules',
                    'when' => 'Before create-process',
                    'content' => 'Author BPMN carefully',
                    'system' => true,
                    'scope' => McpGuidance::SCOPE_PROCESSES,
                ],
            ],
            'name' => 'Skills',
            'group' => 'MCP',
            'ui' => ['component' => 'McpSkills'],
        ]);
    }
}
