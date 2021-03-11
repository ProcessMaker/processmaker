<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Models\ProcessRequest;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Media;

use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;


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
       'responsive_images'
    ];

    use HasMediaTrait;

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
		//Retrieve media from ProcessRequest
		$media = $request->getMedia();

		//Retrieve input variable 'name'
		$name = $laravel_request->get('name');

		//If no name, retern entire collection; otherwise, filter collection
		if (! $name) {
			return new ResourceCollection($media);
		} else {
			$filtered = $media->reject(function ($item, $key) use ($name) {
				if ($item->custom_properties['data_name'] != $name) {
					return true;
				}
			});

	        return new ResourceCollection($filtered);
		}
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
    public function show(Request $laravel_request, ProcessRequest $request, Media $file)
    {
        $path = Storage::disk('public')->getAdapter()->getPathPrefix() .
            $file->id . '/' .
            $file->file_name;
        return response()->download($path);
    }

    /**
     * Chunk uploading behavior
     * @param FileReceiver $receiver The Chunk FileReceiver
     * @return JsonResponse
     */
    private function chunk(FileReceiver $receiver, ProcessRequest $request, Request $laravel_request)
    {
            // Perform a chunk upload
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }
            // receive the file
            $save = $receiver->receive();

            // check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                return $this->saveUploadedFile($save->getFIle(), $request, $laravel_request);
            }
            // we are in chunk mode, lets send the current progress
            /** @var AbstractHandler $handler */
            $handler = $save->handler();
            return response()->json([
                "done" => $handler->getPercentageDone()
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
        if($laravel_request->input('chunk')) {
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
        $user = pmUser();
        $originalCreatedBy = $user ? $user->id : null;

        $data_name = $laravelRequest->input('data_name', $file->getClientOriginalName());
        $rowId = $laravelRequest->input('row_id', null);
        $parent = (int)$laravelRequest->input('parent', null);

        foreach($processRequest->getMedia() as $mediaItem) {
            if(
                $mediaItem->getCustomProperty('data_name') == $data_name &&
                $mediaItem->getCustomProperty('parent') == $parent &&
                $mediaItem->getCustomProperty('row_id') == $rowId)
            {
                $originalCreatedBy = $mediaItem->getCustomProperty('createdBy');
                $mediaItem->delete();
            }
        }

        // save the file and return any response you need
        $media = $processRequest
            ->addMedia($file)
            ->withCustomProperties([
                'data_name' => $data_name,
                'parent' => $parent != 0 ? $parent : null,
                'row_id' => $rowId,
                'createdBy' => $originalCreatedBy
            ])
            ->toMediaCollection();
        return new JsonResponse(['message' => 'The file was uploaded.','fileUploadId' => $media->id], 200);
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
    public function destroy(Request $laravel_request, ProcessRequest $request, Media $file)
    {
        $request->getMedia()->firstWhere('id', $file->id)->destroy();
        return response([], 204);
    }
}
