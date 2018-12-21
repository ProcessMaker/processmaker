<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
    public $skipPermissionCheckFor = ['index', 'store', 'update', 'destroy'];

    use HasMediaTrait;
    /*
    * return list of Process Request files
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
      * Chunk uploading behavior
      * @param FileReceiver $receiver The Chunk FileReceiver
      * @return JsonResponse
      */

    public function show(Request $laravel_request, ProcessRequest $request, $file_id)
    {
        return $request->getMedia()->where('id', $file_id)->first();
    }

    private function chunk(FileReceiver $receiver, ProcessRequest $request, Request $laravel_request)
    {
            // Perform a chunk upload
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }
            // receive the file
            $save = $receiver->receive();

            // This needs to be the unique uploader name
            $data_name = $laravel_request->input('data_name');

            // check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                
                foreach($request->getMedia() as $mediaItem) {
                    if($mediaItem->getCustomProperty('data_name') == $data_name) {
                        $mediaItem->delete();
                    }
                }

                // save the file and return any response you need
                $file = $request
                    ->addMedia($save->getFile())
                    ->withCustomProperties(['data_name' => $data_name]) // photo_1
                    ->toMediaCollection();
                // $identifier = ['_type' => 'file', 'id' => $file->id];
                return new JsonResponse(['message' => 'file successfully uploaded','fileUploadId' => $file->id], 200);
            }
            // we are in chunk mode, lets send the current progress
            /** @var AbstractHandler $handler */
            $handler = $save->handler();
            return response()->json([
                "done" => $handler->getPercentageDone()
            ]);
    }

    /**
     * save media file to db
    */
    public function store(Request $laravel_request, FileReceiver $receiver, ProcessRequest $request)
    {
        //delete it and upload the new one 
        if($laravel_request->input('chunk')) {
            // Perform a chunk upload
            return $this->chunk($receiver, $request, $laravel_request);
        } else {
            $file = $request->addMedia($laravel_request->file)->toMediaCollection();
            return new JsonResponse(['message' => 'file successfully uploaded'], 200);
        }
    }

    /**
     * update existing file
     */
    public function update(Request $laravel_request, ProcessRequest $request)
    {
        $request->getMedia()[0]->delete();
        $newFile = $laravel_request->file;
        $request->addMedia($newFile)->toMediaCollection();
        return new JsonResponse(['message' => 'file successfully updated'], 200);
    }

    /**
     * delete an existing file 
     */
    public function destroy(Request $laravel_request, ProcessRequest $request)
    {
        $request->getMedia()[0]->delete();
        return response([], 204);
    }
}
