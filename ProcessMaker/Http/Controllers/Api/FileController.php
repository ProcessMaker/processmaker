<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\Media;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileController extends Controller
{
    /**
     * A blacklist of attributes that should not be
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
     *     path="/requests/{request_id}/files",
     *     summary="Returns the list of files associated to a request",
     *     operationId="getFiles",
     *     tags={"Files"},
     *     @OA\Parameter(ref="#/components/parameters/filter"),
     *     @OA\Parameter(ref="#/components/parameters/order_by"),
     *     @OA\Parameter(ref="#/components/parameters/order_direction"),
     *     @OA\Parameter(ref="#/components/parameters/per_page"),
     *     @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
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
     *                 allOf={@OA\Schema(ref="#/components/schemas/metadata")},
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/requests/{request_id}/files",
     *     summary="Save a new media file",
     *     operationId="createFile",
     *     tags={"Files"},
     *
     *      @OA\Parameter(
     *         name="media_id",
     *         in="query",
     *         description="ID of the model to which the file will be associated",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *     ),
     *      @OA\Parameter(
     *         name="media",
     *         in="query",
     *         description="Name of the class of the model",
     *         required=false,
     *         @OA\Schema(type="string"),
     *     ),
     *      @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *              @OA\Property(property="file", type="string", format="byte"),
     *      )
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
        // We get the model instance with the data that the user sent
        $modelClass = 'ProcessMaker\\Models\\' . ucwords($request->query('model', null));
        $modelId = $request->query('model_id', null);

        // If no model info was sent in the request
        if ($modelClass === null || $modelId === null || !class_exists($modelClass)) {
            throw new NotFoundHttpException();
        }

        $model = $modelClass::find($modelId);

        // If we can't find the model's instance
        if ($model === null) {
            throw new NotFoundHttpException();
        }

        $addedMedia = $model->addMediaFromRequest('file')->toMediaCollection('local');

        return response([
            'id' => $addedMedia->id,
            'model_id' => $addedMedia->model_id,
            'file_name' => $addedMedia->file_name,
            'mime_type' => $addedMedia->mime_type
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param Media $file
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/requests/{request_id}/files/{file_id}",
     *     summary="Get a file uploaded to a request",
     *     operationId="getFilesById",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         description="ID of the file to return",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the group",
     *         @OA\JsonContent(ref="#/components/schemas/groups")
     *     ),
     * )
     */
    public function show(Media $file)
    {
        $path = Storage::disk('public')->getAdapter()->getPathPrefix() .
                $file->id . '/' .
                $file->file_name;
        return response()->download($path);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param Media $file
     *
     * @return \Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/requests/{request_id}/files/{file_id}",
     *     summary="Update a media file",
     *     operationId="updateFile",
     *     tags={"Files"},
     *
     *     @OA\Parameter(
     *         description="ID of the file to update",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *      @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *              @OA\Property(property="file", type="string", format="byte"),
     *      )
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
     *     path="/requests/{request_id}",
     *     summary="Delete a media file",
     *     operationId="deleteFile",
     *     tags={"Files"},
     *     @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
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
    public function destroy(Media $file)
    {
        // We get the model instance with the information that comes in the media
        $modelType = $file->model_type;
        $modelId = $file->model_id;
        $model = $modelType::find($modelId);

        $model->deleteMedia($file->id);

        return response([], 204);
    }
}
