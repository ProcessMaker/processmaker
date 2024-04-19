<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Http\Resources\ProcessCollection;
use ProcessMaker\Models\Bookmark;
use ProcessMaker\Models\Embed;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessLaunchpad;

class ProcessLaunchpadController extends Controller
{
    public function getProcesses(Request $request)
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

        // Get with bookmark
        $bookmark = $request->input('bookmark', false);
        // Get with launchpad
        $launchpad = $request->input('launchpad', false);
        // Get the processes
        $processes = $processes
            ->select('processes.*')
            ->orderBy('processes.name', 'asc')
            ->get()
            ->collect();

        foreach ($processes as $process) {
            // Get the id bookmark related
            $process->bookmark_id = Bookmark::getBookmarked($bookmark, $process->id, $user->id);
            // Get the launchpad configuration
            $process->launchpad = ProcessLaunchpad::getLaunchpad($launchpad, $process->id);
        }

        return new ProcessCollection($processes);
    }

    public function index(Request $request, Process $process)
    {
        // Get the processes launchpad configuration
        // Get the images related
        // Get the embed related
        $processes = Process::with('launchpad')
            ->with(['media' => function ($query) {
                $query->orderBy('order_column', 'asc');
            }])
            ->with(['embed' => function ($query) {
                $query->orderBy('order_column', 'asc');
            }])
            ->where('id', $process->id)
            ->get()
            ->toArray();

        return new ApiResource($processes);
    }

    public function store(Request $request, Process $process)
    {
        $launch = new ProcessLaunchpad();
        $properties = $request->input('properties');
        try {
            // Store the launchpad configuration
            $newLaunch = $launch->updateOrCreate([
                'process_id' => $process->id,
            ], [
                'user_id' => Auth::user()->id,
                'properties' => $properties,
            ]);
            $launch->newId = $newLaunch->id;
            // If there are configure the carousel
            if ($request->has('imagesCarousel')) {
                $this->saveContentCarousel($request, $process);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return new ApiResource($launch->refresh());
    }

    public function destroy(ProcessLaunchpad $launch)
    {
        // Delete launchpad configuration
        $launch->delete();

        return response([], 204);
    }

   /**
    * Store the elements related to the carousel [IMAGE, EMBED URL]
    */
    public function saveContentCarousel(Request $request, Process $process)
    {
        $contentCarousel = $request->input('imagesCarousel');
        if (!empty($contentCarousel)) {
            foreach ($contentCarousel as $row) {
                $content = !empty($row['type']) ? $row['type'] : '';
                switch ($content) {
                    case 'image':
                        // Store the images related into the Media table
                        $media = new Media();
                        $media->saveProcessMedia($process, $row, 'uuid');
                        break;
                    case 'embed':
                        // Store the embed related into the Embed table
                        $embed = new Embed();
                        $embed->saveProcessEmbed($process, $row, 'uuid');
                        break;
                    default:
                        // Nothing
                        break;
                }

            }
        }
    }

    public function deleteEmbed(Request $request, Process $process)
    {
        // Get UUID in the table
        $uuid = $request->input('uuid');

        $embedUrl = Embed::where('uuid', $uuid)
            ->first();

        // Check if embed before delete
        if ($embedUrl) {
            $embedUrl->delete();
        }
    }
}
