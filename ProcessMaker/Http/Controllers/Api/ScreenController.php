<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Jobs\ExportScreen;
use ProcessMaker\Jobs\ImportScreen;
use ProcessMaker\Models\Screen;
use ProcessMaker\Query\SyntaxError;

class ScreenController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'content',
    ];

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
     *                 @OA\Schema(ref="#/components/schemas/metadata"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        if (!(Auth::user()->can('view-screens') ||
            Auth::user()->can('create-processes') ||
            Auth::user()->can('edit-processes'))) {
            throw new AuthorizationException(__('Not authorized to view screens.'));
        }

        $query = Screen::nonSystem()
                    ->select('screens.*')
                    ->where('key', null)
                    ->leftJoin('screen_categories as category', 'screens.screen_category_id', '=', 'category.id');
        $include = $request->input('include', '');

        if ($include) {
            $include = explode(',', $include);
            $count = array_search('categoryCount', $include);
            if ($count !== false) {
                unset($include[$count]);
                $query->withCount('category');
            }
            if ($include) {
                $query->with($include);
            }
        }


        $filter = $request->input('filter', '');
        $isSelectList = $request->input('selectList', '');
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            if (!$isSelectList) {
                $query->where(function ($query) use ($filter) {
                    $query->where('title', 'like', $filter)
                        ->orWhere('description', 'like', $filter)
                        ->orWhere('category.name', 'like', $filter);
                });
            } else {
                $query->where(function ($query) use ($filter) {
                    $query->where('title', 'like', $filter);
                });
            }
        }
        if ($request->input('type')) {
            $types = explode(',', $request->input('type'));
            $query->whereIn('type', $types);
        }
        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $query->pmql($pmql);
            } catch (SyntaxError $e) {
                return response(['message' => __('Your PMQL contains invalid syntax.')], 400);
            }
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
     * @OA\Get(
     *     path="/screens/{screens_id}",
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
     *         description="Successfully found the screen",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function show(Screen $screen)
    {
        if (!(Auth::user()->can('view-screens') ||
            Auth::user()->can('create-processes') ||
            Auth::user()->can('edit-processes'))) {
            throw new AuthorizationException(__('Not authorized to view screens.'));
        }
        return new ApiResource($screen);
    }

    /**
     * Create a new Screen.
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *  @OA\Post(
     *     path="/screens",
     *     summary="Save a new screens",
     *     operationId="createScreen",
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
     *     path="/screens/{screens_id}",
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
     *         response=204,
     *         description="success"
     *     ),
     * )
     */
    public function update(Screen $screen, Request $request)
    {
        $request->validate(Screen::rules($screen));
        $screen->fill($request->input());
        $screen->saveOrFail();
        return response([], 204);
    }

    /**
     * duplicate a Screen.
     *
     * @param Screen $screen
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/screens/{screens_id}/duplicate",
     *     summary="duplicate a screen",
     *     operationId="duplicateScreen",
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
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function duplicate(Screen $screen, Request $request)
    {
        $request->validate(Screen::rules());
        $newScreen = new Screen();

        $exclude = ['id', 'created_at', 'updated_at'];
        foreach ($screen->getAttributes() as $attribute => $value) {
            if (!in_array($attribute, $exclude)) {
                $newScreen->{$attribute} = $screen->{$attribute};
            }
        }

        if ($request->has('title')) {
            $newScreen->title = $request->input('title');
        }

        if ($request->has('description')) {
            $newScreen->description = $request->input('description');
        }

        if( $request->has('screen_category_id')) {
            $newScreen->screen_category_id = $request->input('screen_category_id');
        }

        $newScreen->saveOrFail();
        return new ApiResource($newScreen);
    }

    /**
     * Delete a Screen.
     *
     * @param Screen $screen
     *
     * @return ResponseFactory|Response
     *     @OA\Delete(
     *     path="/screens/{screens_id}",
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
     *     ),
     * )
     */
    public function destroy(Screen $screen)
    {
        $screen->delete();
        return response([], 204);
    }

    /**
     * Export the specified screen.
     *
     * @param $screen
     *
     * @return Response
     *
     * @OA\Post(
     *     path="/screens/{screensId}/export",
     *     summary="Export a single screen by ID",
     *     operationId="exportScreen",
     *     tags={"Screens"},
     *     @OA\Parameter(
     *         description="ID of screen to return",
     *         in="path",
     *         name="screensId",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully exported the screen",
     *         @OA\JsonContent(ref="#/components/schemas/screenExported")
     *     ),
     * )
     */
    public function export(Request $request, Screen $screen)
    {
        $fileKey = ExportScreen::dispatchNow($screen);

        if ($fileKey) {
            return ['url' => url("/designer/screens/{$screen->id}/download/{$fileKey}")];
        } else {
            return response(['error' => __('Unable to Export Screen')], 500);
        }
    }

    /**
     * Import the specified screen.
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @OA\Post(
     *     path="/screens/import",
     *     summary="Import a new screen",
     *     operationId="importScreen",
     *     tags={"Screens"},
     *     @OA\Response(
     *         response=201,
     *         description="success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="object"),
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     description="file to import",
     *                     type="string",
     *                     format="binary",
     *                 ),
     *             )
     *         )
     *     ),
     * )
     */
    public function import(Request $request)
    {
        $content = $request->file('file')->get();
        if (!$this->validateImportedFile($content)) {
            return response(
                ['message' => __('Invalid Format')],
                422
            );
        }

        $import = ImportScreen::dispatchNow($content);
        return ['status' => $import];
    }

    /**
     * Verify if the file is valid to be imported
     *
     * @param string $content
     *
     * @return bool
     */
    private function validateImportedFile($content)
    {
        $decoded = substr($content, 0, 1) === '{' ? json_decode($content) : (($content = base64_decode($content)) && substr($content, 0, 1) === '{' ? json_decode($content) : null);
        $isDecoded = $decoded && is_object($decoded);
        $hasType = $isDecoded && isset($decoded->type) && is_string($decoded->type);
        $validType = $hasType && $decoded->type === 'screen_package';
        $hasVersion = $isDecoded && isset($decoded->version) && is_string($decoded->version);
        $validVersion = $hasVersion && method_exists(ImportScreen::class, "parseFileV{$decoded->version}");
        return $isDecoded && $validType && $validVersion;
    }
}
