<?php

namespace ProcessMaker\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaseTaskRepository
{
    protected string $table = '';

    public function __construct(private int $caseNumber, private $task)
    {
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * The function `updateCaseStartedTaskStatus` updates the status of a task in a case started record.
     */
    public function updateCaseStartedTaskStatus()
    {
        $this->setTable('cases_started');
        $this->updateTaskStatus();
    }

    /**
     * The function `updateCaseParticipatedTaskStatus` updates the status of a task in a case participated record.
     */
    public function updateCaseParticipatedTaskStatus()
    {
        $this->setTable('cases_participated');
        $this->updateTaskStatus();
    }

    /**
     * The function `updateTaskStatus` updates the status of a task in a case, handling exceptions and logging errors.
     *
     * @return If the case is not found, the function will log an error message and then return without performing any
     * further actions.
     */
    public function updateTaskStatus()
    {
        // Skip non-user tasks (e.g. script task, sub-process, etc.)
        // tasks column contains only user tasks
        $isUserTask = ($this->task->element_type ?? null) === 'task';
        if (!$isUserTask) {
            return;
        }

        try {
            $case = $this->findCaseByTaskId($this->caseNumber, (string) $this->task->id);

            if (!$case) {
                Log::error('CaseException: ' . 'Case not found, case_number=' . $this->caseNumber . ', task_id=' . $this->task->id);

                return;
            }

            $taskIndex = explode('.', $case->task_index);

            $this->updateTaskStatusInCase($this->caseNumber, $taskIndex[0], $this->task->status);
        } catch (\Exception $e) {
            Log::error('CaseException: ' . $e->getMessage());
            Log::error('CaseException: ' . $e->getTraceAsString());
        }
    }

    /**
     * The function `findCaseByTaskId` retrieves a specific case by its number and a task ID from a database table.
     *
     * @param int caseNumber The `caseNumber` parameter is an integer representing the case number that you want to search
     * for in the database. It is used to filter the query results based on the specified case number.
     * @param string taskId The `findCaseByTaskId` function is used to retrieve a case by its case number and a specific
     * task ID. The function queries a database table to find a case that matches the provided case number and contains a
     * task with the specified task ID.
     *
     * @return ?object The `findCaseByTaskId` function is returning an object with the following properties:
     * - `case_number`: The case number associated with the task ID
     * - `task_index`: The index of the task within the `tasks` JSON column that matches the provided task ID
     */
    public function findCaseByTaskId(int $caseNumber, string $taskId): ?object
    {
        return DB::table($this->table)
            ->select([
                'case_number',
                DB::raw("JSON_UNQUOTE(JSON_SEARCH(tasks, 'one', ?, NULL, '$[*].id')) as task_index"),
            ])
            ->where('case_number', $caseNumber)
            ->whereJsonContains('tasks', ['id' => $taskId])
            ->setBindings([$taskId], 'select')
            ->first();
    }

    /**
     * The function `updateTaskStatusInCase` updates the status of a specific task in a case record in a database table.
     *
     * @param int caseNumber The `caseNumber` parameter is an integer that represents the case number associated with the
     * task that needs to be updated.
     * @param string taskIndex The `taskIndex` parameter in the `updateTaskStatusInCase` function is used to specify the
     * index of the task within the JSON array that needs to be updated. It is a string that represents the path to the
     * specific task within the JSON structure. For example, if the tasks are stored
     * @param string status The `updateTaskStatusInCase` function is used to update the status of a specific task in a
     * case. The parameters are as follows:
     */
    public function updateTaskStatusInCase(int $caseNumber, string $taskIndex, string $status): void
    {
        DB::table($this->table)
            ->where('case_number', $caseNumber)
            ->update([
                'tasks' => DB::raw('JSON_SET(tasks, "' . $taskIndex . '.status", "' . $status . '")'),
            ]);
    }
}
