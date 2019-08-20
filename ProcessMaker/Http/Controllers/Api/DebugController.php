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
            "Unhandled Javascript API Error: " . 
            Str::limit($request->getContent(), 100000)
        );
        return response('', 204);
    }
}
