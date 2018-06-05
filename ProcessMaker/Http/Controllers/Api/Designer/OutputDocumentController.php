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
     * Get a list of Output Documents in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process, Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('current_page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('sort_by', 'title'),
            'sort_order' => $request->input('sort_order', 'ASC'),
        ];
        $response = OutputDocumentManager::index($process, $options);
        return fractal($response, new OutputDocumentTransformer())->respond(200);
    }

    /**
     * Get a single Output Document in a process.
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
     * Create a new Output Document in a process.
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
            'current_revision' => $request->input('current_revision', 0),
            'open_type' => $request->input('open_type', 0),
            'versioning' => $request->input('versioning', 0),
            'properties' => $request->input('properties', []),
        ];

        $data = array_merge($data, $this->formatData($request, ['title', 'description', 'filename', 'template',
            'type', 'tags']));

        $response = OutputDocumentManager::save($process, $data);
        return fractal($response, new OutputDocumentTransformer())->respond(201);
    }

    /**
     * Update a Output Document in a process.
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
     * Delete a Output Document in a process.
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
     * Validate if Output Document belong to process.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, OutputDocument $outputDocument): void
    {
        if ($process->id !== $outputDocument->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Output Document does not belong to this process.'));
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
