<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Events\ScreenCreated;
use ProcessMaker\Events\ScreenDeleted;
use ProcessMaker\Events\ScreenUpdated;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\Screen as ScreenResource;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Jobs\ExportScreen;
use ProcessMaker\Jobs\ImportScreen;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScreenTemplates;
use ProcessMaker\Models\ScreenType;
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
     *     @OA\Parameter(
     *         name="exclude",
     *         in="query",
     *         description="Comma separated list of fields to exclude from the response",
     *         @OA\Schema(type="string", default=""),
     *     ),
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
        $exclusions = ($request->input('exclude', '') ? explode(',', $request->input('exclude', '')) : []);

        $query = Screen::nonSystem()
            ->leftJoin('screen_categories as category', 'screens.screen_category_id', '=', 'category.id')
            ->exclude($exclusions);

        $include = $request->input('include', '');

        // sparse fields
        $fields = $request->input('fields', '');
        if ($fields) {
            $fields = explode(',', $fields);
            $query->select($fields);
        }

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
                        ->orWhereIn('screens.id', function ($qry) use ($filter) {
                            $qry->select('assignable_id')
                                ->from('category_assignments')
                                ->leftJoin('screen_categories', function ($join) {
                                    $join->on('screen_categories.id', '=', 'category_assignments.category_id');
                                    $join->where('category_assignments.category_type', '=', ScreenCategory::class);
                                    $join->where('category_assignments.assignable_type', '=', Screen::class);
                                })
                                ->where('screen_categories.name', 'like', $filter);
                        });
                });
            } else {
                $query->where(function ($query) use ($filter) {
                    $query->where('title', 'like', $filter);
                });
            }
        }
        $interactive = filter_var($request->input('interactive'), FILTER_VALIDATE_BOOLEAN);
        if ($interactive) {
            $screens = ScreenType::where('is_interactive', $interactive)->get('name');
            $query->whereIn('type', $screens);
        }
        if (!$interactive && $request->input('type')) {
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

        if ($request->has('key')) {
            $query->where('key', $request->get('key'));
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
        return new ScreenResource($screen);
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
        $newScreen = $screen->fill($request->input());

        if ($request->has('defaultTemplateId') && is_null($request->defaultTemplateId) && $request->has('is_public')) {
            $this->updateDefaultTemplate($request->type, $request->is_public);
        }

        $screen->saveOrFail();
        $screen->syncProjectAsset($request, Screen::class);

        // Creating temporary Key to store multiple id categories
        $newScreen['tmp_screen_category_id'] = $request->input('screen_category_id');
        // Call event to store New Screen data in LOG
        ScreenCreated::dispatch($newScreen->getAttributes());

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
        $original = $screen->getOriginal();
        $screen->saveOrFail();
        $screen->syncProjectAsset($request, Screen::class);

        // Call event to store Screen Changes into Log
        $request->validate(Screen::rules($screen));
        $changes = $screen->getChanges();
        // Creating temporary Key to store multiple id categories
        $changes['tmp_screen_category_id'] = $request->input('screen_category_id');
        ScreenUpdated::dispatch($screen, $changes, $original);
        $this->updateScreenTemplate($screen);

        return response([], 204);
    }

    /**
     * Update a draft Screen.
     *
     * @param Screen $screen
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     *     @OA\Put(
     *     path="/screens/{screens_id}/draft",
     *     summary="Update a draft screen",
     *     operationId="updateDraftScreen",
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
    public function draft(Screen $screen, Request $request)
    {
        $request->validate(Screen::rules($screen));
        $screen->fill($request->input());
        $screen->saveDraft();

        return response([], 204);
    }

    public function close(Screen $screen)
    {
        $screen->deleteDraft();

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

        $exclude = ['id', 'uuid', 'created_at', 'updated_at'];
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

        if ($request->has('screen_category_id')) {
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
        // Call new event to store changes in LOG
        ScreenDeleted::dispatch($screen);

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
        $fileKey = (new ExportScreen($screen))->handle();

        if ($fileKey) {
            return ['url' => url("/designer/screens/{$screen->id}/download/{$fileKey}")];
        } else {
            return response(['message' => __('Unable to Export Screen')], 500);
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

        $import = (new ImportScreen($content))->handle();

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

    /**
     * Get preview a screen
     *
     * @param Request $request
     *
     * @return ResponseFactory|Response
     *
     * @OA\Post(
     *     path="/screens/preview",
     *     summary="Preview a screen",
     *     operationId="preview",
     *     tags={"Screens"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="config", type="object"),
     *             @OA\Property(property="watchers", type="object"),
     *             @OA\Property(property="computed", type="object"),
     *             @OA\Property(property="custom_css", type="string"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the screen",
     *         @OA\JsonContent(ref="#/components/schemas/screens")
     *     ),
     * )
     */
    public function preview(Request $request)
    {
        $screen = new Screen();
        $screen->config = $request->post('config');
        $screen->watchers = $request->post('watchers');
        $screen->computed = $request->post('computed');
        $screen->custom_css = $request->post('custom_css');

        return new ScreenResource($screen);
    }

    public function updateDefaultTemplate(string $screenType, int $isPublic)
    {
        ScreenTemplates::where('screen_type', $screenType)
            ->where('is_public', $isPublic)
            ->where('is_default_template', 1)
            ->update(['is_default_template' => 0]);
    }

    private function updateScreenTemplate(Screen $screen): void
    {
        if ($screen->is_template && $screen->asset_type === 'SCREEN_TEMPLATE') {
            $screen->update(['is_template' => 0, 'asset_type' => null]);
            $exporter = new Exporter();
            $exporter->exportScreen($screen);
            ScreenTemplates::where('editing_screen_uuid', $screen->uuid)
                ->update([
                    'manifest' => json_encode($exporter->payload()),
                    'screen_custom_css' => $screen->custom_css,
                ]);
            $screen->update(['is_template' => 1, 'asset_type' => 'SCREEN_TEMPLATE']);
        }
    }

    public function sync(Request $request)
    {
        // Get screen info
        $screen = Screen::find($request->get('id'));
        $exportScreen = new ExportScreen($screen);
        $exportScreen->handle();
        $content = $exportScreen->prepareForDevLink();
        $fileName = $screen->uuid . '.yaml';

        // Next steps/actions to be defined and/or determined
        /**
         * - Create a JSON schema for each asset type
         * - Clean some attributes not required. e.g. id, created_at, updated_at, etc.
         * - Add uuids from referenced assets e.g. screen_uuid (for nested), screen_category_uuid, etc.
         * - Avoid nested assets in JSON. e.g. screen_category, etc, always use references with the uuid.
         * - Apply "beautify" to JSON (or maybe use YAML)
         */

        // Get DevLink settings
        $githubRepo = config('devlink.repo');
        $githubAccount = config('devlink.account');
        $githubToken =  config('devlink.token');
        $githubBranch =  config('devlink.branch');

        // Authenticate
        $github = new \Github\Client();
        $github->authenticate($githubAccount, $githubToken, \Github\AuthMethod::CLIENT_ID);

        // Create blob
        $blob = $github->api('gitData')->blobs()->create(
            $githubAccount,
            $githubRepo,
            [
                'content' => $content,
                'encoding' => 'utf-8'
            ]
        );

        // Get base tree
        $baseTree = $github->api('gitData')->trees()->show($githubAccount, $githubRepo, $githubBranch);

        // Create tree
        $treeData = [
            'base_tree' => $baseTree['sha'],
            'tree' => [
                [
                    'path' => 'screens' . '/' . $fileName,
                    'mode' => '100644',
                    'type' => 'blob',
                    'sha' => $blob['sha']
                ]
            ]
        ];
        $tree = $github->api('gitData')->trees()->create($githubAccount, $githubRepo, $treeData);

        // Create commit
        $commitData = [
            'message' => 'Update screen ' . $screen->uuid . ' (' . time() . ')',
            'tree' => $tree['sha'],
            'parents' => [
                $baseTree['sha']
            ]
        ];
        $commit = $github->api('gitData')->commits()->create($githubAccount, $githubRepo, $commitData);

        // Update reference
        $referenceData = [
            'sha' => $commit['sha'],
            'force' => false
        ];
        $reference = $github->api('gitData')->references()->update($githubAccount, $githubRepo, 'heads/' . $githubBranch, $referenceData);

        return response([], 204);
    }
}
