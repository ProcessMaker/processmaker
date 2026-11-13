<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Models\User;

/**
 * Interview loop for designing a process before create-process.
 * Returns open questions for the agent to ask the human, or a blueprint when ready.
 */
class ProcessPlanner
{
    private const VIEW = 'view-processes';

    /**
     * @var list<array{id: string, ask: string, why: string}>
     */
    private const QUESTIONS = [
        [
            'id' => 'name',
            'ask' => 'What should the process be named? (unique, letters/numbers/spaces/dashes)',
            'why' => 'Required by create-process.',
        ],
        [
            'id' => 'purpose',
            'ask' => 'In one or two sentences, what business outcome does this process deliver?',
            'why' => 'Becomes the process description and guides screen/task wording.',
        ],
        [
            'id' => 'tasks',
            'ask' => 'List each step in order. For each: name, type (form task / manual / script / service / none), and what data is collected or decided.',
            'why' => 'Maps to BPMN tasks and screens.',
        ],
        [
            'id' => 'decisions',
            'ask' => 'Where do paths split or join? For each decision: condition field/expression, and what happens on yes vs no (or each branch). Say "none" if linear.',
            'why' => 'Maps to exclusive/inclusive gateways and sequence-flow conditions.',
        ],
        [
            'id' => 'assignments',
            'ask' => 'For each human task, who works it? Prefer assignment types: requester, process_manager, user_group (group ids), or user (user ids). Call list-users / list-groups to show options and pick real ids.',
            'why' => 'Sets pm:assignment / assignedUsers / assignedGroups on Form Tasks.',
        ],
        [
            'id' => 'process_manager',
            'ask' => 'Who is the process manager (user id or username)? Required when any task uses process_manager assignment. Use list-users to pick one.',
            'why' => 'Process-level manager; used by pm:assignment="process_manager".',
        ],
        [
            'id' => 'screens',
            'ask' => 'For each form/manual/end summary: reuse an existing screen title, or create a new one (title + FORM vs DISPLAY + key fields)?',
            'why' => 'Screens must exist (or be created) before BPMN pm:screenRef.',
        ],
        [
            'id' => 'start',
            'ask' => 'How does the process start? (requester from Requests UI, web entry anonymous/authenticated, timer, signal). Any interstitial after start?',
            'why' => 'Configures Start Event and optional Web Entry.',
        ],
    ];

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function plan(User $user, array $input): array
    {
        $this->assertCanView($user);

        $goal = trim((string) ($input['goal'] ?? ''));
        $answers = $this->collectAnswers($input);
        $open = $this->openQuestions($answers);
        $ready = $open === [];

        return [
            'ready' => $ready,
            'goal' => $goal,
            'agent_instructions' => $ready
                ? 'Blueprint is complete enough to design screens (if needed), then author BPMN and call validate-process before create-process. Follow the pm4-process skill (and the connector skills you need). Use list-users / list-groups if any assignment id is still unclear.'
                : 'Do not call create-process yet. Ask the human the open_questions (batch a few related ones). After they answer, call plan-process again with the same goal and filled answer fields. Use list-users and list-groups when discussing assignees so the human can pick real people/groups.',
            'open_questions' => $open,
            'answered' => $answers,
            'assignment_types' => [
                'requester',
                'process_manager',
                'user_group',
                'user',
                'previous_task_assignee',
                'process_variable',
                'rule_expression',
            ],
            'helper_tools' => [
                'list-users' => 'Search active users (id, username, name, email) for user / process_manager assignment.',
                'list-groups' => 'Search groups (id, name) for user_group assignment (pm:assignedGroups).',
                'list-screens' => 'On the Screens MCP server — search before creating screens.',
                'list-processes' => 'Search before creating a duplicate process name.',
            ],
            'blueprint' => $ready ? $this->blueprint($goal, $answers) : null,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function collectAnswers(array $input): array
    {
        $answers = [];
        foreach (self::QUESTIONS as $question) {
            $id = $question['id'];
            $value = trim((string) ($input[$id] ?? ''));
            if ($value !== '') {
                $answers[$id] = $value;
            }
        }

        return $answers;
    }

    /**
     * @param array<string, string> $answers
     * @return list<array{id: string, ask: string, why: string}>
     */
    private function openQuestions(array $answers): array
    {
        $open = [];
        foreach (self::QUESTIONS as $question) {
            if (!isset($answers[$question['id']])) {
                $open[] = $question;
            }
        }

        // process_manager only required when assignments mention process_manager
        if ($this->needsProcessManager($answers) === false) {
            $open = array_values(array_filter(
                $open,
                static fn (array $q): bool => $q['id'] !== 'process_manager',
            ));
        }

        return $open;
    }

    /**
     * @param array<string, string> $answers
     */
    private function needsProcessManager(array $answers): bool
    {
        $assignments = strtolower($answers['assignments'] ?? '');
        $tasks = strtolower($answers['tasks'] ?? '');

        return str_contains($assignments, 'process_manager')
            || str_contains($tasks, 'process_manager')
            || str_contains($assignments, 'process manager');
    }

    /**
     * @param array<string, string> $answers
     * @return array<string, mixed>
     */
    private function blueprint(string $goal, array $answers): array
    {
        return [
            'name' => $answers['name'],
            'description' => $answers['purpose'],
            'goal' => $goal,
            'start' => $answers['start'],
            'tasks' => $answers['tasks'],
            'decisions' => $answers['decisions'],
            'assignments' => $answers['assignments'],
            'process_manager' => $answers['process_manager'] ?? null,
            'screens' => $answers['screens'],
            'next_steps' => [
                'Create or reuse screens listed under screens (Screens MCP).',
                'Author BPMN with numeric pm:screenRef (from create-screen/list-screens ids — MCP does not resolve ${screen:title}) and assignment attrs from assignments.',
                'Call validate-process, then create-process (or update-process).',
                'If process_manager is set, ensure the process manager user is assigned on the process after create (Modeler / process settings).',
            ],
        ];
    }

    private function assertCanView(User $user): void
    {
        if ($user->can(self::VIEW)) {
            return;
        }

        throw new AuthorizationException('You do not have the ' . self::VIEW . ' permission.');
    }
}
