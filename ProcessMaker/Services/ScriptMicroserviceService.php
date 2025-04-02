<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;

class ScriptMicroserviceService
{
    public function handle(Request $request)
    {
        $response = $request->all();
        Log::debug('Response microservice executor: ' . print_r($response, true));
        // If the call is from preview
        if (!empty($response['metadata']['nonce'])) {
            $formattedResponse = $this->formatPreviewResponse($response);
            event(new ScriptResponseEvent(
                User::find($response['metadata']['current_user']),
                $formattedResponse['status'],
                $formattedResponse['output'],
                null,
                $response['metadata']['nonce']));
        }
        if (!empty($response['metadata']['script_task'])) {
            $script = Script::find($response['metadata']['script_task']['script_id']);
            $definitions = Definitions::find($response['metadata']['script_task']['definition_id']);
            $instance = ProcessRequest::find($response['metadata']['script_task']['instance_id']);
            $token = ProcessRequestToken::find($response['metadata']['script_task']['token_id']);
            if ($response['status'] === 'success') {
                CompleteActivity::dispatch($definitions, $instance, $token, $response['output'])->onQueue('bpmn');
            }
        }
    }

    /**
     * Format preview response data
     *
     * @param array $response
     * @return array{status: int, output: array}
     */
    private function formatPreviewResponse(array $response): array
    {
        // Simple status determination: success = 200, others = 500
        $status = $response['status'] === 'success' ? 200 : 500;

        return [
            'status' => $status,
            'output' => $this->formatPreviewOutput($response),
        ];
    }

    /**
     * Format preview output data
     *
     * @param array $response
     * @return array
     */
    private function formatPreviewOutput(array $response): array
    {
        // For successful responses, return just the output array
        if (($response['status'] ?? '') === 'success') {
            return [
                'output' => $response['output'],
            ];
        }

        // For error responses, include error details
        $output = $response;

        if (($response['status'] ?? '') === 'error') {
            $output['exception'] = $this->extractErrorDetails($response);
            $output['status'] = 'error';
        }

        return $output;
    }

    /**
     * Extract error details from response
     *
     * @param array $response
     * @return string
     */
    private function extractErrorDetails(array $response): string
    {
        if (isset($response['output']['error'])) {
            return $response['output']['error'];
        }

        if (isset($response['message'])) {
            return $response['message'];
        }

        return 'Unknown error occurred';
    }
}
