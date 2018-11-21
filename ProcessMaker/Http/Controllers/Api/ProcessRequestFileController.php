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
        $newFile = $laravel_request->file;
        $request->addMedia($newFile)->toMediaCollection();
        return new JsonResponse(['message' => 'file successfully uploaded'], 200);
    }
}
