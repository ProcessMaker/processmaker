<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use ProcessMaker\Events\FilesAccessed;
use ProcessMaker\Events\FilesCreated;
use ProcessMaker\Events\FilesDeleted;
use ProcessMaker\Events\FilesDownloaded;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\TaskDraft;

class ProcessRequestFileController extends Controller
{
    /**
     * A whitelist of attributes that should not be
     * sanitized by our SanitizeInput middleware.
     *
     * @var array
     */
    public $doNotSanitize = [
        'custom_properties',
        'manipulations',
        'responsive_images',
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
     *     summary="Returns the list of files associated with a request",
     *     operationId="getRequestFiles",
     *     tags={"Request Files"},
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
     *           type="integer",
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
    public function index(Request $laravel_request, ProcessRequest $request)
    {
        //Retrieve input filter variables
        $name = $laravel_request->get('name');
        $id = $laravel_request->get('id');
        $filter = $name ? $name : $id;

        $media = Media::getFilesRequest($request, $id);

        // Register the Event
        if (!empty($filter)) {
            FilesAccessed::dispatch($filter, $request);
        }

        if ($id && $media) {
            // We retrieved a single item by ID, so no need to filter.
            // Just return a collection with one item.
            $media = [$media];
            $filter = false;
        }

        // If no filter, return entire collection; otherwise, filter collection
        if (!$filter) {
            return new ResourceCollection($media);
        } else {
            $filtered = $media->reject(function ($item, $key) use ($filter, $name, $id) {
                if ($filter === $name) {
                    if ($item->custom_properties['data_name'] != $name) {
                        return true;
                    }
                } elseif ($filter === $id) {
                    if ($item->id != $id) {
                        return true;
                    }
                }

                return false;
            });

            return new ResourceCollection($filtered);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Media $file
     * @return Response
     *
     * @OA\Get(
     *     path="/requests/{request_id}/files/{file_id}",
     *     summary="Get a file uploaded to a request",
     *     operationId="getRequestFilesById",
     *     tags={"Request Files"},
     *     @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
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
    public function show(Request $laravel_request, ProcessRequest $request, $media)
    {
        $file = $request->downloadFile($media);

        if ($file) {
            // Register the Event
            if (!empty($file['file_name'])) {
                FilesDownloaded::dispatch($file['file_name'], $request);
            }

            return response()->download($file);
        }

        return abort(response(__('File ID does not exist'), 404));
    }

    /**
     * Chunk uploading behavior
     * @param FileReceiver $receiver The Chunk FileReceiver
     * @return JsonResponse
     */
    protected function chunk(FileReceiver $receiver, ProcessRequest $request, Request $laravelRequest)
    {
        // Perform a chunk upload
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }
        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            return $this->saveUploadedFile($save->getFIle(), $request, $laravelRequest);
        }
        // we are in chunk mode, lets send the current progress
        /** @var AbstractHandler $handler */
        $handler = $save->handler();

        return response()->json([
            'done' => $handler->getPercentageDone(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     *
     * @OA\Post(
     *     path="/requests/{request_id}/files",
     *     summary="Save a new media file to a request",
     *     operationId="createRequestFile",
     *     tags={"Request Files"},
     *
     *      @OA\Parameter(
     *         description="ID of the request",
     *         in="path",
     *         name="request_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
     *         )
     *     ),
     *      @OA\Parameter(
     *         name="data_name",
     *         in="query",
     *         description="Variable name in the request data to use for the file name",
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
     *        ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="fileUploadId", type="integer"),
     *             ),
     *         )
     *     ),
     * )
     */
    public function store(Request $laravel_request, FileReceiver $receiver, ProcessRequest $request)
    {
        //delete it and upload the new one
        if ($laravel_request->input('chunk')) {
            // Perform a chunk upload
            return $this->chunk($receiver, $request, $laravel_request);
        } else {
            return $this->saveUploadedFile($laravel_request->file, $request, $laravel_request);
        }
    }

    /**
     * Used by both store() and chunk() to associate the uploaded file to a request
     *
     * @param UploadedFile $file
     * @param ProcessRequest $processRequest
     * @param Request $laravelRequest
     * @return JsonResponse
     */
    private function saveUploadedFile(UploadedFile $file, ProcessRequest $processRequest, Request $laravelRequest)
    {
        $parentId = $processRequest->parent_request_id;
        $parentRequest = $processRequest;

        while ($parentId != null) {
            $parentRequest = ProcessRequest::find($parentId);
            $parentId = $parentRequest->parent_request_id;
        }

        $user = pmUser();
        $originalCreatedBy = $user ? $user->id : null;

        $data_name = $laravelRequest->input('data_name', $file->getClientOriginalName());
        $rowId = $laravelRequest->input('row_id', null);
        $parent = (int) $laravelRequest->input('parent', null);
        $multiple = $laravelRequest->input('multiple', null);
        $taskId = (int) $laravelRequest->input('task_id', 0);

        $model = $parentRequest;
        if ($taskId) {
            // The draft may not exist yet. Create it now if it doesn't exist.
            $model = TaskDraft::firstOrCreate(['task_id' => $taskId], ['data' => []]);
        }

        foreach ($model->getMedia() as $mediaItem) {
            if (
                $mediaItem->getCustomProperty('data_name') == $data_name &&
                $mediaItem->getCustomProperty('parent') == $parent &&
                $mediaItem->getCustomProperty('row_id') == $rowId
            ) {
                $originalCreatedBy = $mediaItem->getCustomProperty('createdBy');
                if (empty($multiple)) {
                    $mediaItem->delete();
                }
            }
        }

        // save the file and return any response you need
        $media = $model
            ->addMedia($file)
            ->withCustomProperties([
                'data_name' => $data_name,
                'parent' => $parent != 0 ? $parent : null,
                'row_id' => $rowId,
                'createdBy' => $originalCreatedBy,
            ])
            ->toMediaCollection();

        if ($taskId) {
            // Model is a TaskDraft. Save the new file ID in the draft's data.
            $data = $model->data;
            $data[$data_name] = $media->id;
            $model->data = $data;
            $model->saveOrFail();

            // Set the process request the file should belong to after saving.
            // Note that this is the $parentRequest and may be different than
            // the task->processRequest.
            $media->setCustomProperty('parent_process_request_id', $parentRequest->id);
            $media->setCustomProperty('is_multiple', (bool) $multiple);
            $media->saveOrFail();
        }

        // Register the Event
        FilesCreated::dispatch($media->id, $processRequest);

        return new JsonResponse(['message' => 'The file was uploaded.', 'fileUploadId' => $media->id], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Media $file
     * @return Response
     *
     * @internal param int $id
     *
     * @OA\Delete(
     *     path="/requests/{request_id}/files/{file_id}",
     *     summary="Delete all media associated with a request",
     *     operationId="deleteRequestFile",
     *     tags={"Request Files"},
     *     @OA\Parameter(
     *         description="ID of the file",
     *         in="path",
     *         name="file_id",
     *         required=true,
     *         @OA\Schema(
     *           type="integer",
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
     *         response=204,
     *         description="success"
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     * )
     */
    public function destroy(Request $laravel_request, ProcessRequest $request, $fileId)
    {
        $file = Media::getFilesRequest($request, $fileId);

        if (!$file) {
            return abort(response(__('File ID does not exist'), 404));
        }

        $file->delete();

        // Register the Event
        FilesDeleted::dispatch($fileId, $file->file_name);

        return response([], 204);
    }
}
