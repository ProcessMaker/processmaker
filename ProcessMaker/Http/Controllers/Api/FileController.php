<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\FilesDeleted;
use ProcessMaker\Events\FilesDownloaded;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\MediaLog;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\TaskDraft;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileController extends Controller
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
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return ApiCollection
     *
     * @OA\Get(
     *     path="/files",
     *     summary="Returns the list of files",
     *     operationId="getFiles",
     *     tags={"Files"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="list of files",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/media"),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 ref="#/components/schemas/metadata",
     *             ),
     *         ),
     *     ),
     * )
     */
    public function index(Request $request)
    {
        $query = Media::query();
        $filter = $request->input('filter', '');

        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('file_name', 'like', $filter)
                    ->orWhere('mime_type', 'like', $filter);
            });
        }

        $query->orderBy(
            $request->input('order_by', 'updated_at'),
            $request->input('order_direction', 'asc')
        );

        $response = $query->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/files",
     *     summary="Save a new media file. Note: To upload files to a request, use createRequestFile in the RequestFile API",
     *     operationId="createFile",
     *     tags={"Files"},
     *
     *      @OA\Parameter(
     *         name="model_id",
     *         in="query",
     *         description="ID of the model to which the file will be associated",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *     ),
     *      @OA\Parameter(
     *         name="model",
     *         in="query",
     *         description="Full namespaced class of the model to associate",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *      @OA\Parameter(
     *         name="data_name",
     *         in="query",
     *         description="Name of the variable used in a request",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="file",
     *                description="save a new media file",
     *                type="string",
     *                format="binary",
     *              ),
     *            ),
     *         ),
     *     ),
     *     @OA\Parameter(
     *         name="collection",
     *         in="query",
     *         description="Media collection name. For requests, use 'default'",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="id", type="string"),
     *              @OA\Property(property="model_id", type="string"),
     *              @OA\Property(property="file_name", type="string"),
     *              @OA\Property(property="mime_type", type="string")
     *             ),
     *         )
     *     ),
     * )
     */
    public function store(Request $request)
    {
        // Get the ID of the model this should be attached to
        $modelId = $request->query('model_id', null);

        // Set the model class to false initially
        $modelClass = false;

        // The model class can be a name or a full path
        $classOptions = [
            $request->query('model', null),
            'ProcessMaker\\Models\\' . ucwords($request->query('model', null)),
        ];

        // Check for the model class until we find a match
        foreach ($classOptions as $class) {
            if (class_exists($class)) {
                $modelClass = $class;
                break;
            }
        }

        // If no model info was sent in the request
        if (!$modelClass || !$modelId) {
            throw new NotFoundHttpException();
        }

        $model = $modelClass::find($modelId);

        // If we can't find the model's instance
        if ($model === null) {
            throw new NotFoundHttpException();
        }

        $mediaCollection = $request->input('collection', 'local');
        $file = $model->addMediaFromRequest('file');
        $user = pmUser();
        $originalCreatedBy = $user ? $user->id : null;
        $data_name = $request->input('data_name', '');
        $rowId = $request->input('row_id', null);
        $parent = (int) $request->input('parent', null);

        $addedMedia = $file
            ->withCustomProperties([
                'data_name' => $data_name,
                'parent' => $parent != 0 ? $parent : null,
                'row_id' => $rowId,
                'createdBy' => $originalCreatedBy,
            ])
            ->toMediaCollection($mediaCollection);

        return response([
            'id' => $addedMedia->id,
            'model_id' => $addedMedia->model_id,
            'file_name' => $addedMedia->file_name,
            'mime_type' => $addedMedia->mime_type,
        ], 200);
    }

    /**
     * Get a single media file.
     *
     * @param Media $file
     *
     * @return ResponseFactory|Response
     *
     * @OA\Get(
     *     path="/files/{file_id}",
     *     summary="Get the metadata of a file. To actually fetch the file see Get File Contents",
     *     operationId="getFileById",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         description="ID of the file to return",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the file",
     *         @OA\JsonContent(ref="#/components/schemas/media")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function show(Media $file)
    {
        return new ApiResource($file);
    }

    /**
     * Display the specified resource.
     *
     * @param Media $file
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/files/{file_id}/contents",
     *     summary="Get the contents of a file",
     *     operationId="getFileContentsById",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         description="ID of the file to return",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File stream",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function download(Media $file)
    {
        $path = Storage::disk('public')->path($file->id . '/' . $file->file_name);

        // Register the Event
        if (!empty($file->file_name)) {
            FilesDownloaded::dispatch($file);
        }

        return response()->download($path);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     * @param Media $file
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Media $file)
    {
        $newFile = $request->file('file');
        $newMedia = new \ProcessMaker\Media();
        $newMedia->updateFile($newFile, $file);

        return response([], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Media $file
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     *
     * @OA\Delete(
     *     path="/files/{file_id}",
     *     summary="Delete a media file",
     *     operationId="deleteFile",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         description="ID of the file",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="success"
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function destroy(Media $file)
    {
        // We get the model instance with the information that comes in the media
        $modelType = $file->model_type;
        $modelId = $file->model_id;
        $model = $modelType::find($modelId);

        $taskId = (int) request()->input('task_id', 0);
        if ($taskId && $modelType === ProcessRequest::class) {
            // We are deleting a file from the request, however, we
            // need need to preserve it because this is a draft.
            $draft = TaskDraft::firstOrCreate(['task_id' => $taskId], ['data' => []]);
            $data = $draft->data;
            if (!isset($data['__deleted_files'])) {
                $data['__deleted_files'] = [];
            }
            $data['__deleted_files'][] = $file->id;
            $draft->data = $data;
            $draft->saveOrFail();

            return response([], 204);
        }

        $model->deleteMedia($file->id);

        // Register the Event
        FilesDeleted::dispatch($file->id, $file->file_name);

        return response([], 204);
    }

    public function showLogs(Media $file)
    {
        $response = MediaLog::with('user')
                        ->where('media_id', $file->id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return new ApiCollection($response);
    }
}
