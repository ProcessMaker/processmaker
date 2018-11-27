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

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\JsonResponse;


class ProcessRequestFileController extends Controller
{
    use HasMediaTrait;
    /*
    * return list of Process Request files
    */
    public function index(ProcessRequest $request)
     {
        return new ResourceCollection($request->getMedia());
     }

    /**
     * save media file to db
    */
    public function store(Request $laravel_request, ProcessRequest $request)
    {
        $file = $request->addMedia($laravel_request->file)->toMediaCollection();
        return new JsonResponse(['message' => 'file successfully uploaded'], 200);
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
// **************** merge these two **************

//         // check if the upload is success, throw exception or return response you need
//         if ($receiver->isUploaded() === false) {
//             throw new UploadMissingFileException();
//         }
//         // receive the file
//         $save = $receiver->receive();
//         // check if the upload has finished (in chunk mode it will send smaller files)
//         if ($save->isFinished()) {
//             // save the file and return any response you need
//             return $this->saveFile($save->getFile());
//         }
//         // we are in chunk mode, lets send the current progress
//         /** @var AbstractHandler $handler */
//         $handler = $save->handler();
//         return response()->json([
//             "done" => $handler->getPercentageDone()
//         ]);
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
