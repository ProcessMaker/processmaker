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
    public $skipPermissionCheckFor = ['index', 'show', 'edit'];

    private static $dueLabels = [
        'open' => 'Due ',
        'completed' => 'Completed ',
        'overdue' => 'Due ',
    ];

    public function index()
    {
        return view('tasks.index');
    }

    public function show()
    {
        return view('tasks.show');
    }

    public function edit(ProcessRequestToken $task)
    {
        $task->authorize(Auth::user());
        //Mark as unread any not read notification for the task
        Notification::where('data->url', Request::path())
            ->whereNotNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return view('tasks.edit', ['task' => $task, 'dueLabels' => self::$dueLabels]);
    }
}
