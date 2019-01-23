<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class RequestController extends Controller
{
    use HasMediaTrait;
    /**
     * A user should always be able to see their
     * started requests.
     * 
     * @var array
     */
    public $skipPermissionCheckFor = ['index', 'show'];

    /**
     * Get the list of requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index($type = null)
    {
        //load counters
        $query = ProcessRequest::query();
        if (!Auth::user()->is_administrator) {
            $query->startedMe(Auth::user()->id);
        }

        $allRequest = $this->calculate('allRequest');
        $startedMe = $this->calculate('startedMe');
        $inProgress = $this->calculate('inProgress');
        $completed = $this->calculate('completed');

        $title = 'My Requests';

        $types = ['all'=>'All Requests','in_progress'=>'Requests In Progress','completed'=>'Completed Requests'];

        if(array_key_exists($type,$types)){
          $title = $types[$type];
        }

        return view('requests.index', compact(
            ['allRequest', 'startedMe', 'inProgress', 'completed', 'type','title']
        ));
    }
    private function calculate($type)
    {
        $result = 0;
        $query = ProcessRequest::query();
        if (!Auth::user()->is_administrator) {
            $query->startedMe(Auth::user()->id);
        }

        switch ($type) {
            case 'allRequest':
               $result = $query->count();
               break;
            case 'startedMe':
                $result = ProcessRequest::startedMe(Auth::user()->id)->count();
                break;
            case 'inProgress':
                $result =$query->inProgress()->count();
                break;
            case 'completed':
                $result = $query->completed()->count();
                break;
        }
        return $result;
    }

    /**
     * Request Show
     *
     * @param ProcessRequest $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(ProcessRequest $request, Media $mediaItems)
    {
        $this->authorize('view', $request);
        $request->participants;
        $request->user;
        $request->summary = $request->summary();
        $request->summary_screen = $request->getSummaryScreenId();
        $canCancel = Auth::user()->can('cancel', $request->process);

        $files = $request->getMedia();

        return view('requests.show', compact('request', 'files', 'canCancel'));
    }

    public function downloadFiles(ProcessRequest $requestID, Media $fileID)
    {
        $requestID->getMedia();
        return response()->download($fileID->getPath(), $fileID->name);
    }
}
