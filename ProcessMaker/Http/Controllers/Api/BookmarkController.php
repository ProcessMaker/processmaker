<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ProcessCollection;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Process;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        // Get the user
        $user = Auth::user();
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
        // Get the processes
        $processes = $processes
            ->select('processes.*', 'bookmark.id as bookmark_id')
            ->leftJoin('user_process_bookmarks as bookmark', 'bookmark.process_id', '=', 'processes.id')
            ->leftJoin('users as user', 'processes.user_id', '=', 'user.id') // Required for the pmql
            ->where('bookmark.user_id', $user->id)
            ->orderBy('processes.name', 'asc')
            ->get()
            ->collect();

        return new ProcessCollection($processes);
    }

    public function store(Request $request, Process $process)
    {
        $bookmark = new Bookmark();
        try {
            $bookmark->updateOrCreate([
                'process_id' => $process->id,
                'user_id' => Auth::user()->id,
            ]);
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
