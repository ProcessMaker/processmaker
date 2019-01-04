<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class NotificationController extends Controller
{
    public $skipPermissionCheckFor = ['index', 'show'];

    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status');

        $title = __('All Notifications');

        if($status === 'unread') {
          $title = __('Unread Notifications');
        }
        
        return view('notifications.index', compact('status', 'title'));
    }
}
