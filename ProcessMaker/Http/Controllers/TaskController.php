<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequestToken;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index()
    {
        $title = 'To Do Tasks';
        
        if(Request::input('status') == 'CLOSED'){
            $title = 'Completed Tasks';
        }
        
        return view('tasks.index', compact('title'));
    }

    public function show()
    {
        return view('tasks.show');
    }

    public function edit(ProcessRequestToken $task)
    {
        $this->authorize('update', $task);
        //Mark as unread any not read notification for the task
        Notification::where('data->url', Request::path())
            ->whereNotNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return view('tasks.edit', ['task' => $task, 'dueLabels' => self::$dueLabels]);
    }
}
