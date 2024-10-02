<?php

namespace ProcessMaker\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use ProcessMaker\Contracts\CaseApiRepositoryInterface;
use ProcessMaker\Exception\CaseValidationException;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;

class CaseApiRepository implements CaseApiRepositoryInterface
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

    protected $searchableFields = [
        'case_number',
        'case_title',
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

    protected $dateFields = [
        'initiated_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    const DEFAULT_SORT_DIRECTION = 'asc';

    /**
     * Get all cases
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getAllCases(Request $request): Builder
    {
        $query = CaseStarted::select($this->defaultFields);
        $this->applyFilters($request, $query);
        return $query;
    }

    /**
     * Get all cases in progress
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getInProgressCases(Request $request): Builder
    {
        $query = CaseParticipated::select($this->defaultFields)
            ->where('case_status', 'IN_PROGRESS');
        $this->applyFilters($request, $query);
        return $query;
    }

    /**
     * Get all completed cases
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getCompletedCases(Request $request): Builder
    {
        $query = CaseParticipated::select($this->defaultFields)
            ->where('case_status', 'COMPLETED');
        $this->applyFilters($request, $query);
        return $query;
    }

    /**
     * Apply filters to the query.
     *
     * @param Request $request
     * @param Builder $query
     *
     * @return void
     */
    protected function applyFilters(Request $request, Builder $query): void
    {
        if ($request->filled('userId')) {
            $query->where('user_id', $request->get('userId'));
        }

        if ($request->filled('status')) {
            $query->where('case_status', $request->get('status'));
        }

        $this->search($request, $query);
        $this->filterBy($request, $query);
        $this->sortBy($request, $query);
    }

    /**
     * Search by case number or case title.

     * @param Request $request: Query parameter format: search=keyword
     * @param Builder $query
     *
     * @return void
     */
    public function search(Request $request, Builder $query): void
    {
        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search) {
                foreach ($this->searchableFields as $field) {
                    if ($field === 'case_title') {
                        $q->orWhereFullText($field, $search . '*', ['mode' => 'boolean']);
                    } else {
                        $q->where($field, $search);
                    }
                }
            });
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
    public function filterBy(Request $request, Builder $query): void
    {
        if ($request->filled('filterBy')) {
            $filterByValue = $request->get('filterBy');

            foreach ($filterByValue as $key => $value) {
                if (!in_array($key, $this->filterableFields)) {
                    throw new CaseValidationException("Filter by field $key is not allowed.");
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
     * Sort the query.
     *
     * @param Request $request: Query parameter format: sortBy=field:asc,field2:desc,...
     * @param Builder $query
     *
     * @return void
     */
    public function sortBy(Request $request, Builder $query): void
    {
        if ($request->filled('sortBy')) {
            $sort = explode(',', $request->get('sortBy'));

            foreach ($sort as $value) {
                $sort = explode(':', $value);
                $field = $sort[0];
                $order = $sort[1] ?? self::DEFAULT_SORT_DIRECTION;

                if (!in_array($field, $this->sortableFields)) {
                    throw new CaseValidationException("Sort by field $field is not allowed.");
                }

                $query->orderBy($field, $order);
            }
        }
    }
}
