<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class NotificationController extends Controller
{
    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index()
    {
        return view('notifications.index');
    }
}
