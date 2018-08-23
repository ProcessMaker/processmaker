<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorImplementation;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\ProcessCategory;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Api\Designer\ProcessBpmnController;

class ProcessCategoryManager
{

    /**
     * Provides a list of the process categories.
     *
     * @param array $options
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(array $options)
    {
        // Grab pagination data
        $perPage = $options['per_page'];
        $currentPage = $options['current_page'];
        // Filter
        $filter = $options['filter'];
        // Default order by
        $orderBy = $options['order_by'];
        $orderDirection = $options['order_direction'];
        
        $this->validate(
            [
                'per_page' => $perPage,
                'current_page' => $currentPage,
            ],
            [
                'per_page' => 'nullable|numeric|min:0',
                'current_page' => 'nullable|numeric|min:0',
            ]
        );

        $query = ProcessCategory::where('uid', '!=', '')
                 ->withCount('processes');

        $filter === null ? : $query->where(
            'name', 'like', '%' . $filter . '%'
        );
        $orderBy === null ? : $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($perPage)->appends($options);
    }

    /**
     * Stores a new process category.
     *
     * @param array $data
     *
     * @return \ProcessMaker\Model\ProcessCategory
     */
    public function store(array $data)
    {
        $processCategory = new ProcessCategory();
        $data['uid'] = str_replace('-', '', Uuid::uuid4());
        $processCategory->fill($data);
        $processCategory->saveOrFail();
        return $processCategory;
    }

    /**
     * Update a process category.
     *
     * @param ProcessCategory $processCategory
     * @param array $data
     *
     * @return \ProcessMaker\Model\ProcessCategory
     */
    public function update(ProcessCategory $processCategory, array $data)
    {
        $processCategory->fill($data);
        $processCategory->saveOrFail();
        return $processCategory;
    }

    /**
     * Remove a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return bool
     */
    public function remove(ProcessCategory $processCategory)
    {
        $this->validate(
            [
                'processCategory' => $processCategory,
            ],
            [
                'processCategory' => 'process_category_manager.category_does_not_have_processes',
            ]
        );
        return $processCategory->delete();
    }

    /**
     * Validate the given data with the given rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @throws ValidationException
     */
    private function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        $validator = Validator::make($data, $rules, $messages, $customAttributes);

        /**
         * Validate that the category does not have processes.
         */
        $validator->addExtension(
            'process_category_manager.category_does_not_have_processes',
            function ($attribute, $processCategory, $parameters, ValidatorImplementation $validator) {
                return $processCategory->processes()->count() === 0;
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
