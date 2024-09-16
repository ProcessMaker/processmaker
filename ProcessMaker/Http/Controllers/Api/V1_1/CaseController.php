<?php

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Requests\CaseListRequest;
use ProcessMaker\Http\Resources\V1_1\CaseResource;
use ProcessMaker\Repositories\CaseApiRepository;

class CaseController extends Controller
{
    protected $caseRepository;

    const DEFAULT_PAGE_SIZE = 15;

    public function __construct(private Request $request, CaseApiRepository $caseRepository) {
        $this->caseRepository = $caseRepository;
    }

    /* The comment block you provided is a PHPDoc block. It is used to document the purpose and usage of a method in PHP
    code. In this specific block: */
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
    public function getAllCases(CaseListRequest $request): JSonResponse
    {
        $query = $this->caseRepository->getAllCases($request);
        return $this->paginateResponse($query);
    }

    /**
     * Get a list of all started cases.
     *
     * @param Request $request
     *
     * @queryParam userId int Filter by user ID.
     * @queryParam sortBy string Sort by field:asc,field2:desc,...
     * @queryParam filterBy array Filter by field=value&field2=value2&...
     * @queryParam search string Search by case number or case title.
     * @queryParam pageSize int Number of items per page.
     * @queryParam page int Page number.
     *
     * @return array
     */
    public function getInProgress(CaseListRequest $request): JSonResponse
    {
        $query = $this->caseRepository->getInProgressCases($request);
        return $this->paginateResponse($query);
    }

    /**
     * Get a list of all started cases.
     *
     * @param Request $request
     *
     * @queryParam userId int Filter by user ID.
     * @queryParam sortBy string Sort by field:asc,field2:desc,...
     * @queryParam filterBy array Filter by field=value&field2=value2&...
     * @queryParam search string Search by case number or case title.
     * @queryParam pageSize int Number of items per page.
     * @queryParam page int Page number.
     *
     * @return array
     */
    public function getCompleted(CaseListRequest $request): JSonResponse
    {
        $query = $this->caseRepository->getCompletedCases($request);
        return $this->paginateResponse($query);
    }

    /**
     * Handle pagination and return JSON response.
     *
     * @param Builder $query
     *
     * @return JsonResponse
     */
    private function paginateResponse(Builder $query): JsonResponse
    {
        $pageSize = $this->request->get('pageSize', self::DEFAULT_PAGE_SIZE);
        $pagination = CaseResource::collection($query->paginate($pageSize));

        return response()->json([
            'data' => $pagination->items(),
            'meta' => [
                'total' => $pagination->total(),
                'perPage' => $pagination->perPage(),
                'currentPage' => $pagination->currentPage(),
                'lastPage' => $pagination->lastPage(),
            ],
        ]);
    }
}
