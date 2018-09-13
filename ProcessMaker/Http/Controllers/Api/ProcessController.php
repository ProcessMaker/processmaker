<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Transformers\ProcessTransformer;


class ProcessController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $where = $this->getRequestFilterBy($request, ['name', 'description','status']);
        $oderBy = $this->getRequestSortBy($request, 'name');
        $processes = Process::where($where)
            ->orderBy(...$oderBy)
            ->paginate();
        return fractal($processes, new ProcessTransformer)
            ->parseIncludes($request->input('include'));
    }

    /**
     * Display the specified resource.
     *
     * @param $process
     *
     * @return \Illuminate\Http\Response
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
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(Process::rules());
        $data = $request->json()->all();

        $process = new Process();
        $process->fill($data);


        if (empty($data['user_uuid'])) {
            $process->user_uuid = Auth::user()->uuid;
        }

        if (isset($data['bpmn'])) {
            $process->bpmn = $data['bpmn'];
        }
        else {
            $process->bpmn = Process::getProcessTemplate('OnlyStartElement.bpmn');
        }

        $process->saveOrFail();
        $process->refresh();
        return fractal($process, new ProcessTransformer())->respond(201);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \Illuminate\Http\Response
     *
     */
    public function update(Request $request, Process $process)
    {
        $data = $request->json()->all();
        $process->fill($data);
        $process->saveOrFail();
        $process->refresh();
        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Process $process
     *
     * @return \Illuminate\Http\Response
     *
     */
    public function destroy(Process $process)
    {
        $process->delete();
        return response('', 204);
    }

    protected function getRequestFilterBy(Request $request, array $searchableColumns)
    {
        $where = [];
        $filter = $request->input('filter');
        if ($filter) {
            foreach ($searchableColumns as $column) {
                $where[] = [$column, 'like', $filter, 'or'];
            }
        }
        return $where;
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestSortBy(Request $request, $default)
    {
        $column = $request->input('order_by', $default);
        $direction = $request->input('order_direction', 'asc');
        return [$column, $direction];
    }

    /**
     * Get included relationships.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRequestInclude(Request $request)
    {
        $include = $request->input('include');
        return $include ? explode(',', $include) : [];
    }
}
