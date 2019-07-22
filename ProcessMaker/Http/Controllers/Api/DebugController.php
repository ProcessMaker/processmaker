<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class DebugController extends Controller
{
    public function store(Request $request)
    {
        \Log::debug(
            "Unhandled Javascript API Error: " . 
            str_limit($request->getContent(), 100000)
        );
        return response('', 204);
    }
}
