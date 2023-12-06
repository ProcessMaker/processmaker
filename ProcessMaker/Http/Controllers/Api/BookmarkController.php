<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ProcessCollection;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Process;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        // Get the user
        $user = Auth::user();
        // Get the order by to apply
        $orderBy = $this->getRequestSortBy($request, 'name');
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
            ->select('processes.*')
            ->leftJoin('user_process_bookmarks as bookmark', 'bookmark.process_id', '=', 'processes.id')
            ->where('bookmark.user_id', $user->id)
            ->orderBy(...$orderBy)
            ->get()
            ->collect();

        return new ProcessCollection($processes);
    }

    public function store(Request $request, Process $process)
    {
        $bookmark = new Bookmark();
        $bookmark->fill([
            'process_id' => $process->id,
            'user_id' => Auth::user()->id
        ]);
        try {
            $bookmark->saveOrFail();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, Process $process)
    {
        // Get the user
        $user = Auth::user();
    }
}
