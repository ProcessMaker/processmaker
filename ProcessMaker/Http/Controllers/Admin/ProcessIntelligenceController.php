<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Api\ProcessIntelligenceController as ApiProcessIntelligenceController;
use ProcessMaker\Http\Controllers\Controller;

class ProcessIntelligenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $response = resolve(ApiProcessIntelligenceController::class)->getJweToken();
        $data = json_decode($response->getContent(), true);

        return view('admin.process-intelligence.index', [
            'token' => $data['token'],
        ]);
    }
}
