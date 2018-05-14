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
            'report_generator' => $request->input('out_doc_report_generator', 'HTML2PDF'),
            'landscape' => $request->input('out_doc_landscape', 0),
            'left_margin' => $request->input('out_doc_left_margin', 30),
            'right_margin' => $request->input('out_doc_right_margin', 15),
            'top_margin' => $request->input('out_doc_top_margin', 15),
            'bottom_margin' => $request->input('out_doc_bottom_margin', 15),
            'generate' => $request->input('out_doc_generate', 'BOTH'),
            'type' => $request->input('out_doc_type', 'HTML'),
            'versioning' => $request->input('out_doc_versioning', 0),
            'pdf_security_enabled' => $request->input('out_doc_pdf_security_enabled', 0)
        ];

        $data = array_merge($data, $this->formatData($request, ['out_doc_title', 'out_doc_description', 'out_doc_filename', 'out_doc_template',
            'out_doc_media', 'out_doc_destination_path', 'out_doc_tags', 'out_doc_pdf_security_open_password',
            'out_doc_pdf_security_owner_password']));

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
        $data = $this->formatData($request, ['out_doc_title', 'out_doc_description', 'out_doc_filename', 'out_doc_template', 'out_doc_report_generator',
            'out_doc_landscape', 'out_doc_media', 'out_doc_left_margin', 'out_doc_right_margin', 'out_doc_top_margin',
            'out_doc_bottom_margin', 'out_doc_generate', 'out_doc_type', 'out_doc_versioning', 'out_doc_destination_path',
            'out_doc_tags', 'out_doc_pdf_security_enabled', 'out_doc_pdf_security_open_password', 'out_doc_pdf_security_owner_password']);

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
        return response([], 204);
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
        if ($process->id !== $outPutDocument->process_id) {
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
                $data[substr($field, 8)] = $request->input($field);
            }
        }
        if ($request->has('out_doc_pdf_security_permissions')) {
            $permissions = $request->input('out_doc_pdf_security_permissions');
            $data['pdf_security_permissions'] = is_array($permissions) ? $permissions : explode('|' , $permissions);
        }
        return $data;
    }

}
