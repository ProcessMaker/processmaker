<?php

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Requests\CaseListRequest;
use ProcessMaker\Http\Resources\V1_1\CaseResource;
use ProcessMaker\Models\User;
use ProcessMaker\Repositories\CaseApiRepository;

class CaseController extends Controller
{
    protected $caseRepository;

    const DEFAULT_PAGE_SIZE = 15;

    public function __construct(private Request $request, CaseApiRepository $caseRepository)
    {
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
    public function getAllCases(CaseListRequest $request): JsonResponse
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
    public function getInProgress(CaseListRequest $request): JsonResponse
    {
        // The status parameter should never be considered as a filter for this list.
        $request->merge(['status' => null]);

        // Get query
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
    public function getCompleted(CaseListRequest $request): JsonResponse
    {
        // The status parameter should never be considered as a filter for this list.
        $request->merge(['status' => null]);

        // Get query
        $query = $this->caseRepository->getCompletedCases($request);

        return $this->paginateResponse($query);
    }

    /**
     * Get "my cases" counters
     *
     * @param CaseListRequest $request
     *
     * @return JsonResponse
     */
    public function getMyCasesCounters(CaseListRequest $request): JsonResponse
    {
        // Load user object
        if ($request->filled('userId')) {
            $userId = $request->get('userId');
            $user = User::find($userId);
        } else {
            $user = Auth::user();
        }

        // Initializing variables
        $totalAllCases = null;
        $totalMyCases = null;
        $totalInProgress = null;
        $totalCompleted = null;
        $totalMyRequest = null;

        // Check permission
        if ($user->hasPermission('view-all_cases')) {
            // The total number of cases recorded in the platform. User Id send is overridden.
            $request->merge(['userId' => null]);
            $queryAllCases = $this->caseRepository->getAllCases($request);
            $totalAllCases = $queryAllCases->count();
        }

        // Restore user id
        $request->merge(['userId' => $user->id]);

        // The total number of cases recorded by the user making the request.
        $queryMyCases = $this->caseRepository->getAllCases($request);
        $totalMyCases = $queryMyCases->count();

        // The number of In Progress cases started by the user making the request.
        $queryInProgressCases = $this->caseRepository->getInProgressCases($request);
        $totalInProgress = $queryInProgressCases->count();

        // The number of Completed cases started by the user making the request.
        $queryCompletedCases = $this->caseRepository->getCompletedCases($request);
        $totalCompleted = $queryCompletedCases->count();

        // Check permission
        if ($user->hasPermission('view-my_requests')) {
            // Only in progress requests
            $requestAux = new Request();
            $requestAux->replace(['type' => 'in_progress']);

            // The number of requests for user making the request.
            $totalMyRequest = (new ProcessRequestController)->index($requestAux, true, $user);
        }

        // Build response
        return response()->json([
            'totalAllCases' => $totalAllCases,
            'totalMyCases' => $totalMyCases,
            'totalInProgress' => $totalInProgress,
            'totalCompleted' => $totalCompleted,
            'totalMyRequest' => $totalMyRequest,
        ]);
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
        $data = $query->paginate($pageSize);
        // Get all the participants ids from the data
        $users = $this->caseRepository->getUsers($data);

        $pagination = CaseResource::customCollection($data, $users);

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
