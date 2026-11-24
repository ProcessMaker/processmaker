<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Processes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('update-process')]
#[Title('Update Process')]
#[Description('Updates a ProcessMaker process. Send only the fields that should change (name, description, status, process_category_id, case_title, manager_id, notifications, bpmn). BPMN edits save a draft on alternative A or B (see alternative, create_alternative_b). Everything except bpmn is process-level: it changes the live process (A and B share it) and never publishes a version.')]
class UpdateProcessTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $processes->update(
            $processes->actor($request->user()),
            $request->all(),
        ));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => ProcessFields::id($schema),
            'name' => ProcessFields::name($schema, false),
            'description' => ProcessFields::description($schema, false),
            'status' => ProcessFields::status($schema),
            'process_category_id' => ProcessFields::category($schema, false),
            'case_title' => ProcessFields::caseTitle($schema),
            'manager_id' => ProcessFields::managerIds($schema),
            'notifications' => ProcessFields::notifications($schema),
            'bpmn' => ProcessFields::bpmn($schema, false),
            'alternative' => ProcessFields::alternative($schema),
            'create_alternative_b' => ProcessFields::createAlternativeB($schema),
        ];
    }
}
