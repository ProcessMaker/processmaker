<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;

class ProcessRequestController extends Controller
{
    use ResourceRequestsTrait;

    /**
     * Display a listing of the resource.
     *
     * @param Request $httpRequest
     *
     * @return Response
     */
    public function index(Request $httpRequest)
    {
        $query = ProcessRequest::query();

        $filter = $httpRequest->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter);
            });
        }

        $response =
            $query->orderBy(
                $httpRequest->input('order_by', 'name'),
                $httpRequest->input('order_direction', 'ASC')
            )
                ->paginate($httpRequest->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Display the specified resource.
     *
     * @param ProcessRequest $request
     *
     * @return Response
     *
     */
    public function show(ProcessRequest $request)
    {
        return new ProcessRequestResource($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $httpRequest
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $httpRequest)
    {
        $this->encodeRequestUuids($httpRequest, ['process_uuid', 'process_collaboration_uuid', 'user_uuid']);
        $httpRequest->validate(ProcessRequest::rules());
        $processRequest = new ProcessRequest();
        $processRequest->fill($httpRequest->input());
        $processRequest->saveOrFail();
        return new ProcessRequests($processRequest);
    }

    /**
     * Update a request
     *
     * @param ProcessRequest $request
     * @param Request|ProcessRequest $httpRequest
     *
     * @return ResponseFactory|Response
     */
    public function update(ProcessRequest $request, Request $httpRequest)
    {
        $this->encodeRequestUuids($httpRequest, ['process_uuid', 'process_collaboration_uuid', 'user_uuid']);
        $request->fill($httpRequest->json()->all());
        $this->validateModel($request, ProcessRequest::rules($request));
        $request->saveOrFail();
        return response([], 204);
    }

    /**
     * Delete a request
     *
     * @param ProcessRequest $request
     *
     * @return ResponseFactory|Response
     */
    public function destroy(ProcessRequest $request)
    {
        $request->delete();
        return response([], 204);
    }
}
