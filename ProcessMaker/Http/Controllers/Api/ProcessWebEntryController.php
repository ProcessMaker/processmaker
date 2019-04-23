<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessWebEntry;
use ProcessMaker\Http\Resources\ProcessWebEntry as WebEntryResource;
use Ramsey\Uuid\Uuid;

class ProcessWebEntryController extends Controller
{
    /**
     * Display the specified web entry.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebEntry
     * 
     * @OA\Get(
     *     path="/processes/{process_id}/web_entries/",
     *     summary="Get the web entry for a start node",
     *     operationId="getProcessWebEntry",
     *     tags={"Process Web Entries"},
     *     @OA\Parameter(
     *         name="process_id",
     *         description="ID of process",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="node",
     *         description="Start event node ID",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully found the web entry",
     *     ),
     * )
     */
    public function show(Request $request, Process $process)
    {
        $node = $request->input('node');
        $result = ProcessWebEntry::where([
            'process_id' => $process->id,
            'node' => $node,
        ])->first();

        return new WebEntryResource($result);
    }

    /**
     * Save a new web entry.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebEntry
     * 
     * @OA\Post(
     *     path="/processes/{process_id}/web_entries/",
     *     summary="Save a new web entry for a start node",
     *     operationId="createProcessWebEntry",
     *     tags={"Process Web Entries"},
     *     @OA\Parameter(
     *         name="process_id",
     *         description="ID of process",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="node",
     *         description="Start event node ID",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully saved the web entry",
     *     ),
     * )
     */
    public function store(Request $request, Process $process)
    {
        $result = ProcessWebEntry::create([
            'process_id' => $process->id,
            'node' => $request->input('node'),
            'token' => Uuid::uuid4()->toString(),
        ]);
        return new WebEntryResource($result);
    }

    /**
     * Delete a web entry.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebEntry
     * 
     * @OA\Delete(
     *     path="/processes/{process_id}/web_entries/",
     *     summary="Delete (revoke) a web entries for a start node",
     *     operationId="deleteProcessWebEntry",
     *     tags={"Process Web Entries"},
     *     @OA\Parameter(
     *         name="process_id",
     *         description="ID of process",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="node",
     *         description="Start event node ID",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Successfully deleted the web entry",
     *     ),
     * )
     */
    public function destroy(Request $request, Process $process)
    {
        $node = $request->input('node');
        $web_entry = ProcessWebEntry::where([
            'process_id' => $process->id,
            'node' => $node,
        ])->firstOrFail();

        $web_entry->delete();

        return response(null, 204);
    }
}