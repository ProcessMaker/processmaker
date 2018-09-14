<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Transformers\ProcessCategoryTransformer;

class ProcessCategoryController extends Controller
{
    use ResourceRequestsTrait;
    /**
     * Display a listing of the Process Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $where = $this->getRequestFilterBy($request, ['name','status']);
        $orderBy = $this->getRequestSortBy($request, 'name');
        $perPage = $this->getPerPage($request);
        $processes = ProcessCategory::where($where)
            ->orderBy(...$orderBy)
            ->paginate($perPage);
        return fractal($processes, new ProcessCategoryTransformer())
            ->parseIncludes($request->input('include'));
    }

    /**
     * Display the specified Process category.
     *
     * @param Request $request
     * @param ProcessCategory $ProcessCategory
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, ProcessCategory $ProcessCategory)
    {
        return fractal($ProcessCategory, new ProcessCategoryTransformer())
            ->parseIncludes($request->input('include', []))
            ->respond(200);
    }

    /**
     * Store a newly created Process Category in storage
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $category = new ProcessCategory();
        $category->fill($request->json()->all());

        //validate model trait
        $this->validateModel($category, ProcessCategory::rules());
        $category->save();
        return fractal($category->refresh(), new ProcessCategoryTransformer())->respond(201);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(Request $request, ProcessCategory $processCategory)
    {
        $processCategory->fill($request->json()->all());
        //validate model trait
        $this->validateModel($processCategory, ProcessCategory::rules($processCategory));
        $processCategory->save();
        return response($processCategory->refresh(), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function destroy(ProcessCategory $processCategory)
    {
        /*$this->validateModel($processCategory, [
            'collaborations' => 'empty',
            'requests' => 'empty',
        ]);*/
        //$process = $processCategory->processes()->count();

        $this->validateModel($processCategory, [
            'processes' => 'empty'
        ]);

        $processCategory->delete();
        return response('', 204);
    }
}
