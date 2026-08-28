<?php

namespace ProcessMaker\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mustache_Engine;
use ProcessMaker\AssignmentRules\PreviousTaskAssignee;
use ProcessMaker\AssignmentRules\ProcessManagerAssigned;
use ProcessMaker\Exception\InvalidUserAssignmentException;
use ProcessMaker\Exception\TaskDoesNotHaveRequesterException;
use ProcessMaker\Exception\ThereIsNoProcessManagerAssignedException;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\FormalExpression;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Bpmn\Models\Activity;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;

/**
 * Flat SQL homologs for process execution hot paths (avoids Eloquent N+1 where safe).
 */
class ProcessExecutionRawRepository
{
    private ?PreviousTaskAssignee $previousTaskAssignee = null;

    private ?ProcessManagerAssigned $processManagerAssigned = null;

    /**
     * Analog to Process::getNextUser() — flat queries with the same assignment rules as the Eloquent path.
     */
    public function getNextUserRaw(Process $process, ActivityInterface $activity, ProcessRequestToken $token): ?User
    {
        $default = $activity instanceof ScriptTaskInterface
        || $activity instanceof ServiceTaskInterface ? 'script' : 'requester';
        $assignmentType = $activity->getProperty('assignment', $default);
        $config = json_decode($activity->getProperty('config', '{}'), true) ?: [];
        $escalateToManager = $config['escalateToManager'] ?? false;

        $definitionFlags = $this->resolveAssignmentLockAndSelfServiceFromDefinitions($token, $activity);
        $assignmentLock = $definitionFlags['assignmentLock'];
        $isSelfService = $definitionFlags['isSelfService'];

        $request = $token->getInstance();
        $requestId = (int) $request->getKey();
        $processId = (int) $process->getKey();

        if ($assignmentType === 'rule_expression') {
            $userByRuleId = $isSelfService ? null : $this->getNextUserByRuleRaw($process, $processId, $activity, $token);
            if ($userByRuleId !== null) {
                $userId = $process->scalateToManagerIfEnabled($userByRuleId, $activity, $token, $assignmentType);

                return $this->checkAssignmentRaw(
                    $process,
                    $processId,
                    $request,
                    $activity,
                    $assignmentType,
                    $escalateToManager,
                    $this->getUserByIdRaw($userId),
                    $token
                );
            }
        }

        if (filter_var($assignmentLock, FILTER_VALIDATE_BOOLEAN) === true) {
            $userId = $this->getLastUserAssignedToTaskRaw($processId, $activity->getId(), $requestId);
            if ($userId) {
                return $this->checkAssignmentRaw(
                    $process,
                    $processId,
                    $request,
                    $activity,
                    $assignmentType,
                    $escalateToManager,
                    $this->getUserByIdRaw($userId),
                    $token
                );
            }
        }

        switch ($assignmentType) {
            case 'user_group':
            case 'group':
                $userId = $this->getNextUserFromGroupAssignmentRaw($processId, $activity->getId());
                break;
            case 'user':
                $userId = $this->getNextUserAssignmentRaw($processId, $activity->getId());
                break;
            case 'user_by_id':
                $userId = $this->getNextUserFromVariableRaw($activity, $token);
                break;
            case 'process_variable':
                $userId = $this->getNextUserFromProcessVariableRaw($process, $processId, $activity, $token);
                break;
            case 'requester':
                $userId = $this->getRequesterUserIdRaw($activity, $token);
                break;
            case 'previous_task_assignee':
                $userId = $this->previousTaskAssignee()->getNextUser($activity, $token, $process, $request);
                break;
            case 'process_manager':
                $userId = $this->processManagerAssigned()->getNextUser($activity, $token, $process, $request);
                break;
            case 'manual':
            case 'self_service':
                $userId = null;
                break;
            case 'script':
            default:
                $userId = null;
        }

        if ($isSelfService && in_array($assignmentType, ['user_group', 'process_variable', 'rule_expression'], true)) {
            $userId = null;
        }

        $userId = $process->scalateToManagerIfEnabled($userId, $activity, $token, $assignmentType);

        return $this->checkAssignmentRaw(
            $process,
            $processId,
            $request,
            $activity,
            $assignmentType,
            $escalateToManager,
            $this->getUserByIdRaw($userId),
            $token
        );
    }

    public function taskHasDraftRaw(int $taskId): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1 AS found FROM task_drafts WHERE task_id = ? LIMIT 1',
            [$taskId]
        );
    }

    /**
     * Hydrate an Eloquent model from a raw DB row.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @return T
     */
    public function hydrateModelFromRowRaw(string $modelClass, object $row): Model
    {
        /** @var Model $model */
        $model = new $modelClass();
        $model->setRawAttributes((array) $row, true);
        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }

    /**
     * Analog to checkAssignment(), reusing a User already loaded via getUserByIdRaw().
     */
    private function checkAssignmentRaw(
        Process $process,
        int $processId,
        ProcessRequest $request,
        ActivityInterface $activity,
        $assignmentType,
        $escalateToManager,
        ?User $user = null,
        ?ProcessRequestToken $token = null
    ): ?User {
        $config = $activity->getProperty('config') ? json_decode($activity->getProperty('config'), true) : [];
        $selfServiceToggle = array_key_exists('selfService', $config ?? []) ? $config['selfService'] : false;
        $isSelfService = $selfServiceToggle || $assignmentType === 'self_service';

        if ($activity instanceof ScriptTaskInterface
            || $activity instanceof ServiceTaskInterface) {
            return $user;
        }
        if ($user === null) {
            if ($isSelfService && !$escalateToManager) {
                return null;
            }
            if ($token === null) {
                throw new ThereIsNoProcessManagerAssignedException($activity);
            }
            $userId = $this->processManagerAssigned()->getNextUser($activity, $token, $process, $request);
            if (!$userId) {
                throw new ThereIsNoProcessManagerAssignedException($activity);
            }
            $user = $this->getUserByIdRaw($userId);
        }

        return $user;
    }

    /**
     * Match Process::getNextUser() — assignmentLock and selfService from version BPMN element properties.
     */
    private function resolveAssignmentLockAndSelfServiceFromDefinitions(
        ProcessRequestToken $token,
        ActivityInterface $activity
    ): array {
        $definitions = $token->getInstance()->getVersionDefinitions();
        $element = $definitions->findElementById($activity->getId());
        $properties = $element?->getBpmnElementInstance()?->getProperties() ?? [];

        $assignmentLock = array_key_exists('assignmentLock', $properties) ? $properties['assignmentLock'] : false;
        $config = array_key_exists('config', $properties) ? json_decode($properties['config'], true) : [];
        $isSelfService = array_key_exists('selfService', $config ?? []) ? $config['selfService'] : false;

        return [
            'assignmentLock' => $assignmentLock,
            'isSelfService' => (bool) $isSelfService,
        ];
    }

    private function previousTaskAssignee(): PreviousTaskAssignee
    {
        return $this->previousTaskAssignee ??= new PreviousTaskAssignee();
    }

    private function processManagerAssigned(): ProcessManagerAssigned
    {
        return $this->processManagerAssigned ??= new ProcessManagerAssigned();
    }

    /**
     * Analog to User::find() — single flat query, no eager loads.
     */
    private function getUserByIdRaw(?int $userId): ?User
    {
        if (!$userId) {
            return null;
        }

        $row = DB::selectOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId]);
        if (!$row) {
            return null;
        }

        return $this->hydrateModelFromRowRaw(User::class, $row);
    }

    private function getRequesterUserIdRaw($activity, ProcessRequestToken $token): ?int
    {
        $processRequest = $token->getInstance();

        if ($activity instanceof Activity && !$processRequest->user_id) {
            throw new TaskDoesNotHaveRequesterException();
        }

        return $processRequest->user_id ? (int) $processRequest->user_id : null;
    }

    private function getLastUserAssignedToTaskRaw(int $processId, string $processTaskUuid, int $processRequestId): ?int
    {
        $row = DB::selectOne(
            'SELECT user_id FROM process_request_tokens WHERE process_id = ? AND element_id = ? AND process_request_id = ? ORDER BY created_at DESC LIMIT 1',
            [$processId, $processTaskUuid, $processRequestId]
        );

        return $row && $row->user_id ? (int) $row->user_id : null;
    }

    private function getNextUserFromGroupAssignmentRaw(int $processId, string $processTaskUuid, ?array $users = null): ?int
    {
        $row = DB::selectOne(
            'SELECT user_id FROM process_request_tokens WHERE process_id = ? AND element_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
            [$processId, $processTaskUuid]
        );
        if ($users === null) {
            $users = $this->getAssignableUserIdsRaw($processId, $processTaskUuid);
        }
        if (empty($users)) {
            return null;
        }
        sort($users);
        $lastUserId = $row && $row->user_id ? (int) $row->user_id : null;
        if ($lastUserId) {
            foreach ($users as $user) {
                if ($user > $lastUserId) {
                    return (int) $user;
                }
            }
        }

        return (int) $users[0];
    }

    private function getNextUserAssignmentRaw(int $processId, string $processTaskUuid, ?array $users = null): ?int
    {
        $row = DB::selectOne(
            'SELECT user_id FROM process_request_tokens WHERE process_id = ? AND element_id = ? ORDER BY created_at DESC LIMIT 1',
            [$processId, $processTaskUuid]
        );
        if ($users === null) {
            $users = $this->getAssignableUserIdsRaw($processId, $processTaskUuid);
        }
        if (empty($users)) {
            return null;
        }
        sort($users);
        $lastUserId = $row && $row->user_id ? (int) $row->user_id : null;
        if ($lastUserId) {
            foreach ($users as $user) {
                if ($user > $lastUserId) {
                    return (int) $user;
                }
            }
        }

        return (int) $users[0];
    }

    private function getAssignableUserIdsRaw(int $processId, string $processTaskUuid): array
    {
        $assignments = DB::select(
            'SELECT assignment_id, assignment_type FROM process_task_assignments WHERE process_id = ? AND process_task_id = ?',
            [$processId, $processTaskUuid]
        );

        $users = [];
        $groupIds = [];
        foreach ($assignments as $assignment) {
            if ($assignment->assignment_type === User::class) {
                $users[(int) $assignment->assignment_id] = (int) $assignment->assignment_id;
            } else {
                $groupIds[] = (int) $assignment->assignment_id;
            }
        }

        if ($groupIds) {
            $this->mergeGroupMemberUserIdsRaw($groupIds, $users);
        }

        return array_values($users);
    }

    private function mergeGroupMemberUserIdsRaw(array $groupIds, array &$users): void
    {
        $pending = array_values(array_unique(array_map('intval', $groupIds)));
        $visitedGroups = [];

        while ($pending) {
            $batch = array_values(array_diff($pending, $visitedGroups));
            if (empty($batch)) {
                break;
            }
            $visitedGroups = array_merge($visitedGroups, $batch);
            $pending = [];
            $placeholders = implode(',', array_fill(0, count($batch), '?'));

            $members = DB::select(
                "SELECT member_id, member_type FROM group_members WHERE group_id IN ($placeholders)",
                $batch
            );

            $subGroupIds = [];
            foreach ($members as $member) {
                if ($member->member_type === User::class) {
                    $users[(int) $member->member_id] = (int) $member->member_id;
                } elseif ($member->member_type === Group::class) {
                    $subGroupIds[] = (int) $member->member_id;
                }
            }

            if ($subGroupIds) {
                $subGroupIds = array_values(array_unique($subGroupIds));
                $groupPlaceholders = implode(',', array_fill(0, count($subGroupIds), '?'));
                $activeGroups = DB::select(
                    "SELECT id FROM groups WHERE id IN ($groupPlaceholders) AND status = ?",
                    array_merge($subGroupIds, ['ACTIVE'])
                );
                foreach ($activeGroups as $group) {
                    $pending[] = (int) $group->id;
                }
            }
        }

        if (empty($users)) {
            return;
        }

        $userIds = array_keys($users);
        $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
        $statusPlaceholders = implode(',', array_fill(0, count(Process::NOT_ASSIGNABLE_USER_STATUS), '?'));
        $activeRows = DB::select(
            "SELECT id FROM users WHERE id IN ($userPlaceholders) AND status NOT IN ($statusPlaceholders)",
            array_merge($userIds, Process::NOT_ASSIGNABLE_USER_STATUS)
        );
        $activeIds = array_flip(array_map(fn ($row) => (int) $row->id, $activeRows));
        $users = array_intersect_key($users, $activeIds);
    }

    private function getNextUserFromVariableRaw($activity, ProcessRequestToken $token): ?int
    {
        try {
            $userExpression = $activity->getProperty('assignedUsers');
            $dataManager = new DataManager();
            $instanceData = $dataManager->getData($token);
            $mustache = new Mustache_Engine();
            $userId = (int) $mustache->render($userExpression, $instanceData);
            if (!$this->getUserByIdRaw($userId)) {
                throw new InvalidUserAssignmentException($userExpression, $userId);
            }

            return $userId;
        } catch (Exception $exception) {
            return null;
        }
    }

    private function getNextUserFromProcessVariableRaw(
        Process $process,
        int $processId,
        $activity,
        ProcessRequestToken $token
    ): ?int {
        if ($token->getSelfServiceAttribute()) {
            return null;
        }

        $usersVariable = $activity->getProperty('assignedUsers');
        $groupsVariable = $activity->getProperty('assignedGroups');
        $dataManager = new DataManager();
        $instanceData = $dataManager->getData($token);

        $assignedUsers = $usersVariable ? feelExpression($usersVariable, $instanceData) : [];
        $assignedGroups = $groupsVariable ? feelExpression($groupsVariable, $instanceData) : [];

        if (!is_array($assignedUsers)) {
            $assignedUsers = [$assignedUsers];
        }
        if (!is_array($assignedGroups)) {
            $assignedGroups = [$assignedGroups];
        }

        $users = [];
        if ($assignedUsers) {
            $uniqueUsers = array_values(array_unique(array_map('intval', $assignedUsers)));
            $placeholders = implode(',', array_fill(0, count($uniqueUsers), '?'));
            $statusPlaceholders = implode(',', array_fill(0, count(Process::NOT_ASSIGNABLE_USER_STATUS), '?'));
            $activeRows = DB::select(
                "SELECT id FROM users WHERE id IN ($placeholders) AND status NOT IN ($statusPlaceholders)",
                array_merge($uniqueUsers, Process::NOT_ASSIGNABLE_USER_STATUS)
            );
            foreach ($activeRows as $row) {
                $users[(int) $row->id] = (int) $row->id;
            }

            $oooPlaceholders = implode(',', array_fill(0, count($uniqueUsers), '?'));
            $oooRows = DB::select(
                "SELECT delegation_user_id FROM users WHERE id IN ($oooPlaceholders) AND status = ? AND delegation_user_id IS NOT NULL",
                array_merge($uniqueUsers, ['OUT_OF_OFFICE'])
            );
            foreach ($oooRows as $row) {
                $users[(int) $row->delegation_user_id] = (int) $row->delegation_user_id;
            }
        }

        foreach ($assignedGroups as $groupId) {
            $this->mergeGroupMemberUserIdsRaw([(int) $groupId], $users);
        }

        return $this->getNextUserFromGroupAssignmentRaw($processId, $activity->getId(), array_values($users));
    }

    private function getNextUserByRuleRaw(
        Process $process,
        int $processId,
        $activity,
        ProcessRequestToken $token
    ): ?int {
        $assignmentRules = $activity->getProperty('assignmentRules', null);
        $instanceData = $token->getInstance()->getDataStore()->getData();

        if (!$assignmentRules || !$instanceData) {
            return null;
        }

        $list = json_decode($assignmentRules);
        $list = ($list === null) ? [] : $list;
        foreach ($list as $item) {
            $formalExp = new FormalExpression();
            $formalExp->setLanguage('FEEL');
            $formalExp->setBody($item->expression);
            if (!$formalExp($instanceData)) {
                continue;
            }

            switch ($item->type) {
                case 'user_group':
                    $users = [];
                    foreach ($item->assignee->users as $user) {
                        $users[$user] = $user;
                    }
                    foreach ($item->assignee->groups as $group) {
                        $this->mergeGroupMemberUserIdsRaw([(int) $group], $users);
                    }
                    $userId = $this->getNextUserFromGroupAssignmentRaw($processId, $activity->getId(), array_values($users));
                    break;
                case 'group':
                    $users = [];
                    $this->mergeGroupMemberUserIdsRaw([(int) $item->assignee], $users);
                    $userId = $this->getNextUserFromGroupAssignmentRaw($processId, $activity->getId(), array_values($users));
                    break;
                case 'user':
                    $userId = (int) $item->assignee;
                    break;
                case 'requester':
                    $userId = $this->getRequesterUserIdRaw($activity, $token);
                    break;
                case 'manual':
                case 'self_service':
                    $userId = null;
                    break;
                case 'user_by_id':
                    $mustache = new Mustache_Engine();
                    $userId = (int) $mustache->render($item->assignee, $instanceData);
                    break;
                case 'script':
                default:
                    $userId = null;
            }

            if (!$userId) {
                return null;
            }

            return $this->getUserByIdRaw((int) $userId)?->getKey();
        }

        return null;
    }
}
