<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;

class ScreenCategoryController extends Controller
{
    /**
     * Display a listing of the Screen Categories.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/screen_categories",
     *     summary="Returns all screens categories that the user has access to",
     *     operationId="getScreenCategories",
     *     tags={"ScreenCategories"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of screens categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ScreenCategory"),
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
        $query = ScreenCategory::query();
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
     * Display the specified screen category.
     *
     * @param ScreenCategory $screenCategory
     *
     * @return \Illuminate\Http\JsonResponse
     *     * @OA\Get(
     *     path="/screen_categories/screen_category_id",
     *     summary="Get single screen category by ID",
     *     operationId="getScreenCategoryById",
     *     tags={"ScreenCategories"},
     *     @OA\Parameter(
     *         description="ID of screen category to return",
     *         in="path",
     *         name="screen_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the screen",
     *         @OA\JsonContent(ref="#/components/schemas/ScreenCategory")
     *     ),
     * )
     */
    public function show(ScreenCategory $screenCategory)
    {
        return new ApiResource($screenCategory);
    }

    /**
     * Store a newly created Screen Category in storage
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     *     * @OA\Post(
     *     path="/screen_categories",
     *     summary="Save a new Screen Category",
     *     operationId="createScreenCategory",
     *     tags={"ScreenCategories"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ScreenCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ScreenCategory")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(ScreenCategory::rules());
        $category = new ScreenCategory();
        $category->fill($request->json()->all());
        $category->saveOrFail();
        return new ApiResource($category);
    }

    /**
     * Updates the current element
     *
     * @param Request $request
     * @param ScreenCategory $screenCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *      * @OA\Put(
     *     path="/screen_categories/screen_category_id",
     *     summary="Update a screen Category",
     *     operationId="updateScreenCategory",
     *     tags={"ScreenCategories"},
     *     @OA\Parameter(
     *         description="ID of screen category to return",
     *         in="path",
     *         name="screen_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/ScreenCategoryEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ScreenCategory")
     *     ),
     * )
     */
    public function update(Request $request, ScreenCategory $screenCategory)
    {
        $request->validate(ScreenCategory::rules($screenCategory));
        $screenCategory->fill($request->json()->all());
        $screenCategory->saveOrFail();
        return new ApiResource($screenCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ScreenCategory $screenCategory
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     *
     *      * @OA\Delete(
     *     path="/screen_categories/screen_category_id",
     *     summary="Delete a screen category",
     *     operationId="deleteScreenCategory",
     *     tags={"ScreenCategories"},
     *     @OA\Parameter(
     *         description="ID of screen category to return",
     *         in="path",
     *         name="screen_category_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/ScreenCategory")
     *     ),
     * )
     */
    public function destroy(ScreenCategory $screenCategory)
    {
        if ($screenCategory->screens->count() !== 0) {
            return response (
                ['message'=>'The item should not have associated screens',
                    'errors'=> ['screens' => $screenCategory->screens->count()]],
                    422);
        }

        $screenCategory->delete();
        return response('', 204);
    }
}
