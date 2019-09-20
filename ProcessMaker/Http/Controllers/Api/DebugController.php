<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Support\Str;

class DebugController extends Controller
{
    public function store(Request $request)
    {
        \Log::debug(
            $request->input('name') . ": " . 
            Str::limit($request->input('message'), 100000)
        );
        return response('', 204);
    }
}
