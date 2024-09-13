<?php

namespace ProcessMaker\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface CaseRepositoryInterface
{
    /**
     * Get all cases
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getAllCases(Request $request): Builder;
    /**
     * Get all cases in progress
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getInProgressCases(Request $request): Builder;
    /**
     * Get all completed cases
     *
     * @param Request $request
     *
     * @return Builder
     */
    public function getCompletedCases(Request $request): Builder;
    /**
     * Search by case number or case title.

     * @param Request $request: Query parameter format: search=keyword
     * @param Builder $query
     *
     * @return void
     */
    public function search(Request $request, Builder $query): void;
    /**
     * Filter the query.
     *
     * @param Request $request: Query parameter format: filterBy[field]=value&filterBy[field2]=value2&...
     * @param Builder $query
     * @param array $dateFields List of date fields in current model
     *
     * @return void
     */
    public function filterBy(Request $request, Builder $query): void;
    /**
     * Sort the query.
     *
     * @param Request $request: Query parameter format: sortBy=field:asc,field2:desc,...
     * @param Builder $query
     *
     * @return void
     */
    public function sortBy(Request $request, Builder $query): void;
}
