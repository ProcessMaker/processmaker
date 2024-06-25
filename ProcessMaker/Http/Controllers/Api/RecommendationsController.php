<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;

class RecommendationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return ['test' => $user->username];
    }
}
