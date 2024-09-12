<?php

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Requests\GetAllCasesRequest;
use ProcessMaker\Http\Resources\V1_1\CaseResource;
use ProcessMaker\Models\CaseStarted;

class CaseController extends Controller
{
    /**
     * Default fields used in the query select statement.
     */
    protected $defaultFields = [
        'case_number',
        'user_id',
        'case_title',
        'case_title_formatted',
        'case_status',
        'processes',
        'requests',
        'request_tokens',
        'tasks',
        'participants',
        'initiated_at',
        'completed_at',
    ];

    protected $sortableFields = [
        'case_number',
        'initiated_at',
        'completed_at',
    ];

    protected $filterableFields = [
        'case_number',
        'case_title',
        'case_status',
        'processes',
        'requests',
        'request_tokens',
        'tasks',
        'participants',
        'initiated_at',
        'completed_at',
    ];

    protected $searchableFields = [
        'case_number',
        'case_title',
    ];

    protected $dateFields = [
        'initiated_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    const DEFAULT_SORT_DIRECTION = 'asc';

    /**
     * Get a list of all started cases.
     *
     * @param Request $request
     *
     * @queryParam userId int Filter by user ID.
     * @queryParam status string Filter by case status.
     * @queryParam sortBy string Sort by field:asc,field2:desc,...
     * @queryParam filterBy array Filter by field=value&field2=value2&...
     * @queryParam search string Search by case number or case title.
     * @queryParam pageSize int Number of items per page.
     * @queryParam page int Page number.
     *
     * @return array
     */
    public function getAllCases(GetAllCasesRequest $request): array
    {
        $pageSize = $request->get('pageSize', 15);

        $query = CaseStarted::select($this->defaultFields);

        $this->filters($request, $query);

        $pagination = CaseResource::collection($query->paginate($pageSize));

        return [
            'data' => $pagination->items(),
            'meta' => [
                'total' => $pagination->total(),
                'perPage' => $pagination->perPage(),
                'currentPage' => $pagination->currentPage(),
                'lastPage' => $pagination->lastPage(),
            ],
        ];
    }

    /**
     * Apply filters to the query.
     *
     * @param Request $request
     * @param Builder $query
     *
     * @return void
     */
    private function filters(Request $request, Builder $query): void
    {
        if ($request->has('userId')) {
            $query->where('user_id', $request->get('userId'));
        }

        if ($request->has('status')) {
            $query->where('case_status', $request->get('status'));
        }

        $this->search($request, $query);
        $this->filterBy($request, $query);
        $this->sortBy($request, $query);
    }

    /**
     * Sort the query.
     *
     * @param Request $request: Query parameter format: sortBy=field:asc,field2:desc,...
     * @param Builder $query
     *
     * @return void
     */
    private function sortBy(Request $request, Builder $query): void
    {
        $sort = explode(',', $request->get('sortBy'));

        foreach ($sort as $value) {
            if (!preg_match('/^[a-zA-Z_]+:(asc|desc)$/', $value)) {
                continue;
            }

            $sort = explode(':', $value);
            $field = $sort[0];
            $order = $sort[1] ?? self::DEFAULT_SORT_DIRECTION;

            if (in_array($field, $this->sortableFields)) {
                $query->orderBy($field, $order);
            }
        }
    }

    /**
     * Filter the query.
     *
     * @param Request $request: Query parameter format: filterBy[field]=value&filterBy[field2]=value2&...
     * @param Builder $query
     * @param array $dateFields List of date fields in current model
     *
     * @return void
     */
    private function filterBy(Request $request, Builder $query): void
    {
        if ($request->has('filterBy')) {
            $filterByValue = $request->get('filterBy');

            foreach ($filterByValue as $key => $value) {
                if (!in_array($key, $this->filterableFields)) {
                    continue;
                }

                if (in_array($key, $this->dateFields)) {
                    $query->whereDate($key, $value);
                    continue;
                }

                $query->where($key, $value);
            }
        }
    }

    /**
     * Search by case number or case title.

     * @param Request $request: Query parameter format: search=keyword
     * @param Builder $query
     *
     * @return void
     */
    private function search(Request $request, Builder $query): void
    {
        if ($request->has('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    if ($field === 'case_number') {
                        $q->orWhere($field, $search);
                    } else {
                        $q->orWhereFullText($field, $search . '*', ['mode' => 'boolean']);
                    }
                }
            });
        }
    }
}
