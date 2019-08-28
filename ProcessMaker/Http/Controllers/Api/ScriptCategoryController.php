<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ScriptCategory;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;

class ScriptCategoryController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        //
    ];

    /**
     * Display a listing of the Script Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/script_categories",
     *     summary="Returns all scripts categories that the user has access to",
     *     operationId="getScriptCategories",
     *     tags={"Script Categories"},
     *     @OA\Parameter(
     *             parameter="filter",
     *             name="filter",
     *             in="query",
     *             description="Filter results by string. Searches Name, Description, and Status. All fields must match exactly.",
     *             @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of scripts categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ScriptCategory"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = ScriptCategory::nonSystem();
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
        $include  = $request->input('include') ? explode(',',$request->input('include')) : [];
        $query->with($include);
        $response = $query->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Display the specified script category.
     *
     * @param ScriptCategory $scriptCategory
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/script_categories/{script_category_id}",
     *     summary="Get single script category by ID",
     *     operationId="getScriptCategoryById",
     *     tags={"Script Categories"},
     *     @OA\Parameter(
     *         description="ID of script category to return",
     *         in="path",
     *         name="script_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the script",
     *         @OA\JsonContent(ref="#/components/schemas/ScriptCategory")
     *     ),
     * )
     */
    public function show(ScriptCategory $scriptCategory)
    {
        return new ApiResource($scriptCategory);
    }

    /**
     * Store a newly created Script Category in storage
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     *
     * @OA\Post(
     *     path="/script_categories",
     *     summary="Save a new Script Category",
     *     operationId="createScriptCategory",
     *     tags={"Script Categories"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ScriptCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ScriptCategory")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(ScriptCategory::rules());
        $category = new ScriptCategory();
        $category->fill($request->json()->all());
        $category->saveOrFail();
        return new ApiResource($category);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ScriptCategory $scriptCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     * 
     * @OA\Put(
     *     path="/script_categories/{script_category_id}",
     *     summary="Update a script Category",
     *     operationId="updateScriptCategory",
     *     tags={"Script Categories"},
     *     @OA\Parameter(
     *         description="ID of script category to return",
     *         in="path",
     *         name="script_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ScriptCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ScriptCategory")
     *     ),
     * )
     */
    public function update(Request $request, ScriptCategory $scriptCategory)
    {
        $request->validate(ScriptCategory::rules($scriptCategory));
        $scriptCategory->fill($request->json()->all());
        $scriptCategory->saveOrFail();
        return new ApiResource($scriptCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ScriptCategory $scriptCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Exception
     *
     * @OA\Delete(
     *     path="/script_categories/{script_category_id}",
     *     summary="Delete a script category",
     *     operationId="deleteScriptCategory",
     *     tags={"Script Categories"},
     *     @OA\Parameter(
     *         description="ID of script category to return",
     *         in="path",
     *         name="script_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success"
     *     ),
     * )
     */
    public function destroy(ScriptCategory $scriptCategory)
    {
        if ($scriptCategory->scripts->count() !== 0) {
            return response (
                ['message'=>'The item should not have associated scripts',
                    'errors'=> ['scripts' => $scriptCategory->scripts->count()]],
                422);
        }

        $scriptCategory->delete();
        return response('', 204);
    }
}
