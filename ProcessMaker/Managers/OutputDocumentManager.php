<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\OutputDocument;
use ProcessMaker\Model\Process;

class OutputDocumentManager
{
    /**
     * Get a list of All Output Documents in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $start = $options['current_page'];
        Paginator::currentPageResolver(function () use ($start) {
            return $start;
        });
        $query = OutputDocument::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('filename', 'like', $filter)
                    ->orWhere('report_generator', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('versioning', 'like', $filter)
                    ->orWhere('current_revision', 'like', $filter)
                    ->orWhere('tags', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new Output Document in a process.
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

        return $outputDocument->refresh();
    }

    /**
     * Update Output Document in a process.
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
            $data['properties'] = $this->dataProperties(array_merge($outputDocument->properties, $data['properties']));
        }
        $outputDocument->fill($data);
        $this->validate($outputDocument->toArray());
        $outputDocument->saveOrFail();
        return $outputDocument->refresh();
    }


    /**
     * Remove Output Document in a process.
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
    private function validate($data)
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
