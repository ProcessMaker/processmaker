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
    public function show(Request $request, Process $process)
    {
        $node = $request->input('node');
        $result = ProcessWebhook::where([
            'process_id' => $process->id,
            'node' => $node,
        ])->first();

        return new WebhookResource($result);
    }

    public function store(Request $request, Process $process)
    {
        $result = ProcessWebhook::create([
            'process_id' => $process->id,
            'node' => $request->input('node'),
            'token' => Uuid::uuid4()->toString(),
        ]);
        return new WebhookResource($result);
    }

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