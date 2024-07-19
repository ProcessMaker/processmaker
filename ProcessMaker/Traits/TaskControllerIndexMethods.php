<?php

namespace ProcessMaker\Traits;

use DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ProcessMaker\Filters\Filter;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Query\SyntaxError;

trait TaskControllerIndexMethods
{
    private function indexBaseQuery($request)
    {
        $query = ProcessRequestToken::exclude(['data'])->with([
            'processRequest' => fn($q) => $q->exclude(['data']),
            // review if bpmn is reuiqred here process
            'process' => fn($q) => $q->exclude(['svg', 'warnings']),
            // review if bpmn is reuiqred here processRequest.process
            'processRequest.process' => fn($q) => $q->exclude(['svg', 'warnings']),
            // bpmn is required here in processRequest.processVersion
            'processRequest.processVersion' => fn($q) => $q->exclude(['svg', 'warnings']),
            // review if bpmn is reuiqred here processRequest.processVersion.process
            'processRequest.processVersion.process' => fn($q) => $q->exclude(['svg', 'warnings']),
            'user',
            'draft'
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

    private function excludeNonVisibleTasks($query, $request)
    {
        $nonSystem = filter_var($request->input('non_system'), FILTER_VALIDATE_BOOLEAN);
        $query->where(function ($query) {
            $query->where('element_type', '=', 'task');
            $query->orWhere('element_type', '=', 'serviceTask');
            $query->where('element_name', '=', 'AI Assistant');
        })
            ->when($nonSystem, function ($query) {
                $query->nonSystem();
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
            Filter::filter($query, $advancedFilter);
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
                ->orWhereIn('id', $user->availableSelfServiceTaskIds());
        });
    }
}
