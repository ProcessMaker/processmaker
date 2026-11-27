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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\Processes\ProcessCatalog;
use ProcessMaker\Mcp\Processes\ProcessPlanner;

#[Name('plan-process')]
#[Title('Plan Process')]
#[Description('Process designer interview. Call with a goal before create-process when requirements are vague. Returns open_questions for the human (tasks, decisions, assignments, process manager, screens, start). Pass prior answers back on later calls. When ready=true, use the blueprint to build screens/BPMN. Use list-users and list-groups while resolving assignees.')]
#[IsReadOnly]
#[IsIdempotent]
class PlanProcessTool extends ProcessTool
{
    public function handle(
        Request $request,
        ProcessCatalog $processes,
        ProcessPlanner $planner,
    ): Response|ResponseFactory {
        return $this->respond(fn (): array => $planner->plan(
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
            'goal' => $schema->string()
                ->description('What the human wants to automate (free text). Required on every call.')
                ->required(),
            'name' => $schema->string()->description('Process name (once known).'),
            'purpose' => $schema->string()->description('Business purpose / description.'),
            'tasks' => $schema->string()->description('Ordered steps: name, type, data collected.'),
            'decisions' => $schema->string()->description('Gateways / branch conditions, or "none".'),
            'assignments' => $schema->string()->description('Per-task assignee type and user/group ids from list-users / list-groups.'),
            'process_manager' => $schema->string()->description('Process manager user id or username (when needed).'),
            'screens' => $schema->string()->description('Screens to reuse or create (title, FORM/DISPLAY, fields).'),
            'start' => $schema->string()->description('How the process starts (requester UI, web entry, timer, …).'),
        ];
    }
}
