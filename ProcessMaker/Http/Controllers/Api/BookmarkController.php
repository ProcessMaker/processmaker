<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ProcessCollection;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        // Get the user
        $user = Auth::user();
        $perPage = $this->getPerPage($request);
        // Get the processes  active
        $processes = Process::nonSystem()->active();
        // Filter pmql
        $pmql = $request->input('pmql', '');
        if (!empty($pmql)) {
            try {
                $processes->pmql($pmql);
            } catch (\ProcessMaker\Query\SyntaxError $e) {
                return response(['error' => 'PMQL error'], 400);
            }
        }

        $launchpad = $request->input('launchpad', false);
        // Get the processes
        $processes = $processes
            ->select('processes.*', 'bookmark.id as bookmark_id')
            ->leftJoin('user_process_bookmarks as bookmark', 'bookmark.process_id', '=', 'processes.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id') // Required for the pmql
            ->where('bookmark.user_id', $user->id)
            ->orderBy('processes.name', 'asc')
            ->paginate($perPage);
        
        foreach ($processes as $process) {
            // Get the launchpad configuration
            $process->launchpad = ProcessLaunchpad::getLaunchpad($launchpad, $process->id);
        }

        return new ProcessCollection($processes);
    }

    /**
     * Get the size of the page.
     *
     * @param Request $request
     * @return type
     */
    protected function getPerPage(Request $request)
    {
        return $request->input('per_page', 12);
    }

    public function store(Request $request, Process $process)
    {
        $bookmark = new Bookmark();
        try {
            $newBookmark = $bookmark->updateOrCreate([
                'process_id' => $process->id,
                'user_id' => Auth::user()->id,
            ]);
            $bookmark->newId = $newBookmark->id;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return new ApiResource($bookmark->refresh());
    }

    public function destroy(Bookmark $bookmark)
    {
        // Delete bookmark
        $bookmark->delete();

        return response([], 204);
    }
}
