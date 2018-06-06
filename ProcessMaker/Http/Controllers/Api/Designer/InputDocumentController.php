<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\InputDocumentManager;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;
use ProcessMaker\Transformers\InputDocumentTransformer;

class InputDocumentController
{
    /**
     * Get a list of Input Documents in a process.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = InputDocumentManager::index($process);
        return fractal($response, new InputDocumentTransformer())->respond(200);
    }

    /**
     * Get a single Input Document in a process.
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
        return fractal($inputDocument, new InputDocumentTransformer())->respond(200);
    }

    /**
     * Create a new Input Document in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'title' => $request->input('title', ''),
            'description' => $request->input('description', ''),
            'form_needed' => $request->input('form_needed', 'REAL'),
            'original' => $request->input('original', 'COPY'),
            'published' => $request->input('published', 'PRIVATE'),
            'versioning' => $request->input('versioning', 0),
            'destination_path' => $request->input('destination_path', ''),
            'tags' => $request->input('tags', 'INPUT')
        ];

        $response = InputDocumentManager::save($process, $data);
        return fractal($response, new InputDocumentTransformer())->respond(201);
    }

    /**
     * Update a Input Document in a process.
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
        $fields = ['title', 'description', 'original', 'form_needed', 'published',
            'versioning', 'destination_path', 'tags'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if ($data) {
            InputDocumentManager::update($process, $inputDocument, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Input Document in a process.
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
    private function belongsToProcess(Process $process, InputDocument $inputDocument)
    {
        if ($process->id !== $inputDocument->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Input Document does not belong to this process.'));
        }
    }

}
