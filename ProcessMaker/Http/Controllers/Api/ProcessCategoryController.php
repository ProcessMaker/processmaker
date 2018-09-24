<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessCategory as Resource;

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
        $query = ProcessCategory::query();
        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->Where('name', 'like', $filter)
                    ->orWhere('status', 'like', $filter);
            });
        }
        if ($request->has('status')) {
            $query->where('status', 'like', $request->input('status'));
        }
        $query->orderBy(
            $request->input('order_by', 'name'),
            $request->input('order_direction', 'asc')
        );
        $include = $this->getRequestInclude($request);
        $query->with($include);
        $response = $query->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Display the specified Process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ProcessCategory $processCategory)
    {
        return new Resource($processCategory);
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
        $request->validate(ProcessCategory::rules());
        $category = new ProcessCategory();
        $category->fill($request->json()->all());
        $category->saveOrFail();
        return new Resource($category);
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
        $processCategory->saveOrFail();
        return new Resource($processCategory);
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
        $this->validateModel($processCategory, [
            'processes' => 'empty'
        ]);

        $processCategory->delete();
        return response('', 204);
    }
}
