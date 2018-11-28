<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ApiCollection;

class ScreenController extends Controller
{
    /**
     * Get a list of Screens.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Get(
     *     path="/screens",
     *     summary="Returns all screens that the user has access to",
     *     operationId="getScreens",
     *     tags={"Screens"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(ref="#/components/parameters/include"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of screens",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/screens"),
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
        $query = Screen::query();

        $filter = $request->input('filter', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('config', 'like', $filter);
            });
        }

        $response =
            $query->orderBy(
                $request->input('order_by', 'title'),
                $request->input('order_direction', 'ASC')
            )->paginate($request->input('per_page', 10));
        return new ApiCollection($response);
    }

    /**
     * Get a single Screen.
     *
     * @param Screen $screen
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Get(
     *     path="/screens/screensId",
     *     summary="Get single screens by ID",
     *     operationId="getScreensById",
     *     tags={"Screens"},
     *     @OA\Parameter(
     *         description="ID of screens to return",
     *         in="path",
     *         name="screens_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the screens",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function show(Screen $screen)
    {
        return new ApiResource($screen);
    }

    /**
     * Create a new Screen.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Post(
     *     path="/screens",
     *     summary="Save a new screens",
     *     operationId="createScreens",
     *     tags={"Screens"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/screensEditable")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function store(Request $request)
    {
        $request->validate(Screen::rules());
        $screen = new Screen();
        $screen->fill($request->input());
        $screen->saveOrFail();
        return new ApiResource($screen);
    }

    /**
     * Update a Screen.
     *
     * @param Screen $screen
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/screens/screensId",
     *     summary="Update a screen",
     *     operationId="updateScreen",
     *     tags={"Screens"},
     *     @OA\Parameter(
     *         description="ID of screen to return",
     *         in="path",
     *         name="screens_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/screensEditable")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function update(Screen $screen, Request $request)
    {
        $request->validate(Screen::rules($screen));
        $original_attributes = $screen->getAttributes();
        $screen->fill($request->input());
        $screen->saveOrFail();

        unset(
            $original_attributes['id'],
            $original_attributes['updated_at']
        );
        $screen->versions()->create($original_attributes);

        return response([], 204);
    }

    /**
     * Delete a Screen.
     *
     * @param Screen $screen
     *
     * @return ResponseFactory|Response
     *     @OA\Delete(
     *     path="/screens/screensId",
     *     summary="Delete a screen",
     *     operationId="deleteScreen",
     *     tags={"Screens"},
     *     @OA\Parameter(
     *         description="ID of screen to return",
     *         in="path",
     *         name="screens_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function destroy(Screen $screen)
    {
        $screen->delete();
        return response([], 204);
    }

}
