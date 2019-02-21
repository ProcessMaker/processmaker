<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessWebhook;
use ProcessMaker\Http\Resources\ProcessWebhook as WebhookResource;
use Ramsey\Uuid\Uuid;

class ProcessWebhookController extends Controller
{
    /**
     * Display the specified webhook.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebhook
     * 
     * @OA\Get(
     *     path="/processes/{process_id}/webhooks/",
     *     summary="Get the webhook for a start node",
     *     operationId="getProcessWebhook",
     *     tags={"Process Webhooks"},
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
     *         description="Successfully found the webhook",
     *     ),
     * )
     */
    public function show(Request $request, Process $process)
    {
        $node = $request->input('node');
        $result = ProcessWebhook::where([
            'process_id' => $process->id,
            'node' => $node,
        ])->first();

        return new WebhookResource($result);
    }

    /**
     * Save a new webhook.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebhook
     * 
     * @OA\Post(
     *     path="/processes/{process_id}/webhooks/",
     *     summary="Save a new webhook for a start node",
     *     operationId="createProcessWebhook",
     *     tags={"Process Webhooks"},
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
     *         description="Successfully saved the webhook",
     *     ),
     * )
     */
    public function store(Request $request, Process $process)
    {
        $result = ProcessWebhook::create([
            'process_id' => $process->id,
            'node' => $request->input('node'),
            'token' => Uuid::uuid4()->toString(),
        ]);
        return new WebhookResource($result);
    }

    /**
     * Delete a webhook.
     *
     * @param Request $request
     * @param Process $process
     *
     * @return \ProcessMaker\Http\Resources\ProcessWebhook
     * 
     * @OA\Delete(
     *     path="/processes/{process_id}/webhooks/",
     *     summary="Delete (revoke) a webhook for a start node",
     *     operationId="deleteProcessWebhook",
     *     tags={"Process Webhooks"},
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
     *         description="Successfully deleted the webhook",
     *     ),
     * )
     */
    public function destroy(Request $request, Process $process)
    {
        $node = $request->input('node');
        $webhook = ProcessWebhook::where([
            'process_id' => $process->id,
            'node' => $node,
        ])->firstOrFail();

        $webhook->delete();

        return response(null, 204);
    }
}