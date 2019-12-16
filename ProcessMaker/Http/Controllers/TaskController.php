<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\SearchAutocompleteTrait;

class TaskController extends Controller
{
    use SearchAutocompleteTrait;

    private static $dueLabels = [
        'open' => 'Due',
        'completed' => 'Completed',
        'overdue' => 'Due',
    ];

    public function index()
    {
        $title = 'To Do Tasks';

        if (Request::input('status') == 'CLOSED') {
            $title = 'Completed Tasks';
        }

        return view('tasks.index', compact('title'));
    }

    public function edit(ProcessRequestToken $task)
    {
        $this->authorize('update', $task);
        //Mark as unread any not read notification for the task
        Notification::where('data->url', Request::path())
            ->whereNotNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        $manager = new ScreenBuilderManager();
        event(new ScreenBuilderStarting($manager, $task->getScreen() ? $task->getScreen()->type : 'FORM'));

        $submitUrl = route('api.tasks.update', $task->id);
        $task->processRequest;
        $task->screen;
        $task->request_data = $task->processRequest->data;
        $task->bpmn_tag_name = $task->getBpmnDefinition()->localName;
        $interstitial = $task->getInterstitial();
        $task->interstitial_screen = $interstitial['interstitial_screen'];
        $task->allow_interstitial = $interstitial['allow_interstitial'];
        $task->definition = $task->getDefinition();
        $task->requestor = $task->processRequest->user;

        return view('tasks.edit', [
            'task' => $task,
            'dueLabels' => self::$dueLabels,
            'manager' => $manager,
            'submitUrl' => $submitUrl
            ]);
    }
}
