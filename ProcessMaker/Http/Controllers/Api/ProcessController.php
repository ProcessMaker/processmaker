<?php
namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Transformers\ProcessTransformer;

class ProcessController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $fractal = fractal();
        $fractal->parseIncludes($request->input('include'));
        
        \Illuminate\Support\Facades\Log::info(print_r($fractal, true));
        $relations = $this->getRequestInclude($request);
        $conditions = $this->getRequestFilter($request);
        $processes = Process::with($relations)
            ->where($conditions)
            ->paginate();
        return fractal($processes, new ProcessTransformer());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    protected function getRequestFilter(Request $request)
    {
        $filter = json_decode($request->input('filter', '{}'), true);
        $conditions = [];
        foreach ($filter as $name => $value) {
            $conditions[] = [
                $name,
                $value,
            ];
        }
        return $conditions;
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
