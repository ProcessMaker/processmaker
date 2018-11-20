<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ProcessRequests;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Http\Resources\ProcessRequests as ProcessRequestResource;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;


class ProcessRequestFileController extends Controller
{
    use HasMediaTrait;
    
    public function index(Request $request, ProcessRequest $process_request, $file_id)
     {
        
     }
}
