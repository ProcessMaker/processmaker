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
        return OutPutDocument::where('PRO_UID', $process->PRO_UID)->simplePaginate(20);
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

        $data['OUT_DOC_UID'] = str_replace('-', '', Uuid::uuid4());
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;

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
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;
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
                'OUT_DOC_REPORT_GENERATOR' => 'required|in:' . implode(',', OutPutDocument::DOC_REPORT_GENERATOR_TYPE),
                'OUT_DOC_GENERATE' => 'required|in:' . implode(',', OutPutDocument::DOC_GENERATE_TYPE),
                'OUT_DOC_TYPE' => 'required|in:' . implode(',', OutPutDocument::DOC_TYPE)
            ]
        );

        if (!empty($data['OUT_DOC_PDF_SECURITY_PERMISSIONS'])) {
            $validator->sometimes('OUT_DOC_PDF_SECURITY_PERMISSIONS', 'array', function($value) {
                foreach ($value->getAttributes()['OUT_DOC_PDF_SECURITY_PERMISSIONS'] as $val) {
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
