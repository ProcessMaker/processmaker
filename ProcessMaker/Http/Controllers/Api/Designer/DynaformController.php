<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\DynaformManager;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;

class DynaformController
{
    /**
     * Get a list of Dynaforms in a project.
     *
     * @param Process $process
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process)
    {
        $response = DynaformManager::index($process);
        return response($response, 200);
    }

    /**
     * Get a single Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Dynaform $dynaform)
    {
        $this->belongsToProcess($process, $dynaform);
        return response($dynaform->toArray(), 200);
    }

    /**
     * Create a new Dynaform in a project.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $data = [
            'DYN_TITLE' => $request->input('dyn_title', ''),
            'DYN_DESCRIPTION' => $request->input('dyn_description', ''),
            'DYN_TYPE' => $request->input('dyn_type', 'FORM')
        ];
        $data = array_merge($data, $this->formatData($request, ['dyn_content']));

        if ($request->has('copy_import')) {
            $data['COPY_IMPORT'] = $request->input('copy_import');
            return response(DynaformManager::copyImport($process, $data), 201);
        }
        if ($request->has('pmtable')) {
            $data['PM_TABLE'] = $request->input('pmtable');
            return response(DynaformManager::createBasedPmTable($process, $data), 201);
        }
        $response = DynaformManager::save($process, $data);
        return response($response, 201);
    }

    /**
     * Update a Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Dynaform $dynaform, Request $request)
    {
        $this->belongsToProcess($process, $dynaform);
        $data = $this->formatData($request, ['out_doc_title', 'out_doc_description', 'out_doc_filename', 'out_doc_template', 'out_doc_report_generator',
            'out_doc_landscape', 'out_doc_media', 'out_doc_left_margin', 'out_doc_right_margin', 'out_doc_top_margin',
            'out_doc_bottom_margin', 'out_doc_generate', 'out_doc_type', 'out_doc_versioning', 'out_doc_destination_path',
            'out_doc_tags', 'out_doc_pdf_security_enabled', 'out_doc_pdf_security_open_password', 'out_doc_pdf_security_owner_password']);

        if ($data) {
            DynaformManager::update($process, $dynaform, $data);
        }
        return response([], 200);
    }

    /**
     * Delete a Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Dynaform $dynaform)
    {
        $this->belongsToProcess($process, $dynaform);
        DynaformManager::remove($dynaform);
        return response([], 200);
    }

    /**
     * Validate if Dynaform belong to process.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Dynaform $dynaform): void
    {
        if ($process->PRO_ID !== $dynaform->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The Dynaform does not belong to this process.'));
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
    private function formatData(Request $request, array $fields)
    {
        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[strtoupper($field)] = $request->input($field);
            }
        }
        if ($request->has('out_doc_pdf_security_permissions')) {
            $permissions = $request->input('out_doc_pdf_security_permissions');
            $data[strtoupper('OUT_DOC_PDF_SECURITY_PERMISSIONS')] = is_array($permissions) ? $permissions : explode('|', $permissions);
        }
        return $data;
    }

}
