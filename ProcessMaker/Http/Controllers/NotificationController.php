<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class NotificationController extends Controller
{
    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status');
        return view('notifications.index', compact('status'));
    }
}
