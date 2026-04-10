<?php

namespace ProcessMaker\Traits;

use Auth;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Managers\DataManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;
use ProcessMaker\Query\SyntaxError;

trait TaskControllerIndexMethods
{
    private function indexBaseQuery($request)
    {
        // Parse the includes parameter
        $includes = $request->has('include') ? explode(',', $request->input('include')) : [];
        // Determine if the data should be included
        $includeData = in_array('data', $includes);

        $query = ProcessRequestToken::exclude(['data']);

        $query = $query->with([
            'processRequest' => function ($q) use ($includeData) {
                if (!$includeData) {
                    return $q->exclude(['data']);
                }
            },
            // review if bpmn is required here process
            'process' => fn ($q) => $q->exclude(['svg', 'warnings']),
            // review if bpmn is required here processRequest.process
            'processRequest.process' => fn ($q) => $q->exclude(['svg', 'warnings']),
            // The following lines use to much memory but reduce the number of queries
            // bpmn is required here in processRequest.processVersion
            // 'processRequest.processVersion' => fn($q) => $q->exclude(['svg', 'warnings']),
            // review if bpmn is required here processRequest.processVersion.process
            // 'processRequest.processVersion.process' => fn($q) => $q->exclude(['svg', 'warnings']),
            'user',
            'draft',
        ]);

        $include = $request->input('include') ? explode(',', $request->input('include')) : [];

        foreach (['data'] as $key) {
            if (in_array($key, $include)) {
                unset($include[array_search($key, $include)]);
            }
        }

        $query->with($include);

        return $query;
    }

    private function applyFilters($query, $request)
    {
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->filter($filter);
        }

        $filterByFields = [
            'process_id',
            'process_request_tokens.user_id' => 'user_id',
            'process_request_tokens.status' => 'status',
            'element_id',
            'element_name',
            'process_request_id',
        ];

        $parameters = $request->all();

        foreach ($parameters as $column => $fieldFilter) {
            if (in_array($column, $filterByFields)) {
                if ($column === 'user_id') {
                    $this->applyUserIdFilter($query, $column, $filterByFields, $fieldFilter);
                } elseif ($column === 'process_request_id') {
                    $this->applyProcessRequestIdFilter($query, $column, $filterByFields, $fieldFilter, $parameters);
                } else {
                    $this->applyDefaultFiltering($query, $column, $filterByFields, $fieldFilter);
                }
            }
        }
    }

    private function applyUserIdFilter($query, $column, $filterByFields, $fieldFilter)
    {
        $key = array_search($column, $filterByFields);
        $query->where(function ($query) use ($key, $column, $fieldFilter) {
            $userColumn = is_string($key) ? $key : $column;
            $query->where($userColumn, $fieldFilter);
            $query->orWhere(function ($query) use ($userColumn, $fieldFilter) {
                $query->whereNull($userColumn);
                $query->where('process_request_tokens.is_self_service', 1);
                $user = User::find($fieldFilter);
                $query->where(function ($query) use ($user) {
                    foreach ($user->groups as $group) {
                        $query->orWhereJsonContains(
                            'process_request_tokens.self_service_groups', strval($group->getKey())
                        ); // backwards compatibility
                        $query->orWhereJsonContains(
                            'process_request_tokens.self_service_groups->groups', strval($group->getKey())
                        );
                    }
                    $query->orWhereJsonContains(
                        'process_request_tokens.self_service_groups->users', strval($user->getKey())
                    );
                });
            });
        });
    }

    private function applyProcessRequestIdFilter($query, $column, $filterByFields, $fieldFilter, $parameters)
    {
        $key = array_search($column, $filterByFields);
        $requestIdColumn = is_string($key) ? $key : $column;
        if (empty($parameters['include_sub_tasks'])) {
            $query->where($requestIdColumn, $fieldFilter);
        } else {
            // Include tasks from sub processes
            $ids = ProcessRequest::find($fieldFilter)->childRequests()->pluck('id')->toArray();
            $ids = Arr::prepend($ids, $fieldFilter);
            $query->whereIn($requestIdColumn, $ids);
        }
    }

    private function applyDefaultFiltering($query, $column, $filterByFields, $fieldFilter)
    {
        $key = array_search($column, $filterByFields);
        $operator = is_numeric($fieldFilter) ? '=' : 'like';
        $query->where(is_string($key) ? $key : $column, $operator, $fieldFilter);
    }

    private function addTaskData($response)
    {
        $dataManager = new DataManager();
        $response->getCollection()->transform(function ($row) use ($dataManager) {
            $row->taskData = $dataManager->getData($row, true);

            return $row;
        });

        return $response;
    }

    private function excludeNonVisibleTasks($query, $request)
    {
        $nonSystem = filter_var($request->input('non_system'), FILTER_VALIDATE_BOOLEAN);
        $allTasks = filter_var($request->input('all_tasks'), FILTER_VALIDATE_BOOLEAN);
        $hitlEnabled = filter_var(config('smart-extract.hitl_enabled'), FILTER_VALIDATE_BOOLEAN);
        $query->when(!$allTasks, function ($query) {
            $query->where(function ($query) {
                $query->where('element_type', '=', 'task');
                $query->orWhere(function ($query) {
                    $query->where('element_type', '=', 'serviceTask');
                    $query->where('element_name', '=', 'AI Assistant');
                });
            });
        })
            ->when($nonSystem, function ($query) use ($hitlEnabled) {
                if (!$hitlEnabled) {
                    $query->nonSystem();

                    return;
                }

                $query->where(function ($query) {
                    $query->nonSystem();
                    $query->orWhere(function ($query) {
                        $query->where('element_type', '=', 'task');
                        $query->where('element_name', '=', 'Manual Document Review');
                    });
                });
            });
    }

    private function applyColumnOrdering($query, $request)
    {
        $direction = $request->input('order_direction', 'asc');
        $orderColumns = explode(',', $request->input('order_by', 'updated_at'));
        foreach ($orderColumns as $column) {
            $parts = explode('.', $column);
            $table = count($parts) > 1 ? array_shift($parts) : 'process_request_tokens';
            $columnName = array_pop($parts);

            // Handle ordering by JSON fields
            if ($table === 'data') {
                $this->orderByJsonData($query, $column, $direction);
            } elseif ($column === 'user.name') {
                $this->orderByUserFullName($query, $direction);
            } elseif ($column === 'status') {
                $this->orderByStatusAlias($query, $direction);
            } elseif (!Str::contains($column, '.')) {
                // Order on a column in the process_request_tokens table
                $query->orderBy($column, $direction);
            } elseif ($table === 'process_requests' || $table === 'process_request' || $table === 'processRequests') {
                if ($columnName === 'id') {
                    $query->orderBy(
                        'process_request_id',
                        $direction
                    );
                } else {
                    // Raw sort by (select column from process_requests ...)
                    $query->orderBy(
                        DB::raw("(select
                                $columnName
                            from
                                process_requests
                            where
                                process_requests.id = process_request_tokens.process_request_id
                        )"),
                        $direction
                    );
                }
            }
        }
    }

    private function orderByJsonData(&$query, $column, $direction)
    {
        $pathParts = explode('.', $column);
        array_shift($pathParts);
        $path = '$.' . implode('.', $pathParts);

        // Move null values to the bottom
        $query->orderBy(
            DB::raw("(
                select
                if (
                    json_unquote(json_extract(process_requests.data, '$path')) = 'null',
                    NULL,
                    json_unquote(json_extract(process_requests.data, '$path')) -- could also be null
                )
                from process_requests where
                process_requests.id = process_request_tokens.process_request_id
            )"),
            ($direction === 'asc' ? 'desc' : 'asc')
        );

        $query->orderBy(
            DB::raw("(
                select
                json_unquote(json_extract(process_requests.data, '$path'))
                from process_requests where
                process_requests.id = process_request_tokens.process_request_id
            )"),
            $direction
        );
    }

    private function orderByStatusAlias(&$query, $direction)
    {
        $query->orderBy(
            DB::raw("CASE status when 'ACTIVE' then 'In Progress' else status end"),
            $direction
        );
    }

    private function orderByUserFullName(&$query, $direction)
    {
        $query->orderBy(
            DB::raw('process_request_tokens.user_id is null'),
            $direction
        );
        $query->orderBy(
            DB::raw('(select users.firstname from users where users.id = process_request_tokens.user_id)'),
            $direction
        );
        $query->orderBy(
            DB::raw('(select users.lastname from users where users.id = process_request_tokens.user_id)'),
            $direction
        );
    }

    private function applyStatusFilter($query, $request)
    {
        $statusFilter = $request->input('statusfilter', '');
        if ($statusFilter) {
            $statusFilter = array_map(function ($value) {
                return mb_strtoupper(trim($value));
            }, explode(',', $statusFilter));
            $query->whereIn('status', $statusFilter);
        }
    }

    private function applyPmql($query, $request, $user)
    {
        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql, null, $user);
            } catch (QueryException $e) {
                abort('Your PMQL search could not be completed.', 400);
            } catch (SyntaxError $e) {
                abort('Your PMQL contains invalid syntax.', 400);
            }
        }
    }

    private function applyAdvancedFilter($query, $request)
    {
        if ($advancedFilter = $request->input('advanced_filter', '')) {
            // Parse the advanced filter
            $filterArray = is_string($advancedFilter) ? json_decode($advancedFilter, true) : $advancedFilter;

            // Check if processesIManage is active and we have processManagerIds
            $processManagerIds = $query->getQuery()->processManagerIds ?? null;
            $isProcessManager = !empty($processManagerIds) && $request->input('processesIManage') === 'true';

            // If processesIManage is active, handle "Self Service" status filter specially
            if ($isProcessManager && is_array($filterArray)) {
                $hasSelfServiceFilter = false;
                $filteredArray = [];

                foreach ($filterArray as $filter) {
                    // Check if this is a "Self Service" status filter
                    if (isset($filter['subject']['type']) &&
                        $filter['subject']['type'] === 'Status' &&
                        isset($filter['value']) &&
                        mb_strtolower($filter['value']) === 'self service') {
                        $hasSelfServiceFilter = true;
                        // Don't add this filter to the array - we'll handle it manually
                        continue;
                    }
                    $filteredArray[] = $filter;
                }

                // Apply the filtered advanced_filter (without Self Service)
                if (!empty($filteredArray)) {
                    Filter::filter($query, $filteredArray);
                }

                // Manually apply the Self Service filter for process managers
                if ($hasSelfServiceFilter) {
                    $selfServiceTaskIds = ProcessRequestToken::select(['id'])
                        ->whereIn('process_id', $processManagerIds)
                        ->where('is_self_service', 1)
                        ->whereNull('user_id')
                        ->where('status', 'ACTIVE');

                    $query->whereIn('process_request_tokens.id', $selfServiceTaskIds);
                }
            } else {
                // Normal behavior - apply the filter as-is
                Filter::filter($query, $advancedFilter);
            }
        }
    }

    private function applyUserFilter($response, $request, $user)
    {
        // Only filter results if the user id was specified
        if ($request->input('user_id') === $user->id) {
            $response = $response->filter(function ($processRequestToken) use ($request, $user) {
                if ($request->input('status') === 'CLOSED') {
                    return $user->can('view', $processRequestToken->processRequest);
                }

                return $user->can('view', $processRequestToken);
            })->values();
        }

        return $response;
    }

    private function applyForCurrentUser($query, $user)
    {
        if ($user->is_administrator) {
            return $query;
        }

        if ($user->can('view-all_requests')) {
            return $query;
        }

        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereIn('id', $user->availableSelfServiceTasksQuery());
        });
    }

    public function applyProcessManager($query, $user)
    {
        $ids = Process::select(['id'])
            ->where(function ($subQuery) use ($user) {
                // Handle both single ID and array of IDs in JSON
                $subQuery->whereRaw("JSON_EXTRACT(properties, '$.manager_id') = ?", [$user->id])
                    ->orWhereRaw("JSON_CONTAINS(JSON_EXTRACT(properties, '$.manager_id'), CAST(? AS JSON))", [$user->id]);
            })
            ->where('status', 'ACTIVE')
            ->pluck('id')
            ->toArray();

        if (empty($ids)) {
            // If user is not a manager of any process, return no results
            $query->whereRaw('1 = 0');

            return;
        }

        // Show tasks from processes the user manages that are ACTIVE
        // OR show self-service tasks from those processes
        // Store the process IDs in the query so we can use them later to add self-service tasks
        // We'll add self-service tasks after PMQL is applied to avoid the is_self_service = 0 filter
        $query->getQuery()->processManagerIds = $ids;

        // Apply condition for regular tasks from managed processes
        // Self-service tasks will be added after PMQL to avoid conflicts
        $query->where(function ($query) use ($ids) {
            $query->whereIn('process_request_tokens.process_id', $ids)
                ->where('process_request_tokens.status', 'ACTIVE');
        });
    }

    private function enableUserManager($user)
    {
        // enable user in cache
        Cache::put("user_{$user->id}_manager", true);
    }

    /**
     * Get the ID of the default saved search for tasks
     *
     * @return int|null
     */
    private function getDefaultSavedSearchId()
    {
        $id = null;
        if (class_exists(SavedSearch::class)) {
            $savedSearch = SavedSearch::firstSystemSearchFor(
                Auth::user(),
                SavedSearch::KEY_TASKS,
            );
            if ($savedSearch) {
                $id = $savedSearch->id;
            }
        }

        return $id;
    }
}
