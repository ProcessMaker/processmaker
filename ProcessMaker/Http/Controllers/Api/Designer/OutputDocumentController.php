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
        return response()->collection($response, new OutputDocumentTransformer(), 200);
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
        return response()->item($outputDocument, new OutputDocumentTransformer(), 200);
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
            'landscape' => $request->input('landscape', 0),
            'left_margin' => $request->input('left_margin', 30),
            'right_margin' => $request->input('right_margin', 15),
            'top_margin' => $request->input('top_margin', 15),
            'bottom_margin' => $request->input('bottom_margin', 15),
            'generate' => $request->input('generate', 'BOTH'),
            'type' => $request->input('type', 'HTML'),
            'versioning' => $request->input('versioning', 0),
            'pdf_security_enabled' => $request->input('pdf_security_enabled', 0)
        ];

        $data = array_merge($data, $this->formatData($request, ['title', 'description', 'filename', 'template',
            'media', 'destination_path', 'tags', 'pdf_security_open_password',
            'pdf_security_owner_password']));

        $response = OutputDocumentManager::save($process, $data);
        return response()->item($response, new OutputDocumentTransformer(), 201);
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
            'landscape', 'media', 'left_margin', 'right_margin', 'top_margin',
            'bottom_margin', 'generate', 'type', 'versioning', 'destination_path',
            'tags', 'pdf_security_enabled', 'pdf_security_open_password', 'pdf_security_owner_password']);

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
        if ($request->has('pdf_security_permissions')) {
            $permissions = $request->input('pdf_security_permissions');
            $data['pdf_security_permissions'] = is_array($permissions) ? $permissions : explode('|' , $permissions);
        }
        return $data;
    }

}
