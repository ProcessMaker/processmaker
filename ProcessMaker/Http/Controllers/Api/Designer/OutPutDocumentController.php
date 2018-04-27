<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\OutPutDocumentManager;
use ProcessMaker\Model\OutPutDocument;
use ProcessMaker\Model\Process;

class OutPutDocumentController
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
        $response = OutPutDocumentManager::index($process);
        return response($response, 200);
    }

    /**
     * Get a single OutPut Document in a project.
     *
     * @param Process $process
     * @param OutPutDocument $outPutDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, OutPutDocument $outPutDocument)
    {
        $this->belongsToProcess($process, $outPutDocument);
        return response($outPutDocument->toArray(), 200);
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
            'OUT_DOC_REPORT_GENERATOR' => $request->input('out_doc_report_generator', 'HTML2PDF'),
            'OUT_DOC_LANDSCAPE' => $request->input('out_doc_landscape', 0),
            'OUT_DOC_LEFT_MARGIN' => $request->input('out_doc_left_margin', 30),
            'OUT_DOC_RIGHT_MARGIN' => $request->input('out_doc_right_margin', 15),
            'OUT_DOC_TOP_MARGIN' => $request->input('out_doc_top_margin', 15),
            'OUT_DOC_BOTTOM_MARGIN' => $request->input('out_doc_bottom_margin', 15),
            'OUT_DOC_GENERATE' => $request->input('out_doc_generate', 'BOTH'),
            'OUT_DOC_TYPE' => $request->input('out_doc_type', 'HTML'),
            'OUT_DOC_VERSIONING' => $request->input('out_doc_versioning', 0),
            'OUT_DOC_PDF_SECURITY_ENABLED' => $request->input('out_doc_pdf_security_enabled', 0)
        ];

        $fields = ['out_doc_title', 'out_doc_description', 'out_doc_filename', 'out_doc_template',
            'out_doc_media', 'out_doc_destination_path', 'out_doc_tags', 'out_doc_pdf_security_open_password',
            'out_doc_pdf_security_owner_password', 'out_doc_pdf_security_permission'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[strtoupper($field)] = $request->input($field);
            }
        }

        $response = OutPutDocumentManager::save($process, $data);
        return response($response, 201);
    }

    /**
     * Update a OutPut Document in a project.
     *
     * @param Process $process
     * @param OutPutDocument $outPutDocument
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, OutPutDocument $outPutDocument, Request $request)
    {
        $this->belongsToProcess($process, $outPutDocument);
        $data = [];
        $fields = ['out_doc_title', 'out_doc_description', 'out_doc_filename', 'out_doc_template', 'out_doc_report_generator', 'out_doc_landscape', 'out_doc_media',
            'out_doc_left_margin', 'out_doc_right_margin', 'out_doc_top_margin', 'out_doc_bottom_margin',
            'out_doc_generate', 'out_doc_type', 'out_doc_versioning', 'out_doc_destination_path', 'out_doc_tags',
            'out_doc_pdf_security_enabled', 'out_doc_pdf_security_open_password', 'out_doc_pdf_security_owner_password',
            'out_doc_pdf_security_permission'];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[strtoupper($field)] = $request->input($field);
            }
        }

        if ($data) {
            OutPutDocumentManager::update($process, $outPutDocument, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a OutPut Document in a project.
     *
     * @param Process $process
     * @param OutPutDocument $outPutDocument
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, OutPutDocument $outPutDocument)
    {
        $this->belongsToProcess($process, $outPutDocument);
        OutPutDocumentManager::remove($outPutDocument);
        return response([], 200);
    }

    /**
     * Validate if OutPut Document belong to process.
     *
     * @param Process $process
     * @param OutPutDocument $outPutDocument
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, OutPutDocument $outPutDocument): void
    {
        if ($process->PRO_ID !== $outPutDocument->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The OutPut Document does not belong to this process.'));
        }
    }

}
