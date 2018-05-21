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
        $data['properties'] = $this->dataProperties($data['properties']);
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
        if (isset($data['properties'])) {
            $data['properties'] = array_merge($outputDocument->properties, $data['properties']);
        }
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
                'type' => 'required|in:' . implode(',', OutputDocument::DOC_TYPE),
                'properties.landscape' => 'required|boolean',
                'properties.media' => 'required',
                'properties.left_margin' => 'required|min:0',
                'properties.right_margin' => 'required|min:0',
                'properties.top_margin' => 'required|min:0',
                'properties.bottom_margin' => 'required|min:0',
                'properties.pdf_security_enabled' => 'required|boolean',
                'properties.pdf_security_permissions' => 'present|array',
                'properties.pdf_security_permissions.*' => 'required_without:properties.pdf_security_permissions|in: "",' . implode(',', OutputDocument::PDF_SECURITY_PERMISSIONS_TYPE),
            ]
        );

        /*if (!empty($data['pdf_security_permissions'])) {
            $validator->sometimes('pdf_security_permissions', 'array', function($value) {
                foreach ($value->getAttributes()['pdf_security_permissions'] as $val) {
                    if (!in_array($val, OutputDocument::PDF_SECURITY_PERMISSIONS_TYPE, true)) {
                        return false;
                    }
                }
                return true;
            });
        }*/

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Parameter by default in Output Document properties
     *
     * @param array $properties
     *
     * @return array
     */
    private function dataProperties($properties): array
    {
        $properties['landscape'] = isset($properties['landscape']) ? $properties['landscape'] : 0;
        $properties['media'] = isset($properties['media']) ? $properties['media'] : 'letter';
        $properties['left_margin'] = isset($properties['left_margin']) ? $properties['left_margin'] : 30;
        $properties['right_margin'] = isset($properties['right_margin']) ? $properties['right_margin'] : 15;
        $properties['top_margin'] = isset($properties['top_margin']) ? $properties['top_margin'] : 15;
        $properties['bottom_margin'] = isset($properties['bottom_margin']) ? $properties['bottom_margin'] : 15;
        $properties['field_mapping'] = isset($properties['field_mapping']) ? $properties['field_mapping'] : null;
        $properties['destination_path'] = isset($properties['destination_path']) ? $properties['destination_path'] : null;
        $properties['pdf_security_enabled'] = isset($properties['pdf_security_enabled']) ? $properties['pdf_security_enabled'] : 0;
        $properties['pdf_security_open_password'] = isset($properties['pdf_security_open_password']) ? $properties['pdf_security_open_password'] : '';
        $properties['pdf_security_owner_password'] = isset($properties['pdf_security_owner_password']) ? $properties['pdf_security_owner_password'] : '';
        $properties['pdf_security_permissions'] = isset($properties['pdf_security_permissions']) ? $properties['pdf_security_permissions'] : null;

        return $properties;
    }

}
