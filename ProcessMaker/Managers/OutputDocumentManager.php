<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;

class OutputDocumentManager
{
    /**
     * Get a list of All OutPut Documents in a project.
     *
     * @param Process $process
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process): LengthAwarePaginator
    {
        return OutputDocument::where('process_id', $process->id)->paginate(10);
    }

    /**
     * Create a new OutPut Document in a project.
     *
     * @param Process $process
     * @param array $data
     *
     * @return OutputDocument
     * @throws \Throwable
     */
    public function save(Process $process, $data): OutputDocument
    {
        $this->validate($data);

        $data['process_id'] = $process->id;

        $outputDocument = new OutputDocument();
        $outputDocument->fill($data);
        $outputDocument->saveOrFail();

        return $outputDocument;
    }

    /**
     * Update OutPut Document in a project.
     *
     * @param Process $process
     * @param OutputDocument $outputDocument
     * @param array $data
     *
     * @return OutputDocument
     * @throws \Throwable
     */
    public function update(Process $process, OutputDocument $outputDocument, $data): OutputDocument
    {
        $data['process_id'] = $process->id;
        $outputDocument->fill($data);
        $this->validate($outputDocument->toArray());
        $outputDocument->saveOrFail();
        return $outputDocument;
    }


    /**
     * Remove OutPut Document in a project.
     *
     * @param OutputDocument $outputDocument
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(OutputDocument $outputDocument): ?bool
    {
        return $outputDocument->delete();
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
                'report_generator' => 'required|in:' . implode(',', OutputDocument::DOC_REPORT_GENERATOR_TYPE),
                'generate' => 'required|in:' . implode(',', OutputDocument::DOC_GENERATE_TYPE),
                'type' => 'required|in:' . implode(',', OutputDocument::DOC_TYPE)
            ]
        );

        if (!empty($data['pdf_security_permissions'])) {
            $validator->sometimes('pdf_security_permissions', 'array', function($value) {
                foreach ($value->getAttributes()['pdf_security_permissions'] as $val) {
                    if (!in_array($val, OutputDocument::PDF_SECURITY_PERMISSIONS_TYPE, true)) {
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
