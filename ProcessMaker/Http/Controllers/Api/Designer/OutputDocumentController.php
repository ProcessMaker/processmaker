<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\OutputDocumentManager;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\OutputDocumentTransformer;

class OutputDocumentController
{
    /**
     * Get a list of OutPut Documents in a project.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = OutputDocumentManager::index($process);
        return fractal($response, new OutputDocumentTransformer())->respond(200);
    }

    /**
     * Get a single OutPut Document in a project.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, OutputDocument $outputDocument)
    {
        $this->belongsToProcess($process, $outputDocument);
        return fractal($outputDocument, new OutputDocumentTransformer())->respond(200);
    }

    /**
     * Create a new OutPut Document in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'report_generator' => $request->input('report_generator', 'HTML2PDF'),
            'generate' => $request->input('generate', 'BOTH'),
            'type' => $request->input('type', 'HTML'),
            'versioning' => $request->input('versioning', 0),
            'properties' => $request->input('properties', []),
        ];

        $data = array_merge($data, $this->formatData($request, ['title', 'description', 'filename', 'template',
            'type', 'current_revision', 'tags', 'open_type']));

        $response = OutputDocumentManager::save($process, $data);
        return fractal($response, new OutputDocumentTransformer())->respond(201);
    }

    /**
     * Update a OutPut Document in a project.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, OutputDocument $outputDocument, Request $request)
    {
        $this->belongsToProcess($process, $outputDocument);
        $data = $this->formatData($request, ['title', 'description', 'filename', 'template', 'report_generator',
            'type', 'versioning', 'current_revision', 'tags', 'open_type', 'generate', 'properties']);

        if ($data) {
            OutputDocumentManager::update($process, $outputDocument, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a OutPut Document in a project.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, OutputDocument $outputDocument)
    {
        $this->belongsToProcess($process, $outputDocument);
        OutputDocumentManager::remove($outputDocument);
        return response([], 204);
    }

    /**
     * Validate if OutPut Document belong to process.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, OutputDocument $outputDocument): void
    {
        if ($process->id !== $outputDocument->process_id) {
            Throw new DoesNotBelongToProcessException(__('The OutPut Document does not belong to this process.'));
        }
    }

    /**
     * Format in capital letters to send information.
     *
     * @param Request $request
     * @param array $fields
     *
     * @return array
     */
    private function formatData(Request $request, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }
        return $data;
    }

}
