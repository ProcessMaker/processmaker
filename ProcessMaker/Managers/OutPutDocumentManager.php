<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\OutPutDocument;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

class OutPutDocumentManager
{

    /**
     * Get a list of All OutPut Documents in a project.
     *
     * @param Process $process
     *
     * @return Paginator
     */
    public function index(Process $process): Paginator
    {
        return OutPutDocument::where('process_id', $process->id)->simplePaginate(20);
    }

    /**
     * Create a new OutPut Document in a project.
     *
     * @param Process $process
     * @param array $data
     *
     * @return OutPutDocument
     * @throws \Throwable
     */
    public function save(Process $process, $data): OutPutDocument
    {
        $this->validate($data);

        //$data['OUT_DOC_UID'] = str_replace('-', '', Uuid::uuid4());
        //$data['PRO_UID'] = $process->PRO_UID;
        $data['process_id'] = $process->id;

        $outPutDocument = new OutPutDocument();
        $outPutDocument->fill($data);
        $outPutDocument->saveOrFail();

        return $outPutDocument;
    }

    /**
     * Update OutPut Document in a project.
     *
     * @param Process $process
     * @param OutPutDocument $outPutDocument
     * @param array $data
     *
     * @return OutPutDocument
     * @throws \Throwable
     */
    public function update(Process $process, OutPutDocument $outPutDocument, $data): OutPutDocument
    {
        $data['process_id'] = $process->id;
        $outPutDocument->fill($data);
        $this->validate($outPutDocument->toArray());
        $outPutDocument->saveOrFail();
        return $outPutDocument;
    }


    /**
     * Remove OutPut Document in a project.
     *
     * @param OutPutDocument $outPutDocument
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(OutPutDocument $outPutDocument): ?bool
    {
        return $outPutDocument->delete();
    }

    /**
     * Validate extra rules
     *
     * @param array $data
     *
     * @throws ValidationException
     */
    private function validate($data): void
    {
        /**
         * @var $validator \Illuminate\Validation\Validator
         */
        $validator = Validator::make(
            $data,
            [
                'report_generator' => 'required|in:' . implode(',', OutPutDocument::DOC_REPORT_GENERATOR_TYPE),
                'generate' => 'required|in:' . implode(',', OutPutDocument::DOC_GENERATE_TYPE),
                'type' => 'required|in:' . implode(',', OutPutDocument::DOC_TYPE)
            ]
        );

        if (!empty($data['pdf_security_permissions'])) {
            $validator->sometimes('pdf_security_permissions', 'array', function($value) {
                foreach ($value->getAttributes()['pdf_security_permissions'] as $val) {
                    if (!in_array($val, OutPutDocument::PDF_SECURITY_PERMISSIONS_TYPE, true)) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

}
