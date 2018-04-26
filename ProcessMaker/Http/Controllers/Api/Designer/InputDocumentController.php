<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\InputDocumentManager;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;

class InputDocumentController
{
    /**
     * Get a list of Input Documents in a project.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = InputDocumentManager::index($process);
        return response($response, 200);
    }

    /**
     * Get a single Input Document in a project.
     *
     * @param Process $process
     * @param InputDocument $inputDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, InputDocument $inputDocument)
    {
        $this->belongsToProcess($process, $inputDocument);
        return response($inputDocument->toArray(), 200);
    }

    /**
     * Create a new Input Document in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'INP_DOC_TITLE' => $request->input('inp_doc_title', ''),
            'INP_DOC_DESCRIPTION' => $request->input('inp_doc_description', ''),
            'INP_DOC_FORM_NEEDED' => $request->input('inp_doc_form_needed', 'REAL'),
            'INP_DOC_ORIGINAL' => $request->input('inp_doc_original', 'COPY'),
            'INP_DOC_PUBLISHED' => $request->input('inp_doc_published', 'PRIVATE'),
            'INP_DOC_VERSIONING' => $request->input('inp_doc_versioning', 0),
            'INP_DOC_DESTINATION_PATH' => $request->input('inp_doc_destination_path', ''),
            'INP_DOC_TAGS' => $request->input('inp_doc_tags', 'INPUT')
        ];

        $response = InputDocumentManager::save($process, $data);
        return response($response, 201);
    }

    /**
     * Update a Input Document in a project.
     *
     * @param Process $process
     * @param InputDocument $inputDocument
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, InputDocument $inputDocument, Request $request)
    {
        $this->belongsToProcess($process, $inputDocument);
        $data = [];
        $fields = ['inp_doc_title', 'inp_doc_description', 'inp_doc_original', 'inp_doc_form_needed', 'inp_doc_published',
            'inp_doc_versioning', 'inp_doc_destination_path', 'inp_doc_tags'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[strtoupper($field)] = $request->input($field);
            }
        }

        if ($data) {
            InputDocumentManager::update($process, $inputDocument, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Input Document in a project.
     *
     * @param Process $process
     * @param InputDocument $inputDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, InputDocument $inputDocument)
    {
        $this->belongsToProcess($process, $inputDocument);
        InputDocumentManager::remove($inputDocument);
        return response([], 200);
    }

    /**
     * Validate if Input Document belong to process.
     *
     * @param Process $process
     * @param InputDocument $inputDocument
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, InputDocument $inputDocument): void
    {
        if ($process->PRO_ID !== $inputDocument->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The Input Document does not belong to this process.'));
        }
    }

}
