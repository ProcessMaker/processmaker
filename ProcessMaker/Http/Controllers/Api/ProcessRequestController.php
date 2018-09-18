<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Transformers\ProcessRequestTransformer;

class ProcessRequestController extends Controller
{
    use ResourceRequestsTrait;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $where = $this->getRequestFilterBy($request, ['name', 'description','status']);
        $orderBy = $this->getRequestSortBy($request, 'name');
        $perPage = $this->getPerPage($request);
        $processRequestes = ProcessRequest::where($where)
            ->orderBy(...$orderBy)
            ->paginate($perPage);
        return fractal($processRequestes, new ProcessRequestTransformer)
            ->parseIncludes($request->input('include'));
    }

    /**
     * Display the specified resource.
     *
     * @param $processRequest
     *
     * @return Response
     */
    public function show(Request $request, ProcessRequest $processRequest)
    {
        return fractal($processRequest, new ProcessRequestTransformer())
            ->parseIncludes($request->input('include'))
            ->respond(200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        //Convert the string uuid to binary uuid
        $this->encodeRequestUuids($request, ['process_uuid', 'process_collaboration_uuid', 'user_uuid']);

        $data = $request->json()->all();

        $processRequest = new ProcessRequest();
        $processRequest->fill($data);

        //set current user
        $processRequest->user_uuid = Auth::user()->uuid;

        //validate model trait
        $this->validateModel($processRequest, ProcessRequest::rules());
        $processRequest->save();
        return fractal($processRequest->refresh(), new ProcessRequestTransformer())->respond(201);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ProcessRequest $processRequest
     * @return ResponseFactory|Response
     * @throws \Throwable
     */
    public function update(Request $request, ProcessRequest $processRequest)
    {
        //Convert the string uuid to binary uuid
        $this->encodeRequestUuids($request, ['processRequest_category_uuid']);
        $processRequest->fill($request->json()->all());
        //validate model trait
        $this->validateModel($processRequest, ProcessRequest::rules($processRequest));
        $processRequest->save();
        return response($processRequest->refresh(), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ProcessRequest $processRequest
     *
     * @return ResponseFactory|Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function destroy(ProcessRequest $processRequest)
    {
        $this->validateModel($processRequest, [
            'collaborations' => 'empty',
            'requests' => 'empty',
        ]);
        $processRequest->delete();
        return response('', 204);
    }
}
