<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Transformers\ProcessTransformer;


class ProcessController extends Controller
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
        $processes = Process::where($where)
            ->orderBy(...$orderBy)
            ->paginate($perPage);
        return fractal($processes, new ProcessTransformer)
            ->parseIncludes($request->input('include'));
    }

    /**
     * Display the specified resource.
     *
     * @param $process
     *
     * @return Response
     */
    public function show(Process $process)
    {
        $process->category = $process->category()->first();
        $process->user = $process->user()->first();
        return fractal($process, new ProcessTransformer())->respond(200);
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
        $this->encodeRequestUuids($request, ['process_category_uuid']);
        $data = $request->json()->all();

        $process = new Process();
        $process->fill($data);

        //set current user
        $process->user_uuid = Auth::user()->uuid;

        if (isset($data['bpmn'])) {
            $process->bpmn = $data['bpmn'];
        }
        else {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }
        //validate model trait
        $this->validateModel($process, Process::rules());
        $process->save();
        $process->refresh();
        return fractal($process, new ProcessTransformer())->respond(201);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param Process $process
     * @return ResponseFactory|Response
     * @throws \Throwable
     */
    public function update(Request $request, Process $process)
    {
        //Convert the string uuid to binary uuid
        $this->encodeRequestUuids($request, ['process_category_uuid']);
        $process->fill($request->json()->all());
        //validate model trait
        $this->validateModel($process, Process::rules($process));
        $process->save();
        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function destroy(Process $process)
    {
        $this->validateModel($process, [
            'collaborations' => 'empty',
            'requests' => 'empty',
        ]);
        $process->delete();
        return response('', 204);
    }
}
