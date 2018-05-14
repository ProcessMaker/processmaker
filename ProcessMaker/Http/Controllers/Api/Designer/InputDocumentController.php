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
            'title' => $request->input('inp_doc_title', ''),
            'description' => $request->input('inp_doc_description', ''),
            'form_needed' => $request->input('inp_doc_form_needed', 'REAL'),
            'original' => $request->input('inp_doc_original', 'COPY'),
            'published' => $request->input('inp_doc_published', 'PRIVATE'),
            'versioning' => $request->input('inp_doc_versioning', 0),
            'destination_path' => $request->input('inp_doc_destination_path', ''),
            'tags' => $request->input('inp_doc_tags', 'INPUT')
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
                $data[substr($field, 8)] = $request->input($field);
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
        return response([], 204);
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
        if ($process->id !== $inputDocument->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Input Document does not belong to this process.'));
        }
    }

}
