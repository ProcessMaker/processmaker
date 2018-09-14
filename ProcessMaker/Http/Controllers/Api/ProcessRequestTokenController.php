<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Transformers\ProcessRequestTokenTransformer;

/**
 * Implement the routes related to tokens list and show.
 * 
 */
class ProcessRequestTokenController extends Controller
{
    use ResourceRequestsTrait;

    /**
     * Display a listing of the resource.
     *
     * @param Request $httpRequest
     * @param ProcessRequest $request
     *
     * @return Response
     */
    public function index(Request $httpRequest, ProcessRequest $request)
    {
        $where = $this->getRequestFilterBy($httpRequest, ['status']);
        $orderBy = $this->getRequestSortBy($httpRequest, 'updated_at');
        $perPage = $this->getPerPage($httpRequest);
        $tokens = $request->tokens()->where($where)
            ->orderBy(...$orderBy)
            ->paginate($perPage);
        return fractal($tokens, new ProcessRequestTokenTransformer)
            ->parseIncludes($httpRequest->input('include'));
    }

    /**
     * Display the specified resource.
     *
     * @param Request $httpRequest
     * @param ProcessRequest $request
     * @param ProcessRequestToken $token
     *
     * @return Response
     */
    public function show(Request $httpRequest, ProcessRequest $request, ProcessRequestToken $token)
    {
        if ($request->uuid !== $token->process_request_uuid) {
            return abort(404);
        }
        return fractal($token, new ProcessRequestTokenTransformer())
            ->parseIncludes($httpRequest->input('include'))
            ->respond(200);
    }
}
